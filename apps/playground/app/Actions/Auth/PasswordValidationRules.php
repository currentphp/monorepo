<?php

namespace App\Actions\Auth;

use Closure;
use Illuminate\Validation\Rules\Password;

trait PasswordValidationRules
{
    /**
     * @return array<int, string|Password|Closure>
     */
    protected function passwordRules(): array
    {
        return [
            'required', 'string', 'confirmed', 'not_regex:/\x00/', Password::defaults(),
            function (string $attribute, mixed $value, Closure $fail): void {
                if (is_string($value) && strlen($value) > 72) {
                    $fail('The password must not be greater than 72 bytes.');
                }
            },
        ];
    }
}
