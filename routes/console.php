<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('melo:check-balance')->everyTenMinutes()->withoutOverlapping();
