<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function view(User $auth, User $target): bool
    {
        if ($target->hasRole('Super Admin')) {
            return false;
        }
        return (int) $auth->tenant_id === (int) $target->tenant_id;
    }

    public function update(User $auth, User $target): bool
    {
        if ($target->hasRole('Super Admin')) {
            return false;
        }
        return (int) $auth->tenant_id === (int) $target->tenant_id;
    }

    public function delete(User $auth, User $target): bool
    {
        if ($target->hasRole('Super Admin')) {
            return false;
        }
        if ($auth->id === $target->id) {
            return false;
        }
        return (int) $auth->tenant_id === (int) $target->tenant_id;
    }
}
