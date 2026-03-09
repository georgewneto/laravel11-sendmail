# Sistema Multi-SMTP - Guia de Implementação

## ✅ Implementação Concluída

O sistema foi atualizado com suporte completo para múltiplas contas SMTP, controle de taxa de envio (rate limiting), sistema de retry inteligente e logging detalhado.

---

## 🚀 Como Iniciar

### 1. Executar Migrations

```bash
php artisan migrate
```

Isso criará as seguintes tabelas:
- `smtp_accounts` - Contas SMTP configuradas
- `email_send_attempts` - Histórico de todas as tentativas de envio
- Atualizará `mensagens` com novos campos (from_email, status, retry_count, etc.)

### 2. Popular Contas SMTP

```bash
php artisan db:seed --class=SmtpAccountSeeder
```

⚠️ **IMPORTANTE**: As contas criadas são exemplos. Edite-as no banco de dados com suas credenciais reais:

```sql
UPDATE smtp_accounts 
SET username = 'seu-email@gmail.com', 
    password = 'sua-senha-app',
    from_email = 'seu-email@gmail.com'
WHERE id = 1;
```

### 3. Iniciar Queue Worker

O sistema usa Laravel Queue para processamento assíncrono:

```bash
php artisan queue:work --tries=1 --timeout=60
```

**Dica**: Use Supervisor em produção para manter o worker rodando continuamente.

### 4. Configurar Scheduler (Cron)

Adicione ao crontab para processar emails pendentes automaticamente:

```bash
* * * * * cd /caminho/para/projeto && php artisan schedule:run >> /dev/null 2>&1
```

O comando `email:process-pending` rodará a cada 5 minutos automaticamente.

---

## 📡 API Endpoints

### **POST /store** - Enfileirar Email (NOVO)

Cria uma mensagem e a enfileira para envio.

**Request:**
```json
{
  "email_destino": "destinatario@exemplo.com",
  "assunto": "Título do Email",
  "corpo": "Conteúdo do email",
  "from_email": "principal@empresa.com",  // OBRIGATÓRIO - determina qual conta SMTP usar
  "from_name": "Empresa XYZ"              // OPCIONAL
}
```

**Response (201):**
```json
{
  "message": "Email enfileirado para envio",
  "id": 42,
  "status": "pending"
}
```

**Como funciona:**
1. O sistema busca a conta SMTP onde `from_email` = "principal@empresa.com"
2. Verifica se a conta não excedeu 100 emails/hora
3. Se disponível: envia imediatamente
4. Se limite atingido: aguarda próxima janela disponível com retry automático

---

### **GET /messages/{id}/status** - Status da Mensagem (NOVO)

Retorna status detalhado e histórico de tentativas.

**Response (200):**
```json
{
  "id": 42,
  "email_destino": "destinatario@exemplo.com",
  "from_email": "principal@empresa.com",
  "assunto": "Título do Email",
  "status": "sent",  // pending | processing | sent | failed | max_retries_exceeded
  "retry_count": 1,
  "next_retry_at": null,
  "sent_at": "2026-03-07T14:30:00.000000Z",
  "failed_at": null,
  "smtp_account": {
    "id": 1,
    "name": "Conta Principal - Gmail",
    "from_email": "principal@empresa.com"
  },
  "attempts": [
    {
      "attempt_number": 1,
      "status": "sent",
      "attempted_at": "2026-03-07T14:29:58.000000Z",
      "sent_at": "2026-03-07T14:30:00.000000Z",
      "error_message": null,
      "smtp_account": {
        "name": "Conta Principal - Gmail",
        "from_email": "principal@empresa.com"
      }
    }
  ],
  "created_at": "2026-03-07T14:29:55.000000Z"
}
```

---

### **GET /smtp-accounts/stats** - Estatísticas SMTP (NOVO)

Retorna estatísticas de uso de todas as contas SMTP.

**Response (200):**
```json
{
  "total_accounts": 3,
  "accounts": [
    {
      "id": 1,
      "name": "Conta Principal - Gmail",
      "from_email": "principal@empresa.com",
      "emails_sent_last_hour": 47,
      "hourly_limit": 100,
      "can_send_now": true,
      "utilization_percent": 47.0
    },
    {
      "id": 2,
      "name": "Conta Marketing - SendGrid",
      "from_email": "marketing@empresa.com",
      "emails_sent_last_hour": 100,
      "hourly_limit": 100,
      "can_send_now": false,
      "utilization_percent": 100.0
    }
  ]
}
```

---

### **Endpoints Deprecados** (mantidos por compatibilidade)

- `GET /envioemail` - Use o sistema de queue ao invés de processamento manual
- `POST /send-email` - Use `POST /store` com `from_email`
- `POST /send` - Use `POST /store` com `from_email`

---

## 🔧 Funcionalidades Implementadas

### ✅ 1. Múltiplas Contas SMTP
- Armazena configurações de várias contas SMTP no banco de dados
- Cada conta tem: host, porta, usuário, senha (criptografada), email remetente, limite horário
- Seleção automática baseada no campo `from_email` da requisição

### ✅ 2. Rate Limiting (Controle de Taxa)
- Limite configurável por conta SMTP (padrão: 100 emails/hora)
- Janela deslizante de 1 hora (não reset fixo)
- Email aguarda automaticamente se limite atingido

