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
        Schema::create('smtp_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Nome identificador amigável da conta');
            $table->string('host');
            $table->integer('port')->default(587);
            $table->string('username');
            $table->text('password')->comment('Armazenado com encrypted cast no model');
            $table->string('encryption')->nullable()->comment('tls, ssl ou null');
            $table->string('from_email')->unique()->comment('Email remetente padrão - usado para mapear mensagens');
            $table->string('from_name')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->integer('hourly_limit')->default(100)->comment('Máximo de emails por hora');
            $table->timestamps();
            
            $table->index('from_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('smtp_accounts');
    }
};
