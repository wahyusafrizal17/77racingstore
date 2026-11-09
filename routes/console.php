<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Status PO dari warehouse Modifikasi Ori (butuh cron: * * * * * php artisan schedule:run).
Schedule::command('mo-po:sync --resend')->everyFiveMinutes()->withoutOverlapping();
