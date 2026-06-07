<?php

use Inertia\Testing\AssertableInertia as Assert;

test('home page renders welcome component with demo video id from config', function () {
    config(['landing.demo_video_id' => 'test-video-id']);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->where('demoVideoId', 'test-video-id'));
});

test('home page passes hardcoded demo video id by default', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->where('demoVideoId', 'PNt151KVCO0'));
});
