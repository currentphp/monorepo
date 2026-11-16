<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function update(User $actor, User $user): bool
    {
        return $actor->exists && $user->exists && $actor->is($user) && $actor->hasVerifiedEmail();
    }
}
