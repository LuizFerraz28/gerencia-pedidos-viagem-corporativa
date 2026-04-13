<?php

namespace App\Domain\TravelOrder\ValueObjects;

use InvalidArgumentException;

final class DateRange
{
    public function __construct(
        public readonly \DateTimeImmutable $departureDate,
        public readonly \DateTimeImmutable $returnDate,
    ) {
        if ($returnDate <= $departureDate) {
            throw new InvalidArgumentException(
                'A data de volta deve ser posterior à data de ida.'
            );
        }
    }

    public function overlaps(self $other): bool
    {
        return $this->departureDate <= $other->returnDate
            && $this->returnDate >= $other->departureDate;
    }

    public function containsDate(\DateTimeImmutable $date): bool
    {
        return $date >= $this->departureDate && $date <= $this->returnDate;
    }
}
