<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Mensagem extends Model
{
    use HasFactory;

    protected $table = 'mensagens';

    protected $guarded = ['id'];

    protected $fillable = [
        // Campos antigos (mantidos por compatibilidade)
        'email_destino',
        'assunto',
        'corpo',
        'data',
        'hora',
        'situacao',
        // Novos campos para sistema multi-SMTP
        'from_email',
        'from_name',
        'smtp_account_id',
        'status',
        'retry_count',
        'next_retry_at',
        'sent_at',
        'failed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'situacao' => 'boolean',
        'retry_count' => 'integer',
        'next_retry_at' => 'datetime',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
        'data' => 'date',
    ];

    /**
     * Relacionamento com conta SMTP
     */
    public function smtpAccount(): BelongsTo
    {
        return $this->belongsTo(SmtpAccount::class);
    }

    /**
     * Relacionamento com tentativas de envio
     */
    public function emailSendAttempts(): HasMany
    {
        return $this->hasMany(EmailSendAttempt::class);
    }

    /**
     * Scope para mensagens pendentes de retry
     * Status pending ou failed, retry_count < 10 e (next_retry_at é null ou já passou)
     */
    public function scopePendingRetry($query)
    {
        return $query->whereIn('status', ['pending', 'failed'])
            ->where('retry_count', '<', 10)
            ->where(function ($q) {
                $q->whereNull('next_retry_at')
                  ->orWhere('next_retry_at', '<=', Carbon::now());
            });
    }

    /**
     * Incrementa contador de retry e calcula próximo horário com backoff exponencial
     * Backoff: 5min, 15min, 30min, 1h, 2h, 4h, 8h, 16h, 24h, 48h
     */
    public function incrementRetry(): void
    {
        $this->retry_count++;
        
        // Define intervalos de backoff exponencial (em minutos)
        $backoffMinutes = [
            1 => 5,      // 1ª tentativa: 5 minutos
            2 => 15,     // 2ª tentativa: 15 minutos
            3 => 30,     // 3ª tentativa: 30 minutos
            4 => 60,     // 4ª tentativa: 1 hora
            5 => 120,    // 5ª tentativa: 2 horas
            6 => 240,    // 6ª tentativa: 4 horas
            7 => 480,    // 7ª tentativa: 8 horas
            8 => 960,    // 8ª tentativa: 16 horas
            9 => 1440,   // 9ª tentativa: 24 horas
            10 => 2880,  // 10ª tentativa: 48 horas
        ];

        $minutes = $backoffMinutes[$this->retry_count] ?? 2880;
        $this->next_retry_at = Carbon::now()->addMinutes($minutes);
        
        // Se atingiu o máximo de tentativas, marcar como excedido
        if ($this->retry_count >= 10) {
            $this->status = 'max_retries_exceeded';
            $this->failed_at = Carbon::now();
        } else {
            $this->status = 'failed';
        }
        
        $this->save();
    }
}
