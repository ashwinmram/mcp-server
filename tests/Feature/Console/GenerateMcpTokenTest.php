<?php

use App\Models\User;

it('generates a token with the default mcp-client-token name', function () {
    $this->artisan('mcp:generate-token', ['--force' => true])
        ->assertSuccessful();

    $user = User::first();
    expect($user)->not->toBeNull();

    $token = $user->tokens()->where('name', 'mcp-client-token')->first();
    expect($token)->not->toBeNull();
});

it('generates a token with a custom name', function () {
    $this->artisan('mcp:generate-token', [
        '--name' => 'claude-mcp-token',
        '--force' => true,
    ])->assertSuccessful();

    $user = User::first();
    expect($user->tokens()->where('name', 'claude-mcp-token')->exists())->toBeTrue();
});
