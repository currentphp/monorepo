<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Profile\UpdateDisplayName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/profile', ['status' => $request->session()->get('status')]);
    }

    public function update(UpdateProfileRequest $request, UpdateDisplayName $update): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        /** @var array{name: string} $input */
        $input = $request->validated();
        $update->handle($user, $user, $input['name']);

        return to_route('profile.edit')->with('status', 'Your display name has been updated.');
    }
}
