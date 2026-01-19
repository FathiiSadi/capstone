<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Semester;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    Semester::where('status', 'Open')
        ->where('preferences_closed_at', '<=', now())
        ->update(['status' => 'closed']);
})->everyMinute();
