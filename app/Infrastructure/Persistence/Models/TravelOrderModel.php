<?php

namespace App\Infrastructure\Persistence\Models;

use App\Domain\TravelOrder\ValueObjects\TravelOrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TravelOrderModel extends Model
{
    protected $table = 'travel_orders';

    protected $fillable = [
        'user_id',
        'requester_name',
        'destination',
        'departure_date',
        'return_date',
        'status',
    ];

    protected $casts = [
        'departure_date' => 'date:Y-m-d',
        'return_date'    => 'date:Y-m-d',
        'status'         => TravelOrderStatus::class,
    ];

    // --- Relationships ---

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    // --- Query Scopes ---

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeWithStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeWithDestination(Builder $query, string $destination): Builder
    {
        return $query->where('destination', 'like', "%{$destination}%");
    }

    public function scopeDepartingFrom(Builder $query, string $date): Builder
    {
        return $query->where('departure_date', '>=', $date);
    }

    public function scopeDepartingUntil(Builder $query, string $date): Builder
    {
        return $query->where('departure_date', '<=', $date);
    }

    public function scopeCreatedFrom(Builder $query, string $date): Builder
    {
        return $query->whereDate('created_at', '>=', $date);
    }

    public function scopeCreatedUntil(Builder $query, string $date): Builder
    {
        return $query->whereDate('created_at', '<=', $date);
    }
}
