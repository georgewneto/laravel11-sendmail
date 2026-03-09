<?php

use App\Models\SmtpAccount;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n=== Contas SMTP Configuradas ===\n\n";

$accounts = SmtpAccount::all();

if ($accounts->isEmpty()) {
    echo "❌ Nenhuma conta SMTP encontrada!\n";
    echo "Execute: php artisan db:seed --class=SmtpAccountSeeder\n\n";
    exit(1);
}

foreach ($accounts as $account) {
    echo "ID: {$account->id}\n";
    echo "Nome: {$account->name}\n";
    echo "Host: {$account->host}:{$account->port}\n";
    echo "From: {$account->from_email}\n";
    echo "Ativa: " . ($account->is_active ? '✓ Sim' : '✗ Não') . "\n";
    echo "Limite/hora: {$account->hourly_limit}\n";
    echo "Emails enviados na última hora: " . $account->getEmailsSentLastHour() . "\n";
    echo "Pode enviar agora: " . ($account->canSendNow() ? '✓ Sim' : '✗ Não') . "\n";
    echo str_repeat('-', 50) . "\n";
}

echo "\n✅ Total: " . $accounts->count() . " conta(s) configurada(s)\n\n";
