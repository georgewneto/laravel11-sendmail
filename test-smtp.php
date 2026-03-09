<?php

use App\Models\SmtpAccount;
use App\Mail\SendEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n=== Teste de Envio SMTP Direto ===\n\n";

$fromEmail = $argv[1] ?? 'avisos@prefeituradeitabuna.com.br';
$toEmail = $argv[2] ?? 'georgewneto@gmail.com';

// Buscar conta SMTP
$account = SmtpAccount::where('from_email', $fromEmail)->first();

if (!$account) {
    echo "❌ Conta SMTP não encontrada para: {$fromEmail}\n";
    echo "\nContas disponíveis:\n";
    SmtpAccount::all()->each(function($acc) {
        echo "  - {$acc->from_email} ({$acc->name})\n";
    });
    echo "\n";
    exit(1);
}

echo "📧 Conta SMTP Encontrada:\n";
echo "  Nome: {$account->name}\n";
echo "  Host: {$account->host}:{$account->port}\n";
echo "  From: {$account->from_email}\n";
echo "  Usuário: {$account->username}\n";
echo "  Criptografia: " . ($account->encryption ?? 'none') . "\n\n";

// Configurar mailer dinamicamente
Config::set('mail.mailers.smtp_test', [
    'transport' => 'smtp',
    'host' => $account->host,
    'port' => $account->port,
    'encryption' => $account->encryption,
    'username' => $account->username,
    'password' => $account->password,
    'timeout' => null,
    'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url(config('app.url'), PHP_URL_HOST)),
]);

Config::set('mail.from', [
    'address' => $account->from_email,
    'name' => $account->from_name,
]);

echo "🔧 Configuração do mailer:\n";
echo "  Mailer: smtp_test\n";
echo "  Transport: smtp\n\n";

// Preparar dados do email
$emailData = [
    'subject' => 'Teste de Envio SMTP - ' . date('Y-m-d H:i:s'),
    'message' => 'Este é um email de teste para verificar a configuração SMTP.\n\nSistema Multi-SMTP está funcionando!'
];

echo "📤 Enviando email de teste...\n";
echo "  Para: {$toEmail}\n";
echo "  Assunto: {$emailData['subject']}\n\n";

try {
    Mail::mailer('smtp_test')
        ->to($toEmail)
        ->send(new SendEmail($emailData));
    
    echo "✅ EMAIL ENVIADO COM SUCESSO!\n\n";
    echo "Verifique a caixa de entrada de: {$toEmail}\n";
    echo "(Não esqueça de verificar a pasta de spam)\n\n";
    
} catch (\Exception $e) {
    echo "❌ ERRO AO ENVIAR EMAIL:\n\n";
    echo "Tipo: " . get_class($e) . "\n";
    echo "Mensagem: " . $e->getMessage() . "\n\n";
    
    echo "Detalhes do erro:\n";
    echo $e->getTraceAsString() . "\n\n";
    
    echo "Sugestões de correção:\n";
    echo "1. Verifique se as credenciais SMTP estão corretas\n";
    echo "2. Teste a conexão manualmente: telnet {$account->host} {$account->port}\n";
    echo "3. Verifique se o firewall não está bloqueando a porta {$account->port}\n";
    echo "4. Para Gmail, use 'Senhas de App' ao invés da senha normal\n";
    echo "5. Verifique os logs: storage/logs/laravel.log\n\n";
    
    exit(1);
}
