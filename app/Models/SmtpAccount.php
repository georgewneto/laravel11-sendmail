<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class SmtpAccount extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'host',
        'port',
        'username',
        'password',
        'encryption',
        'from_email',
        'from_name',
        'is_active',
        'hourly_limit',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'password' => 'encrypted',
        'is_active' => 'boolean',
        'port' => 'integer',
        'hourly_limit' => 'integer',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Relacionamento com mensagens
     */
    public function mensagens(): HasMany
    {
        return $this->hasMany(Mensagem::class);
    }

    /**
     * Relacionamento com tentativas de envio
     */
    public function emailSendAttempts(): HasMany
    {
        return $this->hasMany(EmailSendAttempt::class);
    }

    /**
     * Scope para contas ativas
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Verifica se a conta pode enviar emails agora (não excedeu o limite horário)
     *
     * @return bool
     */
    public function canSendNow(): bool
    {
        $emailsSentLastHour = $this->getEmailsSentLastHour();
        return $emailsSentLastHour < $this->hourly_limit;
    }

    /**
     * Obtém a quantidade de emails enviados pela conta na última hora
     *
     * @return int
     */
    public function getEmailsSentLastHour(): int
    {
        $oneHourAgo = Carbon::now()->subHour();
        
        return $this->emailSendAttempts()
            ->where('status', 'sent')
            ->where('sent_at', '>=', $oneHourAgo)
            ->count();
    }

    /**
     * Obtém estatísticas da conta
     *
     * @return array
     */
    public function getStats(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'from_email' => $this->from_email,
            'emails_sent_last_hour' => $this->getEmailsSentLastHour(),
            'hourly_limit' => $this->hourly_limit,
            'can_send_now' => $this->canSendNow(),
            'is_active' => $this->is_active,
        ];
    }
}
