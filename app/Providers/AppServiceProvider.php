<?php

namespace App\Providers;

use App\Domain\TravelOrder\Repositories\TravelOrderRepositoryInterface;
use App\Infrastructure\Persistence\Repositories\EloquentTravelOrderRepository;
use App\Models\User;
use App\Policies\TravelOrderPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            TravelOrderRepositoryInterface::class,
            EloquentTravelOrderRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, TravelOrderPolicy::class);

        // Convenience gate so controllers can call Gate::authorize('update-status')
        Gate::define('update-travel-order-status', fn (User $user) => $user->is_admin);
    }
}
