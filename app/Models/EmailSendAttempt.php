<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailSendAttempt extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'mensagem_id',
        'smtp_account_id',
        'attempt_number',
        'status',
        'error_message',
        'attempted_at',
        'sent_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'attempt_number' => 'integer',
        'attempted_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    /**
     * Relacionamento com mensagem
     */
    public function mensagem(): BelongsTo
    {
        return $this->belongsTo(Mensagem::class);
    }

    /**
     * Relacionamento com conta SMTP
     */
    public function smtpAccount(): BelongsTo
    {
        return $this->belongsTo(SmtpAccount::class);
    }
}
