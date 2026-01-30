<?php

use App\Models\User;

test('swapping to Arabic sets locale and next page has dir rtl and lang ar', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('dashboard'));
    $this->get(route('locale.swap', ['locale' => 'ar']), [
        'Referer' => route('dashboard'),
    ])->assertRedirect(route('dashboard'));

    $response = $this->get(route('dashboard'));
    $response->assertOk();
    $response->assertSee('dir="rtl"', false);
    $response->assertSee('lang="ar"', false);
});

test('swapping to English sets locale and next page has dir ltr', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('locale.swap', ['locale' => 'ar']), [
        'Referer' => route('dashboard'),
    ]);
    $this->get(route('locale.swap', ['locale' => 'en']), [
        'Referer' => route('dashboard'),
    ])->assertRedirect(route('dashboard'));

    $response = $this->get(route('dashboard'));
    $response->assertOk();
    $response->assertSee('dir="ltr"', false);
});

test('invalid locale redirects back without changing locale', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('dashboard'));
    $response = $this->get(route('locale.swap', ['locale' => 'xy']), [
        'Referer' => route('dashboard'),
    ]);

    $response->assertRedirect(route('dashboard'));
    $response = $this->get(route('dashboard'));
    $response->assertSee('dir="ltr"', false);
});
