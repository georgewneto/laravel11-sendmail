<?php

namespace App\Jobs;

use App\Models\Mensagem;
use App\Models\EmailSendAttempt;
use App\Services\SmtpAccountSelector;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use App\Mail\SendEmail;
use Carbon\Carbon;
use Exception;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 0; // Controle manual de retry
    public $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Mensagem $mensagem
    ) {}

    /**
     * Execute the job.
     */
    public function handle(SmtpAccountSelector $selector): void
    {
        // Verificar se já atingiu o máximo de tentativas
        if ($this->mensagem->retry_count >= 10) {
            $this->mensagem->status = 'max_retries_exceeded';
            $this->mensagem->failed_at = Carbon::now();
            $this->mensagem->save();
            
            Log::warning("Mensagem ID {$this->mensagem->id} atingiu o máximo de 10 tentativas");
            return;
        }

        // Selecionar conta SMTP baseada no from_email
        $smtpAccount = $selector->selectAccountForEmail($this->mensagem->from_email);

        // Se não encontrou conta ou atingiu limite, agendar retry
        if (!$smtpAccount) {
            $this->mensagem->incrementRetry();
            
            $delayMinutes = $this->getDelayMinutes($this->mensagem->retry_count);
            $this->release($delayMinutes * 60); // release em segundos
            
            Log::info("Mensagem ID {$this->mensagem->id} reagendada para {$delayMinutes} minutos (tentativa {$this->mensagem->retry_count})");
            return;
        }

        // Atualizar mensagem com a conta SMTP selecionada
        $this->mensagem->smtp_account_id = $smtpAccount->id;
        $this->mensagem->status = 'processing';
        $this->mensagem->save();

        // Criar registro de tentativa
        $attempt = EmailSendAttempt::create([
            'mensagem_id' => $this->mensagem->id,
            'smtp_account_id' => $smtpAccount->id,
            'attempt_number' => $this->mensagem->retry_count + 1,
            'status' => 'pending',
            'attempted_at' => Carbon::now(),
        ]);

        try {
            // Configurar mailer dinamicamente com as credenciais da conta SMTP
            $this->configureDynamicMailer($smtpAccount);

            // Preparar dados do email no formato esperado pelo Mailable
            $emailData = [
                'subject' => $this->mensagem->assunto,
                'message' => $this->mensagem->corpo
            ];

            // Enviar o email
            Mail::mailer('smtp_dynamic')
                ->to($this->mensagem->email_destino)
                ->send(new SendEmail($emailData));

            // Sucesso! Atualizar registros
            $this->mensagem->status = 'sent';
            $this->mensagem->sent_at = Carbon::now();
            $this->mensagem->save();

            $attempt->status = 'sent';
            $attempt->sent_at = Carbon::now();
            $attempt->save();

            Log::info("Email enviado com sucesso - Mensagem ID {$this->mensagem->id} via conta '{$smtpAccount->name}'");

        } catch (Exception $e) {
            // Falha no envio
            $errorMessage = $e->getMessage();
            
            $attempt->status = 'failed';
            $attempt->error_message = $errorMessage;
            $attempt->save();

            $this->mensagem->incrementRetry();
            
            $delayMinutes = $this->getDelayMinutes($this->mensagem->retry_count);
            $this->release($delayMinutes * 60);

            Log::error("Falha ao enviar email - Mensagem ID {$this->mensagem->id}: {$errorMessage}");
        }
    }

    /**
     * Configura o mailer dinamicamente com as credenciais da conta SMTP
     */
    private function configureDynamicMailer($smtpAccount): void
    {
        Config::set('mail.mailers.smtp_dynamic', [
            'transport' => 'smtp',
            'host' => $smtpAccount->host,
            'port' => $smtpAccount->port,
            'encryption' => $smtpAccount->encryption,
            'username' => $smtpAccount->username,
            'password' => $smtpAccount->password,
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url(config('app.url'), PHP_URL_HOST)),
        ]);

        Config::set('mail.from', [
            'address' => $smtpAccount->from_email,
            'name' => $smtpAccount->from_name,
        ]);
    }

    /**
     * Obtém o delay em minutos baseado no número de tentativas
     * Backoff: 5min, 15min, 30min, 1h, 2h, 4h, 8h, 16h, 24h, 48h
     */
    private function getDelayMinutes(int $retryCount): int
    {
        $backoffMinutes = [
            1 => 5,
            2 => 15,
            3 => 30,
            4 => 60,
            5 => 120,
            6 => 240,
            7 => 480,
            8 => 960,
            9 => 1440,
            10 => 2880,
        ];

        return $backoffMinutes[$retryCount] ?? 2880;
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error("Job falhou definitivamente - Mensagem ID {$this->mensagem->id}: {$exception->getMessage()}");
        
        $this->mensagem->status = 'max_retries_exceeded';
        $this->mensagem->failed_at = Carbon::now();
        $this->mensagem->save();
    }
}
