<?php

namespace Tests\Unit\Domain;

use App\Domain\TravelOrder\Entities\TravelOrder;
use App\Domain\TravelOrder\Exceptions\TravelOrderException;
use App\Domain\TravelOrder\ValueObjects\DateRange;
use App\Domain\TravelOrder\ValueObjects\TravelOrderStatus;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class TravelOrderTest extends TestCase
{
    private function makeOrder(
        int $id = 1,
        int $userId = 10,
        TravelOrderStatus $status = TravelOrderStatus::Requested,
    ): TravelOrder {
        return new TravelOrder(
            id: $id,
            userId: $userId,
            requesterName: 'João Silva',
            destination: 'São Paulo',
            dateRange: new DateRange(
                departureDate: new DateTimeImmutable('2026-05-01'),
                returnDate: new DateTimeImmutable('2026-05-10'),
            ),
            status: $status,
        );
    }

    // --- TravelOrderStatus ---

    public function test_status_can_transition_from_requested_to_approved(): void
    {
        $this->assertTrue(
            TravelOrderStatus::Requested->canTransitionTo(TravelOrderStatus::Approved)
        );
    }

    public function test_status_can_transition_from_requested_to_cancelled(): void
    {
        $this->assertTrue(
            TravelOrderStatus::Requested->canTransitionTo(TravelOrderStatus::Cancelled)
        );
    }

    public function test_status_can_transition_from_approved_to_cancelled(): void
    {
        $this->assertTrue(
            TravelOrderStatus::Approved->canTransitionTo(TravelOrderStatus::Cancelled)
        );
    }

    public function test_status_cannot_transition_from_cancelled(): void
    {
        $this->assertFalse(
            TravelOrderStatus::Cancelled->canTransitionTo(TravelOrderStatus::Approved)
        );
        $this->assertFalse(
            TravelOrderStatus::Cancelled->canTransitionTo(TravelOrderStatus::Requested)
        );
    }

    public function test_approved_cannot_go_back_to_requested(): void
    {
        $this->assertFalse(
            TravelOrderStatus::Approved->canTransitionTo(TravelOrderStatus::Requested)
        );
    }

    // --- DateRange ---

    public function test_date_range_throws_when_return_before_departure(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new DateRange(
            departureDate: new DateTimeImmutable('2026-05-10'),
            returnDate: new DateTimeImmutable('2026-05-01'),
        );
    }

    public function test_date_range_throws_when_dates_are_equal(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new DateRange(
            departureDate: new DateTimeImmutable('2026-05-01'),
            returnDate: new DateTimeImmutable('2026-05-01'),
        );
    }

    // --- Approve ---

    public function test_admin_can_approve_requested_order(): void
    {
        $order = $this->makeOrder();
        $order->approve(actingUserId: 99); // different from userId=10

        $this->assertEquals(TravelOrderStatus::Approved, $order->getStatus());
    }

    public function test_owner_cannot_approve_own_order(): void
    {
        $this->expectException(TravelOrderException::class);

        $order = $this->makeOrder(userId: 10);
        $order->approve(actingUserId: 10);
    }

    public function test_cannot_approve_already_approved_order(): void
    {
        $this->expectException(TravelOrderException::class);

        $order = $this->makeOrder(status: TravelOrderStatus::Approved);
        $order->approve(actingUserId: 99);
    }

    public function test_cannot_approve_cancelled_order(): void
    {
        $this->expectException(TravelOrderException::class);

        $order = $this->makeOrder(status: TravelOrderStatus::Cancelled);
        $order->approve(actingUserId: 99);
    }

    // --- Cancel ---

    public function test_admin_can_cancel_approved_order(): void
    {
        $order = $this->makeOrder(status: TravelOrderStatus::Approved);
        $order->cancel(actingUserId: 99, isAdmin: true);

        $this->assertEquals(TravelOrderStatus::Cancelled, $order->getStatus());
    }

    public function test_admin_owner_cannot_cancel_own_order(): void
    {
        $this->expectException(TravelOrderException::class);

        $order = $this->makeOrder(userId: 10, status: TravelOrderStatus::Approved);
        $order->cancel(actingUserId: 10, isAdmin: true);
    }

    public function test_regular_user_can_cancel_own_requested_order(): void
    {
        $order = $this->makeOrder(userId: 10, status: TravelOrderStatus::Requested);
        $order->cancel(actingUserId: 10, isAdmin: false);

        $this->assertEquals(TravelOrderStatus::Cancelled, $order->getStatus());
    }

    public function test_regular_user_cannot_cancel_own_approved_order(): void
    {
        $this->expectException(TravelOrderException::class);

        $order = $this->makeOrder(userId: 10, status: TravelOrderStatus::Approved);
        $order->cancel(actingUserId: 10, isAdmin: false);
    }

    public function test_cannot_cancel_already_cancelled_order(): void
    {
        $this->expectException(TravelOrderException::class);

        $order = $this->makeOrder(status: TravelOrderStatus::Cancelled);
        $order->cancel(actingUserId: 99, isAdmin: true);
    }

    // --- Domain events ---

    public function test_approve_dispatches_domain_event(): void
    {
        $order = $this->makeOrder();
        $order->approve(actingUserId: 99);
        $events = $order->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(\App\Domain\TravelOrder\Events\TravelOrderApproved::class, $events[0]);
        $this->assertEquals($order->getId(), $events[0]->travelOrderId);
    }

    public function test_cancel_dispatches_domain_event(): void
    {
        $order = $this->makeOrder();
        $order->cancel(actingUserId: 99, isAdmin: true);
        $events = $order->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(\App\Domain\TravelOrder\Events\TravelOrderCancelled::class, $events[0]);
    }

    public function test_pull_domain_events_clears_events(): void
    {
        $order = $this->makeOrder();
        $order->approve(actingUserId: 99);
        $order->pullDomainEvents(); // first pull
        $events = $order->pullDomainEvents(); // second pull

        $this->assertEmpty($events);
    }
}
