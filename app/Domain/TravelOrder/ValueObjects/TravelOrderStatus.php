<?php

namespace App\Domain\TravelOrder\ValueObjects;

enum TravelOrderStatus: string
{
    case Requested = 'solicitado';
    case Approved  = 'aprovado';
    case Cancelled = 'cancelado';

    public function canTransitionTo(self $new): bool
    {
        return match ($this) {
            self::Requested => in_array($new, [self::Approved, self::Cancelled], true),
            self::Approved  => $new === self::Cancelled,
            self::Cancelled => false,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Solicitado',
            self::Approved  => 'Aprovado',
            self::Cancelled => 'Cancelado',
        };
    }
}
