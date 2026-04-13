<?php

namespace App\Policies;

use App\Models\User;

class TravelOrderPolicy
{
    /**
     * Only admins can change the status of any order.
     */
    public function updateStatus(User $user): bool
    {
        return $user->is_admin;
    }

    /**
     * Admins see all orders; regular users only see their own.
     * (Used in controller via $user->cannot — returns true to allow access.)
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users may list — scoping done in use case.
    }

    /**
     * Admin can view any order; owner can view their own.
     * The use case already handles scoping, so the policy just prevents
     * explicit cross-user access at the HTTP layer.
     */
    public function view(User $user): bool
    {
        return true; // Use case enforces row-level ownership.
    }
}
