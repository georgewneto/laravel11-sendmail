<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mensagem extends Model
{
    use HasFactory;

    protected $table = 'mensagens';

    protected $guarded = ['id'];

    protected $fillable = [
        'email_destino',
        'assunto',
        'corpo',
        'data',
        'hora',
        'situacao',
    ];
}
