<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResultsPublished extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Event $event)
    {
        //
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Resultado do {$this->event->name} já está no ar")
            ->greeting('Olá!')
            ->line("A organização publicou o resultado do {$this->event->name}.")
            ->action('Ver resultado', route('resultados.show'))
            ->line('Obrigado por participar!');
    }
}
