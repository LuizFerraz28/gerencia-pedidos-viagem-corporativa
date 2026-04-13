<?php

namespace App\Application\TravelOrder\UseCases;

use App\Application\TravelOrder\DTOs\CreateTravelOrderDTO;
use App\Domain\TravelOrder\Entities\TravelOrder;
use App\Domain\TravelOrder\Repositories\TravelOrderRepositoryInterface;
use App\Domain\TravelOrder\ValueObjects\DateRange;
use App\Domain\TravelOrder\ValueObjects\TravelOrderStatus;
use DateTimeImmutable;

class CreateTravelOrderUseCase
{
    public function __construct(
        private readonly TravelOrderRepositoryInterface $repository,
    ) {}

    public function execute(CreateTravelOrderDTO $dto): TravelOrder
    {
        $dateRange = new DateRange(
            departureDate: new DateTimeImmutable($dto->departureDate),
            returnDate: new DateTimeImmutable($dto->returnDate),
        );

        $order = new TravelOrder(
            id: 0,
            userId: $dto->userId,
            requesterName: $dto->requesterName,
            destination: $dto->destination,
            dateRange: $dateRange,
            status: TravelOrderStatus::Requested,
        );

        return $this->repository->save($order);
    }
}
