<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->user()) ?? false;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255']];
    }
}
