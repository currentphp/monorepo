<?php

namespace App\Actions\Profile;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class UpdateDisplayName
{
    public function handle(User $actor, User $target, string $name): User
    {
        Gate::forUser($actor)->authorize('update', $target);

        /** @var array{name: string} $validated */
        $validated = Validator::make(['name' => trim($name)], [
            'name' => ['required', 'string', 'max:255'],
        ])->validate();

        $target->fill($validated)->save();

        return $target;
    }
}
