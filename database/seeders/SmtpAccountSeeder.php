<?php

namespace Database\Seeders;

use App\Models\SmtpAccount;
use Illuminate\Database\Seeder;

class SmtpAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            [
                'name' => 'Conta Principal - Gmail',
                'host' => 'smtp.gmail.com',
                'port' => 587,
                'username' => 'principal@empresa.com',
                'password' => 'senha-app-gmail-aqui',
                'encryption' => 'tls',
                'from_email' => 'principal@empresa.com',
                'from_name' => 'Empresa - Principal',
                'is_active' => true,
                'hourly_limit' => 100,
            ],
            [
                'name' => 'Conta Secundária - Gmail',
                'host' => 'smtp.gmail.com',
                'port' => 587,
                'username' => 'secundaria@empresa.com',
                'password' => 'senha-app-gmail-aqui',
                'encryption' => 'tls',
                'from_email' => 'secundaria@empresa.com',
                'from_name' => 'Empresa - Secundária',
                'is_active' => true,
                'hourly_limit' => 100,
            ],
            [
                'name' => 'Conta Marketing - SendGrid',
                'host' => 'smtp.sendgrid.net',
                'port' => 587,
                'username' => 'apikey',
                'password' => 'SG.xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
                'encryption' => 'tls',
                'from_email' => 'marketing@empresa.com',
                'from_name' => 'Empresa - Marketing',
                'is_active' => true,
                'hourly_limit' => 100,
            ],
        ];

        foreach ($accounts as $accountData) {
            SmtpAccount::create($accountData);
        }

        $this->command->info('3 contas SMTP criadas com sucesso!');
        $this->command->warn('ATENÇÃO: Atualize as senhas das contas SMTP antes de usar em produção!');
    }
}
