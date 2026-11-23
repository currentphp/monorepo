<?php

declare(strict_types=1);

namespace App;

use Current\Experience;
use Current\Facades\Current;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

/** Session-only simulation. Current describes it; this class owns its state and rules. */
final class OnboardingDemo
{
    /** @return array{name: string, email: string}|null */
    public function profile(): ?array
    {
        $value = session('onboarding.profile');

        return is_array($value) && is_string($value['name'] ?? null) && is_string($value['email'] ?? null)
            ? ['name' => $value['name'], 'email' => $value['email']] : null;
    }

    public function workspace(): DemoWorkspace
    {
        $name = session('onboarding.workspace');

        return new DemoWorkspace(is_string($name) && $name !== '' ? $name : 'Your workspace');
    }

    public function hasWorkspace(): bool
    {
        return is_string(session('onboarding.workspace'));
    }

    public function complete(): bool
    {
        return session('onboarding.complete') === true;
    }

    public function experience(string $pageId): Experience
    {
        return Current::forPage($pageId, $pageId === 'onboarding.review' ? ['workspace' => $this->workspace()] : []);
    }

    /** @param array<string, mixed> $input */
    public function saveProfile(array $input): void
    {
        $validator = Validator::make($input, ['name' => ['required', 'string', 'max:80'], 'email' => ['required', 'email', 'max:120']]);
        $validator->validate();
        $data = $validator->safe();
        session()->put('onboarding.profile', ['name' => $data->string('name')->toString(), 'email' => $data->string('email')->toString()]);
        session()->forget(['onboarding.workspace', 'onboarding.complete']);
    }

    /** @param array<string, mixed> $input */
    public function saveWorkspace(array $input): void
    {
        Gate::authorize('prepare-demo-workspace');
        $validator = Validator::make($input, ['workspaceName' => ['required', 'string', 'max:80']]);
        $validator->validate();
        session()->put('onboarding.workspace', $validator->safe()->string('workspaceName')->toString());
        session()->forget('onboarding.complete');
    }

    public function finish(): void
    {
        Gate::authorize('finish', $this->workspace());
        session()->put('onboarding.complete', true);
    }

    public function reset(): void
    {
        session()->forget('onboarding');
    }

    /** @return array<string, mixed> */
    public function data(string $pageId, Rendering $rendering): array
    {
        $reader = $this->experience($pageId);

        return [
            'step' => substr($pageId, strlen('onboarding.')) ?: 'intro',
            'profile' => $this->profile(),
            'workspaceName' => $this->hasWorkspace() ? $this->workspace()->name : 'Acme studio',
            'complete' => $this->complete(),
            'steps' => $reader->menu('onboarding'),
            'resetUrl' => $rendering->url(route('onboarding.reset')),
        ];
    }
}
