<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests can view the documentation page', function () {
    $this->get(route('documentation'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Documentation'));
});

test('authenticated users can view the documentation page', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get(route('documentation'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Documentation'));
});
