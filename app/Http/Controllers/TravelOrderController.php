<?php

namespace App\Http\Controllers;

use App\Application\TravelOrder\DTOs\CreateTravelOrderDTO;
use App\Application\TravelOrder\DTOs\ListTravelOrdersDTO;
use App\Application\TravelOrder\UseCases\CreateTravelOrderUseCase;
use App\Application\TravelOrder\UseCases\GetTravelOrderUseCase;
use App\Application\TravelOrder\UseCases\ListTravelOrdersUseCase;
use App\Application\TravelOrder\UseCases\UpdateTravelOrderStatusUseCase;
use App\Domain\TravelOrder\Exceptions\TravelOrderException;
use App\Http\Requests\TravelOrder\CreateTravelOrderRequest;
use App\Http\Requests\TravelOrder\ListTravelOrdersRequest;
use App\Http\Requests\TravelOrder\UpdateTravelOrderStatusRequest;
use App\Http\Resources\TravelOrderResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Tymon\JWTAuth\Facades\JWTAuth;

class TravelOrderController extends Controller
{
    public function __construct(
        private readonly CreateTravelOrderUseCase       $createUseCase,
        private readonly GetTravelOrderUseCase          $getUseCase,
        private readonly ListTravelOrdersUseCase        $listUseCase,
        private readonly UpdateTravelOrderStatusUseCase $updateStatusUseCase,
    ) {}

    public function store(CreateTravelOrderRequest $request): JsonResponse
    {
        $user = JWTAuth::user();

        $order = $this->createUseCase->execute(new CreateTravelOrderDTO(
            userId: $user->id,
            requesterName: $user->name,
            destination: $request->validated('destination'),
            departureDate: $request->validated('departure_date'),
            returnDate: $request->validated('return_date'),
        ));

        return (new TravelOrderResource($order))
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $id): JsonResponse
    {
        $user  = JWTAuth::user();
        $order = $user->is_admin
            ? $this->getUseCase->executeAsAdmin($id)
            : $this->getUseCase->execute($id, $user->id);

        if ($order === null) {
            return response()->json(['message' => "Pedido de viagem #{$id} não encontrado."], 404);
        }

        return (new TravelOrderResource($order))->response();
    }

    public function index(ListTravelOrdersRequest $request)
    {
        $user   = JWTAuth::user();
        $orders = $this->listUseCase->execute(
            ListTravelOrdersDTO::fromRequest($request->validated()),
            $user->id,
            $user->is_admin,
        );

        // Return the ResourceCollection directly so Laravel serialises it
        // with the standard {"data": [...]} envelope automatically.
        return TravelOrderResource::collection(collect($orders));
    }

    public function updateStatus(UpdateTravelOrderStatusRequest $request, int $id): JsonResponse
    {
        Gate::authorize('update-travel-order-status');

        try {
            $order = $this->updateStatusUseCase->execute(
                orderId: $id,
                newStatus: $request->validated('status'),
                actingUserId: JWTAuth::user()->id,
                isAdmin: JWTAuth::user()->is_admin,
            );
        } catch (TravelOrderException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return (new TravelOrderResource($order))->response();
    }
}
