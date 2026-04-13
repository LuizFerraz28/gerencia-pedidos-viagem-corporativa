<?php

namespace App\Application\TravelOrder\DTOs;

final class ListTravelOrdersDTO
{
    public function __construct(
        public readonly ?string $status = null,
        public readonly ?string $destination = null,
        public readonly ?string $departureFrom = null,
        public readonly ?string $departureUntil = null,
        public readonly ?string $createdFrom = null,
        public readonly ?string $createdUntil = null,
    ) {}

    /**
     * Build from a validated request array (from FormRequest::validated()).
     */
    public static function fromRequest(array $validated): self
    {
        return new self(
            status: $validated['status'] ?? null,
            destination: $validated['destination'] ?? null,
            departureFrom: $validated['departure_from'] ?? null,
            departureUntil: $validated['departure_until'] ?? null,
            createdFrom: $validated['created_from'] ?? null,
            createdUntil: $validated['created_until'] ?? null,
        );
    }

    public function toFilters(): array
    {
        return array_filter([
            'status'          => $this->status,
            'destination'     => $this->destination,
            'departure_from'  => $this->departureFrom,
            'departure_until' => $this->departureUntil,
            'created_from'    => $this->createdFrom,
            'created_until'   => $this->createdUntil,
        ]);
    }
}
