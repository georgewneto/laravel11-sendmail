<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mensagens', function (Blueprint $table) {
            // Novos campos para sistema multi-SMTP
            $table->string('from_email')->after('email_destino')->comment('Email remetente - determina qual conta SMTP usar');
            $table->string('from_name')->nullable()->after('from_email')->comment('Nome do remetente');
            
            $table->foreignId('smtp_account_id')
                ->nullable()
                ->after('from_name')
                ->constrained('smtp_accounts')
                ->onDelete('set null')
                ->comment('Conta SMTP selecionada para envio');
            
            // Controle de status e retry
            $table->enum('status', ['pending', 'processing', 'sent', 'failed', 'max_retries_exceeded'])
                ->default('pending')
                ->after('smtp_account_id')
                ->comment('Status atual do envio');
            
            $table->integer('retry_count')->default(0)->after('status')->comment('Número de tentativas realizadas');
            $table->timestamp('next_retry_at')->nullable()->after('retry_count')->comment('Quando tentar próximo envio');
            $table->timestamp('sent_at')->nullable()->after('next_retry_at')->comment('Quando foi enviado com sucesso');
            $table->timestamp('failed_at')->nullable()->after('sent_at')->comment('Quando falhou definitivamente');
            
            // Índices para otimizar queries
            $table->index(['status', 'next_retry_at']);
            $table->index('smtp_account_id');
            $table->index('from_email');
            
            // Nota: Mantemos os campos antigos (situacao, data, hora) por compatibilidade
            // Eles podem ser removidos em uma migration futura após migração de dados
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mensagens', function (Blueprint $table) {
            $table->dropForeign(['smtp_account_id']);
            $table->dropIndex(['status', 'next_retry_at']);
            $table->dropIndex(['smtp_account_id']);
            $table->dropIndex(['from_email']);
            
            $table->dropColumn([
                'from_email',
                'from_name',
                'smtp_account_id',
                'status',
                'retry_count',
                'next_retry_at',
                'sent_at',
                'failed_at',
            ]);
        });
    }
};
