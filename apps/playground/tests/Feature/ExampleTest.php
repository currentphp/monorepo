<?php

use Inertia\Testing\AssertableInertia as Assert;

test('opens the curated examples overview', function () {
    $this->get(route('home'))->assertOk()->assertInertia(fn (Assert $page) => $page->component('examples'));
});
