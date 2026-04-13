<?php

namespace App\Application\TravelOrder\DTOs;

use App\Domain\TravelOrder\ValueObjects\TravelOrderStatus;

final class CreateTravelOrderDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly string $requesterName,
        public readonly string $destination,
        public readonly string $departureDate,
        public readonly string $returnDate,
    ) {}
}
