<?php

namespace App\Services;

use App\Models\SmtpAccount;
use Illuminate\Support\Facades\Log;

class SmtpAccountSelector
{
    /**
     * Seleciona uma conta SMTP baseada no email remetente
     * Verifica se a conta está ativa e se não excedeu o limite horário
     *
     * @param string $fromEmail
     * @return SmtpAccount|null
     */
    public function selectAccountForEmail(string $fromEmail): ?SmtpAccount
    {
        // Busca conta SMTP correspondente ao from_email
        $account = SmtpAccount::where('from_email', $fromEmail)
            ->where('is_active', true)
            ->first();

        if (!$account) {
            Log::warning("Nenhuma conta SMTP ativa encontrada para o email: {$fromEmail}");
            return null;
        }

        // Verifica se a conta não excedeu o limite horário
        if (!$account->canSendNow()) {
            $emailsSent = $account->getEmailsSentLastHour();
            Log::info("Conta SMTP '{$account->name}' ({$account->from_email}) atingiu o limite horário: {$emailsSent}/{$account->hourly_limit} emails na última hora");
            return null;
        }

        return $account;
    }

    /**
     * Obtém todas as contas SMTP ativas e suas estatísticas
     *
     * @return array
     */
    public function getAccountsStats(): array
    {
        $accounts = SmtpAccount::active()->get();
        
        return $accounts->map(function ($account) {
            return $account->getStats();
        })->toArray();
    }
}
