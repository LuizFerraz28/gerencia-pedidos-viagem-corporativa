<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class TravelOrderTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(bool $isAdmin = false): User
    {
        return User::factory()->create(['is_admin' => $isAdmin]);
    }

    private function token(User $user): string
    {
        return JWTAuth::fromUser($user);
    }

    private function travelOrderPayload(array $overrides = []): array
    {
        return array_merge([
            'destination'    => 'Rio de Janeiro',
            'departure_date' => '2026-06-01',
            'return_date'    => '2026-06-10',
        ], $overrides);
    }

    /** Helper: create an order and return its decoded JSON body. */
    private function createOrder(User $user, array $overrides = []): array
    {
        return $this->withToken($this->token($user))
                    ->postJson('/api/travel-orders', $this->travelOrderPayload($overrides))
                    ->json('data');
    }

    // --- Create ---

    public function test_authenticated_user_can_create_travel_order(): void
    {
        $user     = $this->makeUser();
        $response = $this->withToken($this->token($user))
                         ->postJson('/api/travel-orders', $this->travelOrderPayload());

        $response->assertStatus(201)
                 ->assertJsonPath('data.destination', 'Rio de Janeiro')
                 ->assertJsonPath('data.status', 'solicitado');
    }

    public function test_create_travel_order_fails_with_invalid_dates(): void
    {
        $response = $this->withToken($this->token($this->makeUser()))
                         ->postJson('/api/travel-orders', $this->travelOrderPayload([
                             'departure_date' => '2026-06-10',
                             'return_date'    => '2026-06-01',
                         ]));

        $response->assertStatus(422)->assertJsonStructure(['message', 'errors']);
    }

    public function test_create_requires_authentication(): void
    {
        $this->postJson('/api/travel-orders', $this->travelOrderPayload())
             ->assertStatus(401);
    }

    // --- Show ---

    public function test_user_can_view_own_travel_order(): void
    {
        $user  = $this->makeUser();
        $order = $this->createOrder($user);

        $this->withToken($this->token($user))
             ->getJson("/api/travel-orders/{$order['id']}")
             ->assertOk()
             ->assertJsonPath('data.id', $order['id']);
    }

    public function test_user_cannot_view_another_users_order(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $order = $this->createOrder($owner);

        $this->withToken($this->token($other))
             ->getJson("/api/travel-orders/{$order['id']}")
             ->assertStatus(404);
    }

    public function test_admin_can_view_any_order(): void
    {
        $user  = $this->makeUser();
        $admin = $this->makeUser(isAdmin: true);
        $order = $this->createOrder($user);

        $this->withToken($this->token($admin))
             ->getJson("/api/travel-orders/{$order['id']}")
             ->assertOk()
             ->assertJsonPath('data.id', $order['id']);
    }

    // --- List ---

    public function test_user_can_only_list_own_orders(): void
    {
        $user1 = $this->makeUser();
        $user2 = $this->makeUser();

        $this->createOrder($user1);
        $this->createOrder($user2, ['destination' => 'Curitiba']);

        $data = $this->withToken($this->token($user1))
                     ->getJson('/api/travel-orders')
                     ->assertOk()
                     ->json('data');

        $this->assertCount(1, $data);
        $this->assertEquals($user1->id, $data[0]['user_id']);
    }

    public function test_admin_can_list_all_orders(): void
    {
        $this->createOrder($this->makeUser());
        $this->createOrder($this->makeUser(), ['destination' => 'Fortaleza']);

        $data = $this->withToken($this->token($this->makeUser(isAdmin: true)))
                     ->getJson('/api/travel-orders')
                     ->assertOk()
                     ->json('data');

        $this->assertCount(2, $data);
    }

    public function test_list_can_filter_by_status(): void
    {
        $user  = $this->makeUser();
        $admin = $this->makeUser(isAdmin: true);
        $order = $this->createOrder($user);

        $this->withToken($this->token($admin))
             ->patchJson("/api/travel-orders/{$order['id']}/status", ['status' => 'aprovado']);

        $data = $this->withToken($this->token($user))
                     ->getJson('/api/travel-orders?status=aprovado')
                     ->assertOk()
                     ->json('data');

        $this->assertCount(1, $data);
    }

    public function test_list_can_filter_by_destination(): void
    {
        $user = $this->makeUser();

        $this->createOrder($user, ['destination' => 'Manaus']);
        $this->createOrder($user, ['destination' => 'Belém']);

        $data = $this->withToken($this->token($user))
                     ->getJson('/api/travel-orders?destination=Manaus')
                     ->assertOk()
                     ->json('data');

        $this->assertCount(1, $data);
    }

    // --- Update Status ---

    public function test_admin_can_approve_order(): void
    {
        $user  = $this->makeUser();
        $admin = $this->makeUser(isAdmin: true);
        $order = $this->createOrder($user);

        $this->withToken($this->token($admin))
             ->patchJson("/api/travel-orders/{$order['id']}/status", ['status' => 'aprovado'])
             ->assertOk()
             ->assertJsonPath('data.status', 'aprovado');
    }

    public function test_regular_user_cannot_approve_order(): void
    {
        $user  = $this->makeUser();
        $order = $this->createOrder($user);

        $this->withToken($this->token($user))
             ->patchJson("/api/travel-orders/{$order['id']}/status", ['status' => 'aprovado'])
             ->assertStatus(403);
    }

    public function test_admin_can_cancel_approved_order(): void
    {
        $user  = $this->makeUser();
        $admin = $this->makeUser(isAdmin: true);
        $order = $this->createOrder($user);

        $this->withToken($this->token($admin))
             ->patchJson("/api/travel-orders/{$order['id']}/status", ['status' => 'aprovado']);

        $this->withToken($this->token($admin))
             ->patchJson("/api/travel-orders/{$order['id']}/status", ['status' => 'cancelado'])
             ->assertOk()
             ->assertJsonPath('data.status', 'cancelado');
    }

    public function test_cannot_approve_already_cancelled_order(): void
    {
        $user  = $this->makeUser();
        $admin = $this->makeUser(isAdmin: true);
        $order = $this->createOrder($user);

        $this->withToken($this->token($admin))
             ->patchJson("/api/travel-orders/{$order['id']}/status", ['status' => 'cancelado']);

        $this->withToken($this->token($admin))
             ->patchJson("/api/travel-orders/{$order['id']}/status", ['status' => 'aprovado'])
             ->assertStatus(422);
    }

    public function test_update_status_with_invalid_value_fails(): void
    {
        $user  = $this->makeUser();
        $admin = $this->makeUser(isAdmin: true);
        $order = $this->createOrder($user);

        $this->withToken($this->token($admin))
             ->patchJson("/api/travel-orders/{$order['id']}/status", ['status' => 'invalido'])
             ->assertStatus(422);
    }
}
