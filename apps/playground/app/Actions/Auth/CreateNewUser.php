<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * @param  array<string, mixed>  $input
     */
    public function create(array $input): User
    {
        if (is_string($input['email'] ?? null)) {
            $input['email'] = Str::lower(trim($input['email']));
        }

        if (is_string($input['name'] ?? null)) {
            $input['name'] = trim($input['name']);
        }

        /** @var array{name: string, email: string, password: string} $validated */
        $validated = Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'password' => $this->passwordRules(),
        ])->validate();

        try {
            return User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
            ]);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'email' => __('validation.unique', ['attribute' => 'email']),
            ]);
        }
    }
}
