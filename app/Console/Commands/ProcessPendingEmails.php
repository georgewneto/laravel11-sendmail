<?php

namespace App\Console\Commands;

use App\Models\Mensagem;
use App\Jobs\SendEmailJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessPendingEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:process-pending';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Despacha emails pendentes para a fila de processamento';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Buscando mensagens pendentes...');

        // Buscar mensagens pendentes de retry
        $pendingMessages = Mensagem::pendingRetry()->get();

        if ($pendingMessages->isEmpty()) {
            $this->info('Nenhuma mensagem pendente encontrada.');
            return Command::SUCCESS;
        }

        $count = 0;
        foreach ($pendingMessages as $mensagem) {
            SendEmailJob::dispatch($mensagem);
            $count++;
        }

        $this->info("✓ {$count} mensagem(ns) despachada(s) para processamento");
        Log::info("ProcessPendingEmails: {$count} mensagens despachadas");

        return Command::SUCCESS;
    }
}
