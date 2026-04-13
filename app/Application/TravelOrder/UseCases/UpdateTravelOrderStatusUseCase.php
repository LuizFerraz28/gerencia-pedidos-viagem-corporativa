<?php

namespace App\Application\TravelOrder\UseCases;

use App\Domain\TravelOrder\Entities\TravelOrder;
use App\Domain\TravelOrder\Exceptions\TravelOrderException;
use App\Domain\TravelOrder\Repositories\TravelOrderRepositoryInterface;
use App\Domain\TravelOrder\ValueObjects\TravelOrderStatus;
use App\Infrastructure\Notifications\TravelOrderStatusChangedNotification;
use App\Models\User;

class UpdateTravelOrderStatusUseCase
{
    public function __construct(
        private readonly TravelOrderRepositoryInterface $repository,
    ) {}

    public function execute(int $orderId, string $newStatus, int $actingUserId, bool $isAdmin): TravelOrder
    {
        $order = $this->repository->findById($orderId);

        if ($order === null) {
            throw TravelOrderException::notFound($orderId);
        }

        $status = TravelOrderStatus::from($newStatus);

        match ($status) {
            TravelOrderStatus::Approved  => $order->approve($actingUserId),
            TravelOrderStatus::Cancelled => $order->cancel($actingUserId, $isAdmin),
            default => throw new \InvalidArgumentException("Status inválido: {$newStatus}"),
        };

        $this->repository->update($order);

        // Pull domain events and dispatch a notification for each one.
        // We load the User model here (Infrastructure concern) to decouple
        // the domain entity from the Notifiable contract.
        $events = $order->pullDomainEvents();

        if (!empty($events) && $requester = User::find($order->getUserId())) {
            $requester->notify(new TravelOrderStatusChangedNotification($order));
        }

        return $order;
    }
}
