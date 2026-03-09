# 🚀 PRÓXIMOS PASSOS - Configure e Teste

## ✅ Status Atual

- ✅ Migrations executadas com sucesso
- ✅ Tabelas criadas: `smtp_accounts`, `email_send_attempts`
- ✅ Campos adicionados à tabela `mensagens`
- ✅ 3 contas SMTP de exemplo criadas

## 📝 Passo 1: Configurar Credenciais SMTP Reais

As contas criadas são exemplos. Você precisa atualizá-las com suas credenciais reais.

### Opção A: Via Tinker (Recomendado)

```bash
php artisan tinker
```

Depois execute:

```php
# Atualizar Conta 1 (Gmail Principal)
$account = App\Models\SmtpAccount::find(1);
$account->username = 'seu-email@gmail.com';
$account->password = 'sua-senha-app-gmail';  // Senha de app do Gmail
$account->from_email = 'seu-email@gmail.com';
$account->from_name = 'Sua Empresa';
$account->save();

# Atualizar Conta 2 (Gmail Secundário)
$account = App\Models\SmtpAccount::find(2);
$account->username = 'outro-email@gmail.com';
$account->password = 'outra-senha-app';
$account->from_email = 'outro-email@gmail.com';
$account->save();

# Atualizar Conta 3 (SendGrid ou outra)
$account = App\Models\SmtpAccount::find(3);
$account->host = 'smtp.sendgrid.net';
$account->username = 'apikey';
$account->password = 'SG.sua-chave-api-aqui';
$account->from_email = 'noreply@seudominio.com';
$account->save();

exit
```

### Opção B: Via SQL Diretamente

```sql
UPDATE smtp_accounts 
SET username = 'seu-email@gmail.com',
    password = 'sua-senha-app',
    from_email = 'seu-email@gmail.com',
    from_name = 'Sua Empresa'
WHERE id = 1;
```

⚠️ **Importante**: Para Gmail, você precisa gerar uma "Senha de App" em:
https://myaccount.google.com/apppasswords

---

## 🔧 Passo 2: Iniciar Queue Worker

O sistema usa Laravel Queue para processar emails. Abra um terminal separado e execute:

```bash
php artisan queue:work --tries=1 --timeout=60
```

**Deixe esse terminal rodando!** Ele processará os emails continuamente.

💡 **Dica**: Em produção, use Supervisor para manter o worker ativo 24/7.

---

## 🧪 Passo 3: Testar Envio de Email

### Método 1: Via API (curl)

Primeiro, obtenha um token JWT (se ainda não tiver):

```bash
# Substitua com sua rota de login
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password"}'
```

Depois envie um email de teste:

```bash
curl -X POST http://localhost:8000/api/store \
  -H "Authorization: Bearer SEU_TOKEN_JWT_AQUI" \
  -H "Content-Type: application/json" \
  -d '{
    "email_destino": "seu-email-teste@gmail.com",
    "assunto": "Teste Sistema Multi-SMTP",
    "corpo": "Este é um email de teste do novo sistema!",
    "from_email": "seu-email@gmail.com",
    "from_name": "Sistema de Testes"
  }'
```

### Método 2: Via Tinker (mais fácil para primeiro teste)

```bash
php artisan tinker
```

```php
use App\Models\Mensagem;
use App\Jobs\SendEmailJob;

# Criar mensagem de teste
$msg = new Mensagem();
$msg->email_destino = 'seu-email-teste@gmail.com';
$msg->assunto = 'Teste Sistema Multi-SMTP';
$msg->corpo = 'Este é um email de teste!';
$msg->from_email = 'seu-email@gmail.com';  # Use o email que você configurou
$msg->from_name = 'Sistema Teste';
$msg->status = 'pending';
$msg->retry_count = 0;
$msg->data = date('Y-m-d');
$msg->hora = date('H:i:s');
$msg->situacao = 0;
$msg->save();

# Despachar para fila
SendEmailJob::dispatch($msg);

echo "Email enfileirado! ID: " . $msg->id;
exit
```

