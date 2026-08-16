<?php

use Illuminate\Support\Facades\Schedule;

// Precisa de `php artisan schedule:work` (ou cron real) rodando pra disparar
// de fato -- ver PLANO.md, semana 7 ("e-mail de deadline dispara sozinho").
Schedule::command('hackathon:send-deadline-reminders')->everyFiveMinutes();
