<?php

use App\Models\Mensagem;
use App\Jobs\SendEmailJob;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n=== Resetar e Retentar Email ===\n\n";

$id = $argv[1] ?? 1;

$mensagem = Mensagem::find($id);

if (!$mensagem) {
    echo "❌ Mensagem ID {$id} não encontrada!\n\n";
    exit(1);
}

echo "Mensagem ID: {$mensagem->id}\n";
echo "Para: {$mensagem->email_destino}\n";
echo "De: {$mensagem->from_email}\n";
echo "Assunto: {$mensagem->assunto}\n";
echo "Status atual: {$mensagem->status}\n";
echo "Tentativas: {$mensagem->retry_count}\n\n";

// Resetar status
$mensagem->status = 'pending';
$mensagem->retry_count = 0;
$mensagem->next_retry_at = null;
$mensagem->failed_at = null;
$mensagem->save();

echo "✅ Status resetado para 'pending'\n";
echo "✅ Retry count zerado\n\n";

// Despachar para fila novamente
SendEmailJob::dispatch($mensagem);

echo "✅ Email despachado para a fila!\n";
echo "\nAguarde alguns segundos e verifique o status:\n";
echo "  php artisan tinker\n";
echo "  App\\Models\\Mensagem::find({$id})\n\n";

echo "Ou consulte via API:\n";
echo "  curl http://localhost:8000/api/messages/{$id}/status\n\n";
