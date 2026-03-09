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
        Schema::create('email_send_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mensagem_id')
                ->constrained('mensagens')
                ->onDelete('cascade')
                ->comment('FK para mensagens');
            $table->foreignId('smtp_account_id')
                ->nullable()
                ->constrained('smtp_accounts')
                ->onDelete('set null')
                ->comment('Conta SMTP utilizada nesta tentativa');
            $table->integer('attempt_number')->comment('Número da tentativa (1-10)');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->text('error_message')->nullable()->comment('Mensagem de erro caso falhe');
            $table->timestamp('attempted_at')->nullable()->comment('Quando a tentativa foi iniciada');
            $table->timestamp('sent_at')->nullable()->comment('Quando o email foi enviado com sucesso');
            $table->timestamps();
            
            $table->index(['mensagem_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_send_attempts');
    }
};
