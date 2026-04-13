<?php

namespace App\Domain\TravelOrder\Entities;

use App\Domain\TravelOrder\Events\TravelOrderApproved;
use App\Domain\TravelOrder\Events\TravelOrderCancelled;
use App\Domain\TravelOrder\Exceptions\TravelOrderException;
use App\Domain\TravelOrder\ValueObjects\DateRange;
use App\Domain\TravelOrder\ValueObjects\TravelOrderStatus;

class TravelOrder
{
    private array $domainEvents = [];

    public function __construct(
        private readonly int $id,
        private readonly int $userId,
        private readonly string $requesterName,
        private string $destination,
        private DateRange $dateRange,
        private TravelOrderStatus $status,
    ) {}

    // --- Getters ---

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getRequesterName(): string
    {
        return $this->requesterName;
    }

    public function getDestination(): string
    {
        return $this->destination;
    }

    public function getDateRange(): DateRange
    {
        return $this->dateRange;
    }

    public function getStatus(): TravelOrderStatus
    {
        return $this->status;
    }

    // --- Business rules ---

    public function approve(int $actingUserId): void
    {
        if ($actingUserId === $this->userId) {
            throw TravelOrderException::ownerCannotChangeStatus();
        }

        if (!$this->status->canTransitionTo(TravelOrderStatus::Approved)) {
            throw TravelOrderException::invalidStatusTransition(
                $this->status,
                TravelOrderStatus::Approved
            );
        }

        $this->status = TravelOrderStatus::Approved;
        $this->domainEvents[] = new TravelOrderApproved($this->id, $this->userId);
    }

    public function cancel(int $actingUserId, bool $isAdmin): void
    {
        if (!$isAdmin && $actingUserId === $this->userId) {
            // Regular users can only cancel if status is still "requested"
            if ($this->status !== TravelOrderStatus::Requested) {
                throw TravelOrderException::cannotCancelAfterApproval();
            }
        }

        if ($isAdmin && $actingUserId === $this->userId) {
            throw TravelOrderException::ownerCannotChangeStatus();
        }

        if (!$this->status->canTransitionTo(TravelOrderStatus::Cancelled)) {
            throw TravelOrderException::invalidStatusTransition(
                $this->status,
                TravelOrderStatus::Cancelled
            );
        }

        $this->status = TravelOrderStatus::Cancelled;
        $this->domainEvents[] = new TravelOrderCancelled($this->id, $this->userId);
    }

    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }
}
