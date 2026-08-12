<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Precisa de `php artisan schedule:work` (ou cron real) rodando pra disparar
// de fato -- ver PLANO.md, semana 7 ("e-mail de deadline dispara sozinho").
Schedule::command('hackathon:send-deadline-reminders')->everyFiveMinutes();
