<?php

declare(strict_types=1);

namespace App\Policies;

use App\TaskExample;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Authenticatable;

/** Who may change demo tasks. Routes enforce it; actions only describe it. */
final class TaskExamplePolicy
{
    public function update(Authenticatable $user, TaskExample $task): bool
    {
        return true;
    }

    public function archive(Authenticatable $user, TaskExample $task): Response
    {
        return $task->id !== 2
            ? Response::allow()
            : Response::deny('This demo task is protected from archiving.');
    }
}
