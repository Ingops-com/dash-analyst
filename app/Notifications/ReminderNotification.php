<?php

namespace App\Notifications;

use App\Models\Reminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $reminder;

    /**
     * Create a new notification instance.
     */
    public function __construct(Reminder $reminder)
    {
        $this->reminder = $reminder;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Recordatorio: ' . $this->reminder->title)
            ->greeting('¡Hola ' . $notifiable->name . '!')
            ->line('Te recordamos que hoy tienes el siguiente recordatorio:')
            ->line('**' . $this->reminder->title . '**')
            ->line($this->reminder->description ?? '')
            ->line('Fecha: ' . $this->reminder->reminder_date->format('d/m/Y H:i'))
            ->action('Ver Recordatorio', route('reminders.show', $this->reminder))
            ->line('¡Gracias por usar nuestra plataforma!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'reminder_id' => $this->reminder->id,
            'title' => $this->reminder->title,
            'description' => $this->reminder->description,
            'reminder_date' => $this->reminder->reminder_date,
        ];
    }
}
