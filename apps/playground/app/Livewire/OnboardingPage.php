<?php

declare(strict_types=1);

namespace App\Livewire;

use App\DemoWorkspace;
use App\OnboardingDemo;
use App\Rendering;
use Current\Facades\Current;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('experience.layout', ['livewire' => true])]
final class OnboardingPage extends Component
{
    #[Locked]
    public string $pageId = 'onboarding';

    public string $name = 'Alex Taylor';

    public string $email = 'alex@example.test';

    public string $workspaceName = 'Acme studio';

    public function mount(?DemoWorkspace $workspace = null): void
    {
        $this->pageId = Current::pageId() ?? 'onboarding';
        $demo = app(OnboardingDemo::class);
        $profile = $demo->profile();
        $this->name = $profile['name'] ?? $this->name;
        $this->email = $profile['email'] ?? $this->email;
        $this->workspaceName = $demo->hasWorkspace() ? $demo->workspace()->name : $this->workspaceName;
    }

    public function submit(): void
    {
        $demo = app(OnboardingDemo::class);
        $this->name = trim($this->name);
        $this->email = trim($this->email);
        $this->workspaceName = trim($this->workspaceName);
        $next = match ($this->pageId) {
            'onboarding.profile' => $this->saveProfile($demo),
            'onboarding.workspace' => $this->saveWorkspace($demo),
            'onboarding.review' => $this->finish($demo),
            default => abort(404),
        };
        $this->redirect(Rendering::Livewire->url($next));
    }

    private function saveProfile(OnboardingDemo $demo): string
    {
        $demo->saveProfile(['name' => $this->name, 'email' => $this->email]);

        return route('onboarding.workspace');
    }

    private function saveWorkspace(OnboardingDemo $demo): string
    {
        $demo->saveWorkspace(['workspaceName' => $this->workspaceName]);

        return route('onboarding.review', 'demo');
    }

    private function finish(OnboardingDemo $demo): string
    {
        $demo->finish();
        session()->flash('status', 'Simulation complete. No account was created.');

        return route('onboarding.review', 'demo');
    }

    public function render(): View
    {
        $demo = app(OnboardingDemo::class);

        return view('onboarding.livewire', [...$demo->data($this->pageId, Rendering::Livewire),
            'experience' => $demo->experience($this->pageId), 'live' => true,
        ])->title($demo->experience($this->pageId)->title());
    }
}
