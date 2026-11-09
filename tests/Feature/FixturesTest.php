<?php

declare(strict_types=1);

use App\Fixtures;
use App\ProjectExample;
use Inertia\Testing\AssertableInertia as Assert;

it('serves demo projects from the JSON fixture', function () {
    $names = array_column(Fixtures::load('projects'), 'name');

    expect(array_map(fn (ProjectExample $project): string => $project->name, ProjectExample::examples()))->toBe($names);
    $this->get(route('projects.index'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->has('projects', count($names))
        ->where('projects.0.name', $names[0]));
});

it('rejects a missing fixture', function () {
    Fixtures::load('missing');
})->throws(LogicException::class, 'is missing');
