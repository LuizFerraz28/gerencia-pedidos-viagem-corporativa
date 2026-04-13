<?php

namespace App\Application\TravelOrder\UseCases;

use App\Application\TravelOrder\DTOs\ListTravelOrdersDTO;
use App\Domain\TravelOrder\Entities\TravelOrder;
use App\Domain\TravelOrder\Repositories\TravelOrderRepositoryInterface;

class ListTravelOrdersUseCase
{
    public function __construct(
        private readonly TravelOrderRepositoryInterface $repository,
    ) {}

    /** @return TravelOrder[] */
    public function execute(ListTravelOrdersDTO $dto, int $actingUserId, bool $isAdmin): array
    {
        $filters = $dto->toFilters();

        return $isAdmin
            ? $this->repository->findAll($filters)
            : $this->repository->findAllForUser($actingUserId, $filters);
    }
}
