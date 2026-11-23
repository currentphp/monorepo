<?php

declare(strict_types=1);

use App\Livewire\ProjectPage;
use App\ProjectExample;
use Current\Facades\Current;
use Illuminate\Support\Facades\Blade;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;

it('renders the same persisted project through Inertia Blade Livewire and JSON', function () {
    $this->from('/projects/42/edit?view=blade')->post('/projects/42/rename', ['name' => 'Shared name'])
        ->assertRedirect('/projects/42/edit?view=blade')->assertSessionHas('status', 'Project renamed.');
    $this->get('/projects/42')->assertInertia(fn (Assert $page) => $page->where('current.subject.label', 'Shared name'));
    $this->get('/projects/42?view=blade')->assertOk()->assertSee('Project Shared name');
    $this->get('/projects/42/edit?view=livewire')->assertOk()->assertSee('Shared name');
    $this->getJson('/api/projects/42/experience')->assertOk()->assertJsonPath('subject.label', 'Shared name');
});

it('uses the executable escaped Blade guide verbatim', function () {
    $guide = file_get_contents(dirname(base_path(), 2).'/packages/current/docs/blade.md');
    preg_match('/```blade\n(.*?)```/s', $guide, $matches);
    $markup = Blade::render($matches[1], ['experience' => Current::forPage('projects.edit', ['project' => ProjectExample::findExample('42')])]);
    expect($markup)->toContain('Project Apollo', 'method="post"');
    ProjectExample::query()->whereKey(42)->update(['name' => '<script>alert(1)</script>']);
    $this->get('/projects/42?view=blade')->assertOk()->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;', false);
});

it('reauthorizes rename and validates before persisting', function () {
    $this->postJson('/projects/42/rename', ['name' => ''])->assertUnprocessable();
    ProjectExample::query()->whereKey(42)->update(['is_editable' => false]);
    $this->postJson('/projects/42/rename', ['name' => 'Denied'])->assertForbidden();
    expect(ProjectExample::findExample('42')->name)->toBe('Apollo');
});

it('reloads models on Livewire updates and rechecks project access', function () {
    $page = Livewire::test(ProjectPage::class, ['project' => ProjectExample::findExample('42')]);
    ProjectExample::query()->whereKey(42)->update(['name' => 'Fresh name']);
    $page->call('tick')->assertSee('Fresh name');
    ProjectExample::query()->whereKey(42)->update(['is_visible' => false]);
    $page->call('tick')->assertForbidden();
});

it('preserves page identity across all renderers', function (string $path, string $pageId, string $title) {
    $this->get($path)->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('current.pageId', $pageId)
        ->where('renderingChoices.0.url', url($path).'?view=inertia')
        ->where('renderingChoices.2.url', url($path).'?view=livewire'));
    foreach (['blade', 'livewire'] as $view) {
        $this->get($path.'?view='.$view)->assertOk()
            ->assertSee('<title>'.$title.' - '.config('app.name').'</title>', false)
            ->assertSee('aria-label="Rendering choices"', false);
    }
    $this->getJson($path.'?view=json')->assertOk()->assertJsonPath('pageId', $pageId)->assertJsonPath('title', $title);
})->with([
    ['/projects', 'projects', 'Projects'],
    ['/projects/42', 'projects.show', 'Project Apollo'],
    ['/projects/42/edit', 'projects.edit', 'Edit Apollo'],
    ['/projects/42/tasks', 'projects.tasks', 'Tasks'],
    ['/projects/42/tasks/1', 'tasks.show', 'Fix login'],
    ['/projects/42/tasks/create', 'tasks.create', 'New task'],
    ['/projects/42/tasks/1/edit', 'tasks.edit', 'Edit'],
]);

it('keeps native navigation and task feedback in the selected renderer', function (string $view) {
    $this->get('/projects/42?view='.$view)->assertOk()->assertSee('/projects/42/edit?view='.$view, false);
    $this->post('/projects/42/tasks?view='.$view, ['title' => 'New task'])
        ->assertRedirect(route('tasks.index', ['project' => 42, 'view' => $view]))
        ->assertSessionHas('status', 'Demo: the new task was not saved.');
})->with(['blade', 'livewire']);

it('rejects unsupported rendering choices', function () {
    $this->get('/projects?view=unknown')->assertNotFound();
});

it('opens directly in demo mode without account routes', function () {
    $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page->component('examples'));
    $this->get('/projects')->assertOk()->assertInertia(fn (Assert $page) => $page->missing('auth'));
    $this->postJson('/projects/42/rename', ['name' => 'No account needed'])->assertOk()->assertJsonPath('name', 'No account needed');
    $this->getJson('/projects/42?view=json')->assertOk()->assertJsonPath('userId', 1);
});

it('has no account entry points', function (string $path) {
    $this->get($path)->assertNotFound();
})->with(['/login', '/register', '/email/verify', '/forgot-password', '/settings/profile', '/dashboard']);

it('denies hidden project routes in every renderer', function (string $view) {
    ProjectExample::query()->whereKey(42)->update(['is_visible' => false]);
    foreach (['/projects/42', '/projects/42/edit', '/projects/42/tasks', '/projects/42/tasks/create', '/projects/42/tasks/1', '/projects/42/tasks/1/edit', '/api/projects/42/experience'] as $path) {
        $this->get($path.'?view='.$view)->assertForbidden();
    }
    $this->post('/projects/42/rename?view='.$view, ['name' => 'Denied'])->assertForbidden();
    $this->post('/projects/42/tasks?view='.$view, ['title' => 'Denied'])->assertForbidden();
    $this->put('/projects/42/tasks/1?view='.$view, ['title' => 'Denied'])->assertForbidden();
    $this->post('/projects/42/tasks/1/archive?view='.$view)->assertForbidden();
    expect(ProjectExample::findExample('42')->name)->toBe('Apollo');
})->with(['inertia', 'blade', 'livewire', 'json']);

it('clears a failed rename validation error after correction', function () {
    Livewire::test(ProjectPage::class, ['project' => ProjectExample::findExample('42')])
        ->set('name', '   ')->call('saveName')->assertHasErrors(['name' => 'required'])
        ->set('name', 'Corrected')->call('saveName')->assertHasNoErrors()->assertSee('Corrected');
    expect(ProjectExample::findExample('42')->name)->toBe('Corrected');
});
