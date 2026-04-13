<?php

namespace App\Http\Resources;

use App\Domain\TravelOrder\Entities\TravelOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read TravelOrder $resource
 */
class TravelOrderResource extends JsonResource
{
    /**
     * Wrap domain entity so Laravel Resource conventions are respected
     * while keeping the domain object decoupled from the HTTP layer.
     */
    public function __construct(TravelOrder $resource)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        /** @var TravelOrder $order */
        $order = $this->resource;

        return [
            'id'             => $order->getId(),
            'user_id'        => $order->getUserId(),
            'requester_name' => $order->getRequesterName(),
            'destination'    => $order->getDestination(),
            'departure_date' => $order->getDateRange()->departureDate->format('Y-m-d'),
            'return_date'    => $order->getDateRange()->returnDate->format('Y-m-d'),
            'status'         => $order->getStatus()->value,
            'status_label'   => $order->getStatus()->label(),
        ];
    }

    /**
     * Customize the outgoing response envelope — removes the default "data" key
     * for single resources so the API stays flat and consistent.
     */
    public function withResponse(Request $request, \Illuminate\Http\JsonResponse $response): void
    {
        $response->header('X-Resource-Type', 'TravelOrder');
    }
}
