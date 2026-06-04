<?php

it('pre-compact hook outputs valid user_message JSON', function () {
    $script = base_path('packages/laravel-mcp-pusher/stubs/cursor-hooks/pre-compact-checkpoint.sh');
    $prompt = base_path('packages/laravel-mcp-pusher/stubs/cursor-hooks/pre-compact-prompt.txt');

    expect($script)->toBeReadableFile();
    expect($prompt)->toBeReadableFile();

    $command = sprintf(
        'echo \'{"trigger":"auto"}\' | %s',
        escapeshellarg($script),
    );

    $output = shell_exec($command);

    expect($output)->not->toBeEmpty();

    $decoded = json_decode(trim($output), true);

    expect($decoded)->toBeArray()
        ->and($decoded)->toHaveKey('user_message')
        ->and($decoded['user_message'])->toBeString()
        ->and($decoded['user_message'])->not->toBeEmpty()
        ->and($decoded['user_message'])->toContain('mcp:append');
});
