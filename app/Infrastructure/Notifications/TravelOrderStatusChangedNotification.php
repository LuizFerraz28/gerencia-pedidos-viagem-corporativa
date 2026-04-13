<?php

namespace App\Infrastructure\Notifications;

use App\Domain\TravelOrder\Entities\TravelOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TravelOrderStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly TravelOrder $order,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $status = $this->order->getStatus()->label();

        return (new MailMessage())
            ->subject("Pedido de Viagem #{$this->order->getId()} — {$status}")
            ->greeting("Olá, {$notifiable->name}!")
            ->line("Seu pedido de viagem para **{$this->order->getDestination()}** foi **{$status}**.")
            ->line("Data de ida: {$this->order->getDateRange()->departureDate->format('d/m/Y')}")
            ->line("Data de volta: {$this->order->getDateRange()->returnDate->format('d/m/Y')}")
            ->action('Ver pedido', url("/api/travel-orders/{$this->order->getId()}"))
            ->line('Obrigado por usar nosso sistema de gerenciamento de pedidos!');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'travel_order_id' => $this->order->getId(),
            'destination'     => $this->order->getDestination(),
            'status'          => $this->order->getStatus()->value,
            'message'         => "Pedido #{$this->order->getId()} para {$this->order->getDestination()} foi {$this->order->getStatus()->label()}.",
        ];
    }
}
