<?php

declare(strict_types=1);

use App\OnboardingDemo;
use Inertia\Testing\AssertableInertia as Assert;

it('starts as an optional simulation with only the first step visible', function () {
    $this->get('/onboarding')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('onboarding')->where('step', 'intro')->where('profile', null)->where('complete', false)
        ->has('steps', 1)->where('current.actions.0.label', 'Try simulated signup'));
    $this->getJson('/onboarding/workspace?view=json')->assertOk()->assertJsonPath('current.actions.0.enabled', false)
        ->assertJsonPath('current.actions.0.reason', 'Complete your demo profile first.');
    $this->post('/onboarding/workspace', ['workspaceName' => 'Skipped step'])->assertForbidden();
    $this->post('/onboarding/workspaces/demo/finish')->assertForbidden();
});

it('shows the same onboarding context in every renderer', function (string $path, string $pageId, string $title) {
    $this->get($path)->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('current.pageId', $pageId)->where('current.title', $title)
        ->where('renderingChoices.2.url', url($path).'?view=livewire'));
    foreach (['blade', 'livewire'] as $view) {
        $this->get($path.'?view='.$view)->assertOk()->assertSee('<title>'.$title.' - '.config('app.name').'</title>', false);
    }
    $this->getJson($path.'?view=json')->assertOk()->assertJsonPath('current.pageId', $pageId);
})->with([
    ['/onboarding', 'onboarding', 'Try simulated onboarding'],
    ['/onboarding/profile', 'onboarding.profile', 'Create your demo profile'],
    ['/onboarding/workspace', 'onboarding.workspace', 'Prepare a workspace for your demo profile'],
    ['/onboarding/workspaces/demo', 'onboarding.review', 'Review Your workspace'],
]);

it('advances the simulation with validation dynamic navigation and subject presentation', function (string $view) {
    $this->postJson('/onboarding/profile', ['name' => '', 'email' => 'invalid'])->assertUnprocessable();
    $this->post('/onboarding/profile?view='.$view, ['name' => 'Alex', 'email' => 'alex@example.test'])
        ->assertRedirect(route('onboarding.workspace', ['view' => $view]));
    $this->getJson('/onboarding/workspace?view=json')->assertOk()->assertJsonCount(2, 'steps')
        ->assertJsonPath('current.title', 'Prepare a workspace for Alex')->assertJsonPath('current.actions.0.enabled', true);
    $this->postJson('/onboarding/workspace', ['workspaceName' => ''])->assertUnprocessable();
    $this->post('/onboarding/workspace?view='.$view, ['workspaceName' => 'Acme studio'])
        ->assertRedirect(route('onboarding.review', ['workspace' => 'demo', 'view' => $view]));
    $this->getJson('/onboarding/workspaces/demo?view=json')->assertOk()
        ->assertJsonPath('current.subject.label', 'Acme studio')->assertJsonPath('current.title', 'Review Acme studio')
        ->assertJsonPath('current.actions.0.enabled', true)->assertJsonCount(3, 'steps')->assertJsonCount(1, 'current.actions');
    $this->post('/onboarding/workspaces/demo/finish?view='.$view)
        ->assertRedirect(route('onboarding.review', ['workspace' => 'demo', 'view' => $view]));
    $this->getJson('/onboarding/workspaces/demo?view=json')->assertOk()->assertJsonPath('complete', true)
        ->assertJsonPath('current.actions.0.enabled', false)->assertJsonPath('current.actions.1.label', 'Open Projects');
    $this->post('/onboarding/reset?view='.$view)->assertRedirect(route('onboarding.index', ['view' => $view]));
    $this->getJson('/onboarding?view=json')->assertOk()->assertJsonPath('profile', null)->assertJsonPath('complete', false)->assertJsonCount(1, 'steps');
    $this->assertDatabaseHas('projects', ['id' => 42, 'name' => 'Apollo']);
})->with(['blade', 'livewire', 'json']);

it('escapes simulated names and resets dependent state when the profile changes', function () {
    $this->post('/onboarding/profile', ['name' => 'Alex', 'email' => 'alex@example.test']);
    $this->post('/onboarding/workspace', ['workspaceName' => '<script>alert(1)</script>']);
    $this->get('/onboarding/workspaces/demo?view=blade')->assertOk()->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;', false);
    $this->post('/onboarding/profile', ['name' => 'Taylor', 'email' => 'taylor@example.test']);
    expect(app(OnboardingDemo::class)->hasWorkspace())->toBeFalse();
});
