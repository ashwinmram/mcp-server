<?php

it('pre-compact hook outputs valid user_message JSON', function () {
    $script = base_path('packages/laravel-mcp-pusher/stubs/cursor-hooks/pre-compact-checkpoint.sh');
    $prompt = base_path('packages/laravel-mcp-pusher/stubs/knowledge-capture-prompt.txt');

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
        ->and(strlen($decoded['user_message']))->toBeLessThan(2000)
        ->and($decoded['user_message'])->toContain('mcp:append')
        ->and($decoded['user_message'])->toContain('git log')
        ->and($decoded['user_message'])->toContain('Do NOT append raw')
        ->and($decoded['user_message'])->toContain('Git commit');
});

it('knowledge capture prompt stub matches README fenced block', function () {
    $stub = trim(file_get_contents(base_path('packages/laravel-mcp-pusher/stubs/knowledge-capture-prompt.txt')));
    $readme = file_get_contents(base_path('packages/laravel-mcp-pusher/README.md'));

    preg_match('/```text\n(.*?)```/s', $readme, $matches);

    expect($matches)->toHaveCount(2)
        ->and(trim($matches[1]))->toBe($stub);
});
