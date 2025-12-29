<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Ejecutar el comando de notificaciones de recordatorios todos los días a las 8:00 AM
        $schedule->command('reminders:send')->dailyAt('08:00');
        
        // También ejecutarlo cada hora para capturar recordatorios en cualquier momento
        $schedule->command('reminders:send')->hourly();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
