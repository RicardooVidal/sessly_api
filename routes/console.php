<?php

use App\Console\Commands\CreateAppointmentsCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command(CreateAppointmentsCommand::class)->weeklyOn(0, '00:00');
