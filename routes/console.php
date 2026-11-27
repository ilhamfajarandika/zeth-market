<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('melo:check-balance')->everyTenMinutes()->withoutOverlapping();

// Retensi cek nickname 24 jam (SECURITY_RULES §13): baris cache kedaluwarsa dihapus, tidak hanya saat dibaca ulang.
Schedule::command('melo:prune-nickname-cache')->hourly()->withoutOverlapping();
