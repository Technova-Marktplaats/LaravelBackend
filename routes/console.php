<?php

use Illuminate\Support\Facades\Schedule;

// Schedule de command om elke 5 minuten te controleren op verlopen reservaties
Schedule::command('reservaties:check-verlopen')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();