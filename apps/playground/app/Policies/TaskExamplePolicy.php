<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\TaskExample;
use Illuminate\Auth\Access\Response;

/** Who may change demo tasks. Routes enforce it; actions only describe it. */
final class TaskExamplePolicy
{
    public function update(User $user, TaskExample $task): bool
    {
        return true;
    }

    public function archive(User $user, TaskExample $task): Response
    {
        return $user->hasVerifiedEmail()
            ? Response::allow()
            : Response::deny('Verify your email address to archive tasks.');
    }
}
