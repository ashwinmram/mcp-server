<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

test('service provider registers mcp install cursor rules command', function () {
    expect(array_key_exists('mcp:install-cursor-rules', Artisan::all()))->toBeTrue();
});

test('mcp install cursor rules command installs rule files', function () {
    $rulesPath = base_path('.cursor/rules');

    if (File::isDirectory($rulesPath)) {
        File::deleteDirectory($rulesPath);
    }

    Artisan::call('mcp:install-cursor-rules');

    expect($rulesPath.'/mcp-session-startup.mdc')->toBeFile()
        ->and($rulesPath.'/mcp-session-capture.mdc')->toBeFile()
        ->and(File::get($rulesPath.'/mcp-session-startup.mdc'))->toContain('lessons://overview')
        ->and(File::get($rulesPath.'/mcp-session-capture.mdc'))->toContain('mcp:append');
});

test('mcp install cursor rules skips existing files without force', function () {
    $rulesPath = base_path('.cursor/rules');
    File::ensureDirectoryExists($rulesPath);
    File::put($rulesPath.'/mcp-session-startup.mdc', 'existing');

    Artisan::call('mcp:install-cursor-rules');

    expect(File::get($rulesPath.'/mcp-session-startup.mdc'))->toBe('existing');
});

test('mcp install cursor rules overwrites with force', function () {
    $rulesPath = base_path('.cursor/rules');
    File::ensureDirectoryExists($rulesPath);
    File::put($rulesPath.'/mcp-session-startup.mdc', 'existing');

    Artisan::call('mcp:install-cursor-rules', ['--force' => true]);

    expect(File::get($rulesPath.'/mcp-session-startup.mdc'))->toContain('lessons://overview');
});

test('mcp install cursor rules can install cursorrules example', function () {
    $cursorRulesPath = base_path('.cursorrules');
    $backup = File::exists($cursorRulesPath) ? File::get($cursorRulesPath) : null;

    if (File::exists($cursorRulesPath)) {
        File::delete($cursorRulesPath);
    }

    Artisan::call('mcp:install-cursor-rules', ['--with-cursorrules' => true]);

    expect($cursorRulesPath)->toBeFile()
        ->and(File::get($cursorRulesPath))->toContain('mcp:install-cursor-rules');

    if ($backup !== null) {
        File::put($cursorRulesPath, $backup);
    } else {
        File::delete($cursorRulesPath);
    }
});
