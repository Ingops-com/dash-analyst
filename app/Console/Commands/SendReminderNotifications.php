<?php

namespace App\Console\Commands;

use App\Models\Reminder;
use App\Notifications\ReminderNotification;
use Illuminate\Console\Command;
use Carbon\Carbon;

class SendReminderNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía notificaciones para recordatorios pendientes del día actual';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();

        // Obtener recordatorios pendientes que vencen ahora o antes y no han sido notificados
        $reminders = Reminder::where('status', 'pending')
            ->where('notified', false)
            ->where('reminder_date', '<=', $now)
            ->with('user')
            ->get();

        $count = 0;
        foreach ($reminders as $reminder) {
            if (!$reminder->user) {
                continue; // seguridad: sin usuario no notificamos
            }

            // Enviar notificación al usuario
            $reminder->user->notify(new ReminderNotification($reminder));

            // Marcar como notificado
            $reminder->update(['notified' => true]);
            $count++;
        }

        $this->info("Se enviaron $count notificaciones de recordatorios.");
        return 0;
    }
}

