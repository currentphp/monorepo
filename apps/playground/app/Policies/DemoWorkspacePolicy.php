<?php

declare(strict_types=1);

namespace App\Policies;

use App\DemoWorkspace;
use App\OnboardingDemo;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Authenticatable;

final class DemoWorkspacePolicy
{
    public function finish(Authenticatable $user, DemoWorkspace $workspace): Response
    {
        $demo = app(OnboardingDemo::class);
        if ($demo->complete()) {
            return Response::deny('This simulation is already complete. Reset to try again.');
        }

        return $demo->profile() !== null && $demo->hasWorkspace()
            ? Response::allow() : Response::deny('Complete your demo profile and workspace first.');
    }
}