### ✅ 3. Sistema de Retry Inteligente
- Até 10 tentativas automáticas por email
- Backoff exponencial: 5min → 15min → 30min → 1h → 2h → 4h → 8h → 16h → 24h → 48h
- Após 10 falhas: status muda para `max_retries_exceeded`

### ✅ 4. Logging Completo
- Tabela `email_send_attempts` registra cada tentativa
- Campos rastreados: data/hora da tentativa, conta SMTP usada, status, mensagem de erro
- Histórico completo acessível via endpoint `/messages/{id}/status`

### ✅ 5. Processamento Assíncrono
- Laravel Queue Worker processa emails em background
- Comando agendado (`email:process-pending`) roda a cada 5 minutos
- Não bloqueia a API durante envio

---

## 📊 Estrutura do Banco de Dados

### Tabela: `smtp_accounts`
| Campo | Tipo | Descrição |
|---|---|---|
| id | bigint | PK |
| name | string | Nome identificador |
| host | string | Servidor SMTP |
| port | integer | Porta (padrão: 587) |
| username | string | Usuário SMTP |
| password | text | Senha criptografada |
| encryption | string | tls, ssl ou null |
| from_email | string | Email remetente (único) |
| from_name | string | Nome remetente |
| is_active | boolean | Conta ativa? |
| hourly_limit | integer | Emails/hora (padrão: 100) |

### Tabela: `email_send_attempts`
| Campo | Tipo | Descrição |
|---|---|---|
| id | bigint | PK |
| mensagem_id | bigint | FK → mensagens |
| smtp_account_id | bigint | FK → smtp_accounts |
| attempt_number | integer | 1-10 |
| status | enum | pending, sent, failed |
| error_message | text | Erro se falhou |
| attempted_at | timestamp | Quando tentou |
| sent_at | timestamp | Quando enviou com sucesso |

### Tabela: `mensagens` (campos novos)
| Campo | Tipo | Descrição |
|---|---|---|
| from_email | string | Email remetente (determina conta SMTP) |
| from_name | string | Nome remetente |
| smtp_account_id | bigint | FK → conta SMTP usada |
| status | enum | pending, processing, sent, failed, max_retries_exceeded |
| retry_count | integer | Número de tentativas (0-10) |
| next_retry_at | timestamp | Quando tentar novamente |
| sent_at | timestamp | Quando enviou com sucesso |
| failed_at | timestamp | Quando falhou definitivamente |

---

## 🧪 Testando o Sistema

### Teste 1: Enviar Email com Conta Válida

```bash
curl -X POST http://localhost:8000/api/store \
  -H "Authorization: Bearer SEU_TOKEN_JWT" \
  -H "Content-Type: application/json" \
  -d '{
    "email_destino": "teste@exemplo.com",
    "assunto": "Teste Multi-SMTP",
    "corpo": "Testando o novo sistema!",
    "from_email": "principal@empresa.com"
  }'
```

### Teste 2: Verificar Status

```bash
curl -X GET http://localhost:8000/api/messages/1/status \
  -H "Authorization: Bearer SEU_TOKEN_JWT"
```

### Teste 3: Ver Estatísticas

```bash
curl -X GET http://localhost:8000/api/smtp-accounts/stats \
  -H "Authorization: Bearer SEU_TOKEN_JWT"
```

### Teste 4: Simular Rate Limiting

Envie 101 emails rapidamente com o mesmo `from_email` e veja o comportamento:
- Primeiros 100: enviam imediatamente
- 101º em diante: aguardam próxima janela

### Teste 5: Processar Pendentes Manualmente

```bash
php artisan email:process-pending
```

---

## 🐛 Troubleshooting

### Queue worker não está processando

```bash
# Verificar se há jobs na fila
php artisan queue:work --once

# Ver logs
tail -f storage/logs/laravel.log
```

### Email não está sendo enviado

1. Verifique se a conta SMTP está ativa: `SELECT * FROM smtp_accounts WHERE is_active = 1;`
2. Verifique se não atingiu o limite: endpoint `/smtp-accounts/stats`
3. Veja o histórico de tentativas: endpoint `/messages/{id}/status`
4. Cheque o log: `storage/logs/laravel.log`

### Senha da conta SMTP está errada

```sql
UPDATE smtp_accounts 
SET password = 'nova-senha'
WHERE id = 1;
```

A senha será automaticamente criptografada pelo Laravel.

---

## 🔒 Segurança

- ✅ Senhas SMTP criptografadas no banco (encrypted cast)
- ✅ Todos os endpoints protegidos por JWT
- ✅ Validação de entrada em todas as requisições
- ✅ Senhas nunca retornadas em responses JSON

---

## 📈 Próximos Passos (Opcionais)

1. **Dashboard Web**: Interface para gerenciar contas SMTP e visualizar estatísticas
2. **Webhooks**: Notificações quando email é enviado/falha
3. **Templates HTML**: Sistema de templates customizáveis
4. **Health Check**: Endpoint para testar conectividade das contas SMTP
5. **Prioridades**: Campo `priority` para processar emails urgentes primeiro

---

## 📞 Suporte

Para dúvidas ou problemas:
1. Verifique os logs: `storage/logs/laravel.log`
2. Teste a conexão SMTP manualmente com telnet
3. Verifique as credenciais das contas SMTP no banco de dados

---

**Sistema implementado com sucesso! 🎉**