---

## 📊 Passo 4: Verificar Status do Email

### Via Script PHP:

```bash
php artisan tinker
```

```php
# Verificar mensagem
$msg = App\Models\Mensagem::with('emailSendAttempts')->find(1);
echo "Status: " . $msg->status . "\n";
echo "Tentativas: " . $msg->retry_count . "\n";

# Ver histórico de tentativas
$msg->emailSendAttempts->each(function($attempt) {
    echo "Tentativa #" . $attempt->attempt_number . " - " . $attempt->status;
    if ($attempt->error_message) echo " - Erro: " . $attempt->error_message;
    echo "\n";
});
```

### Via API:

```bash
curl -X GET http://localhost:8000/api/messages/1/status \
  -H "Authorization: Bearer SEU_TOKEN_JWT"
```

---

## 📈 Passo 5: Ver Estatísticas das Contas

```bash
curl -X GET http://localhost:8000/api/smtp-accounts/stats \
  -H "Authorization: Bearer SEU_TOKEN_JWT"
```

Ou use o script criado:

```bash
php check-smtp-accounts.php
```

---

## 🔍 Logs e Troubleshooting

### Ver logs em tempo real:

```powershell
# PowerShell
Get-Content storage/logs/laravel.log -Tail 50 -Wait
```

### Ver jobs na fila:

```bash
# Ver tabela jobs
php artisan tinker
DB::table('jobs')->count();
DB::table('jobs')->get();
```

### Processar pendentes manualmente:

```bash
php artisan email:process-pending
```

---

## ⚙️ Passo 6: Configurar Processamento Automático (Opcional)

Para processar emails pendentes automaticamente a cada 5 minutos, configure o cron:

### No Windows (Task Scheduler):
1. Abra o Agendador de Tarefas
2. Crie nova tarefa
3. Ação: `php C:\developer\PHP\Laravel\laravel11-sendmail\artisan schedule:run`
4. Agendar: A cada 1 minuto

### No Linux/Mac:
```bash
crontab -e
```

Adicione:
```
* * * * * cd /caminho/para/projeto && php artisan schedule:run >> /dev/null 2>&1
```

---

## ✅ Checklist de Verificação

- [ ] Credenciais SMTP atualizadas
- [ ] Queue worker rodando (`php artisan queue:work`)
- [ ] Email de teste enviado com sucesso
- [ ] Status verificado via API ou tinker
- [ ] Logs verificados sem erros
- [ ] Estatísticas das contas consultadas

---

## 🆘 Problemas Comuns

### Email não é enviado

1. **Verifique se o worker está rodando**: `ps aux | grep queue:work`
2. **Veja os logs**: `tail -f storage/logs/laravel.log`
3. **Verifique credenciais**: Teste login manual no SMTP
4. **Confira o from_email**: Deve corresponder a uma conta cadastrada

### "Nenhuma conta SMTP encontrada"

```bash
php artisan tinker
App\Models\SmtpAccount::where('from_email', 'seu-email@gmail.com')->first();
```

Se retornar `null`, atualize o `from_email` da conta.

### Gmail está bloqueando

- Use "Senhas de App" (não a senha normal)
- Ative "Acesso de apps menos seguros" (não recomendado)
- Considere usar SendGrid, Mailgun ou SES para produção

---

## 📚 Documentação Completa

- [SISTEMA-MULTI-SMTP.md](SISTEMA-MULTI-SMTP.md) - Manual completo da API
- [RESUMO-IMPLEMENTACAO.md](RESUMO-IMPLEMENTACAO.md) - Resumo da implementação
- [COMANDOS-SETUP.txt](COMANDOS-SETUP.txt) - Lista de comandos úteis

---

**Pronto para começar! 🎉**
