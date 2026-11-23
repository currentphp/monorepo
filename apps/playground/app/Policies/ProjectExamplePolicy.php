<?php

declare(strict_types=1);

namespace App\Policies;

use App\ProjectExample;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Authenticatable;

final class ProjectExamplePolicy
{
    public function view(?Authenticatable $user, ProjectExample $project): bool
    {
        return $project->is_visible;
    }

    public function rename(?Authenticatable $user, ProjectExample $project): Response
    {
        return $user !== null && $project->is_visible && $project->is_editable
            ? Response::allow() : Response::deny('This project is read-only.');
    }
}
