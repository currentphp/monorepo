<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    /**
     * The caller must authorize the reset through the password broker first.
     *
     * @param  User  $user
     * @param  array<string, mixed>  $input
     */
    public function reset($user, array $input): void
    {
        /** @var array{password: string} $validated */
        $validated = Validator::make($input, [
            'password' => $this->passwordRules(),
        ])->validate();

        $user->getConnection()->transaction(function () use ($user, $validated): void {
            $user->forceFill([
                'password' => $validated['password'],
                'remember_token' => Str::random(60),
            ])->save();

            if (config('session.driver') === 'database') {
                DB::connection(config('session.connection'))
                    ->table(config('session.table'))
                    ->where('user_id', $user->getAuthIdentifier())
                    ->delete();
            }
        });
    }
}
