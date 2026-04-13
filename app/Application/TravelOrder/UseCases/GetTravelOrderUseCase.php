<?php

namespace App\Application\TravelOrder\UseCases;

use App\Domain\TravelOrder\Entities\TravelOrder;
use App\Domain\TravelOrder\Repositories\TravelOrderRepositoryInterface;

class GetTravelOrderUseCase
{
    public function __construct(
        private readonly TravelOrderRepositoryInterface $repository,
    ) {}

    /**
     * Fetch an order that belongs to the given user (non-admin path).
     */
    public function execute(int $id, int $userId): ?TravelOrder
    {
        return $this->repository->findByIdForUser($id, $userId);
    }

    /**
     * Fetch any order regardless of ownership (admin path).
     */
    public function executeAsAdmin(int $id): ?TravelOrder
    {
        return $this->repository->findById($id);
    }
}
