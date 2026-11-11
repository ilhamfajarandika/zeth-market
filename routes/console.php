<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('accounts:release-expired')->everyMinute()->withoutOverlapping();
