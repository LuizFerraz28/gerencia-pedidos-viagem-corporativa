<?php

namespace App\Domain\TravelOrder\Events;

final class TravelOrderCancelled
{
    public function __construct(
        public readonly int $travelOrderId,
        public readonly int $userId,
    ) {}
}
