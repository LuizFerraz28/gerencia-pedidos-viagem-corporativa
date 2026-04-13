<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\TravelOrder\Entities\TravelOrder;
use App\Domain\TravelOrder\Repositories\TravelOrderRepositoryInterface;
use App\Domain\TravelOrder\ValueObjects\DateRange;
use App\Domain\TravelOrder\ValueObjects\TravelOrderStatus;
use App\Infrastructure\Persistence\Models\TravelOrderModel;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;

class EloquentTravelOrderRepository implements TravelOrderRepositoryInterface
{
    public function findById(int $id): ?TravelOrder
    {
        $model = TravelOrderModel::find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function findByIdForUser(int $id, int $userId): ?TravelOrder
    {
        $model = TravelOrderModel::query()
            ->forUser($userId)
            ->where('id', $id)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    /** @return TravelOrder[] */
    public function findAll(array $filters = []): array
    {
        return $this->applyFilters(TravelOrderModel::query(), $filters)
            ->latest()
            ->get()
            ->map(fn ($m) => $this->toDomain($m))
            ->all();
    }

    /** @return TravelOrder[] */
    public function findAllForUser(int $userId, array $filters = []): array
    {
        return $this->applyFilters(TravelOrderModel::query()->forUser($userId), $filters)
            ->latest()
            ->get()
            ->map(fn ($m) => $this->toDomain($m))
            ->all();
    }

    public function save(TravelOrder $order): TravelOrder
    {
        $model = TravelOrderModel::create([
            'user_id'        => $order->getUserId(),
            'requester_name' => $order->getRequesterName(),
            'destination'    => $order->getDestination(),
            'departure_date' => $order->getDateRange()->departureDate->format('Y-m-d'),
            'return_date'    => $order->getDateRange()->returnDate->format('Y-m-d'),
            'status'         => $order->getStatus()->value,
        ]);

        return $this->toDomain($model);
    }

    public function update(TravelOrder $order): void
    {
        TravelOrderModel::where('id', $order->getId())->update([
            'status' => $order->getStatus()->value,
        ]);
    }

    // --- Private helpers ---

    private function applyFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when(
                filled($filters['status'] ?? null),
                fn ($q) => $q->withStatus($filters['status'])
            )
            ->when(
                filled($filters['destination'] ?? null),
                fn ($q) => $q->withDestination($filters['destination'])
            )
            ->when(
                filled($filters['departure_from'] ?? null),
                fn ($q) => $q->departingFrom($filters['departure_from'])
            )
            ->when(
                filled($filters['departure_until'] ?? null),
                fn ($q) => $q->departingUntil($filters['departure_until'])
            )
            ->when(
                filled($filters['created_from'] ?? null),
                fn ($q) => $q->createdFrom($filters['created_from'])
            )
            ->when(
                filled($filters['created_until'] ?? null),
                fn ($q) => $q->createdUntil($filters['created_until'])
            );
    }

    private function toDomain(TravelOrderModel $model): TravelOrder
    {
        return new TravelOrder(
            id: $model->id,
            userId: $model->user_id,
            requesterName: $model->requester_name,
            destination: $model->destination,
            dateRange: new DateRange(
                departureDate: new DateTimeImmutable($model->departure_date->format('Y-m-d')),
                returnDate: new DateTimeImmutable($model->return_date->format('Y-m-d')),
            ),
            status: $model->status,
        );
    }
}
