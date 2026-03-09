<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Agendar processamento de emails pendentes a cada 5 minutos
Schedule::command('email:process-pending')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();
