<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule the daily salesman recap report
Schedule::command('distora:daily-recap')->dailyAt('17:00');
Schedule::command('distora:ml-evaluate --limit=1000')->hourly();
Schedule::command('distora:aggregate-monthly-sales')->hourly();
