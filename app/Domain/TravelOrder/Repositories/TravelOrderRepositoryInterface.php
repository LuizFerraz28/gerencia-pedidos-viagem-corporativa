<?php

namespace App\Domain\TravelOrder\Repositories;

use App\Domain\TravelOrder\Entities\TravelOrder;
use App\Domain\TravelOrder\ValueObjects\TravelOrderStatus;

interface TravelOrderRepositoryInterface
{
    public function findById(int $id): ?TravelOrder;

    public function findByIdForUser(int $id, int $userId): ?TravelOrder;

    /** @return TravelOrder[] */
    public function findAll(array $filters = []): array;

    /** @return TravelOrder[] */
    public function findAllForUser(int $userId, array $filters = []): array;

    public function save(TravelOrder $order): TravelOrder;

    public function update(TravelOrder $order): void;
}
