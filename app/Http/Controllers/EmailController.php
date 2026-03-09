<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\NotificationEmail;
use App\Mail\SendEmail;
use App\Models\Mensagem;
use App\Models\SmtpAccount;
use App\Jobs\SendEmailJob;
use App\Services\SmtpAccountSelector;
use Tymon\JWTAuth\Facades\JWTAuth;

class EmailController extends Controller
{
    public function send(Request $request)
    {
        // Validação dos dados de entrada
        $request->validate([
            'email' => 'required|email',
            'subject' => 'required|string',
            'message' => 'required|string',
        ]);

        // Preparando os dados para o e-mail
        $emailData = [
            'subject' => $request->input('subject'),
            'message' => $request->input('message')
        ];

        // Enviando o e-mail
        Mail::to($request->input('email'))->send(new NotificationEmail($emailData));
        return response()->json(['message' => 'E-mail enviado com sucesso!'], 200);
    }

    public function sendEmail(Request $request)
    {
        // Validação dos dados de entrada
        $request->validate([
            'email' => 'required|email',
            'subject' => 'required|string',
            'message' => 'required|string',
        ]);
        // Preparando os dados para o e-mail
        $emailData = [
            'subject' => $request->input('subject'),
            'message' => $request->input('message')
        ];

        // Enviando o e-mail
        Mail::to($request->input('email'))->send(new SendEmail($emailData));
        return response()->json(['message' => 'E-mail enviado com sucesso!'], 200);
    }

    function envioemail()
    {
        $user = JWTAuth::parseToken()->authenticate();
        /*
        if ($user->can('edit articles')) {
            // Usuário tem permissão para editar artigos
        }
        */
        if ($user->hasRole('admin')) {
            // Usuário possui o papel de administrador
            print('sou admin');
        }
        print($user);

        $mensagens = Mensagem::orderBy('data', 'ASC')
            ->where('situacao', 0)
            ->get();
        $i = 0;
        if (count($mensagens) > 0) {
            foreach ($mensagens as $mensagem) :

                if ($i++ > 2) break;
                //Mail::send(new Mensagem($mensagem));
                $emailData = [
                    'subject' => $mensagem->assunto,
                    'message' => $mensagem->corpo
                ];
                Mail::to($mensagem->email_destino)->send(new SendEmail($emailData));

                $mail = Mensagem::find($mensagem->id);
                $mail->situacao = 1;
                $mail->save();
            endforeach;
        }
        return response()->json(['message' => $i .' e-mail(s) enviado(s)'], 200);
    }

    function store(Request $request)
    {
        try {
            $request->validate([
                'email_destino' => 'required|email',
                'assunto' => 'required|string',
                'corpo' => 'required|string',
                'from_email' => 'required|email', // Novo campo obrigatório
                'from_name' => 'nullable|string'
            ]);

            try {
                // Criar mensagem com novo sistema
                $mail = new Mensagem();
                $mail->email_destino = $request->email_destino;
                $mail->assunto = $request->assunto;
                $mail->corpo = $request->corpo;
                $mail->from_email = $request->from_email;
                $mail->from_name = $request->from_name;

                // Novos campos
                $mail->status = 'pending';
                $mail->retry_count = 0;

                // Campos antigos (mantidos por compatibilidade)
                $mail->data = date("Y-m-d");
                $mail->hora = date("H:i:s");
                $mail->situacao = 0;

                $mail->save();

                // Despachar email para fila imediatamente
                SendEmailJob::dispatch($mail);

                return response()->json([
                    'message' => 'Email enfileirado para envio',
                    'id' => $mail->id,
                    'status' => 'pending'
                ], 201);

            } catch (\Exception $e) {
                return response()->json(['message' => $e->getMessage()], 400);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Obtém o status detalhado de uma mensagem
     * GET /messages/{id}/status
     */
    public function status($id)
    {
        $mensagem = Mensagem::with(['smtpAccount', 'emailSendAttempts.smtpAccount'])
            ->find($id);

        if (!$mensagem) {
            return response()->json(['message' => 'Mensagem não encontrada'], 404);
        }

        return response()->json([
            'id' => $mensagem->id,
            'email_destino' => $mensagem->email_destino,
            'from_email' => $mensagem->from_email,
            'assunto' => $mensagem->assunto,
            'status' => $mensagem->status,
            'retry_count' => $mensagem->retry_count,
            'next_retry_at' => $mensagem->next_retry_at,
            'sent_at' => $mensagem->sent_at,
            'failed_at' => $mensagem->failed_at,
            'smtp_account' => $mensagem->smtpAccount ? [
                'id' => $mensagem->smtpAccount->id,
                'name' => $mensagem->smtpAccount->name,
                'from_email' => $mensagem->smtpAccount->from_email,
            ] : null,
            'attempts' => $mensagem->emailSendAttempts->map(function ($attempt) {
                return [
                    'attempt_number' => $attempt->attempt_number,
                    'status' => $attempt->status,
                    'attempted_at' => $attempt->attempted_at,
                    'sent_at' => $attempt->sent_at,
                    'error_message' => $attempt->error_message,
                    'smtp_account' => $attempt->smtpAccount ? [
                        'name' => $attempt->smtpAccount->name,
                        'from_email' => $attempt->smtpAccount->from_email,
                    ] : null,
                ];
            }),
            'created_at' => $mensagem->created_at,
        ], 200);
    }

    /**
     * Obtém estatísticas de todas as contas SMTP
     * GET /smtp-accounts/stats
     */
    public function stats(SmtpAccountSelector $selector)
    {
        $accounts = SmtpAccount::active()->get();

        $stats = $accounts->map(function ($account) {
            $emailsSentLastHour = $account->getEmailsSentLastHour();
            $canSendNow = $account->canSendNow();

            return [
                'id' => $account->id,
                'name' => $account->name,
                'from_email' => $account->from_email,
                'emails_sent_last_hour' => $emailsSentLastHour,
                'hourly_limit' => $account->hourly_limit,
                'can_send_now' => $canSendNow,
                'utilization_percent' => round(($emailsSentLastHour / $account->hourly_limit) * 100, 2),
            ];
        });

        return response()->json([
            'total_accounts' => $accounts->count(),
            'accounts' => $stats,
        ], 200);
    }
}
