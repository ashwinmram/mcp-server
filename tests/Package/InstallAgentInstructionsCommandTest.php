<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

test('service provider registers claude and antigravity install commands', function () {
    $commands = Artisan::all();

    expect(array_key_exists('mcp:install-claude-instructions', $commands))->toBeTrue()
        ->and(array_key_exists('mcp:install-antigravity-skills', $commands))->toBeTrue()
        ->and(array_key_exists('mcp:install-agent-instructions', $commands))->toBeTrue();
});

test('mcp install claude instructions command installs rule files', function () {
    $rulesPath = base_path('.claude/rules');

    if (File::isDirectory($rulesPath)) {
        File::deleteDirectory($rulesPath);
    }

    Artisan::call('mcp:install-claude-instructions');

    expect($rulesPath.'/mcp-session-startup.md')->toBeFile()
        ->and($rulesPath.'/mcp-session-capture.md')->toBeFile()
        ->and(File::get($rulesPath.'/mcp-session-startup.md'))->toContain('lessons://overview');
});

test('mcp install claude instructions can install CLAUDE.md index', function () {
    $claudeMd = base_path('CLAUDE.md');
    $backup = File::exists($claudeMd) ? File::get($claudeMd) : null;

    if (File::exists($claudeMd)) {
        File::delete($claudeMd);
    }

    Artisan::call('mcp:install-claude-instructions', ['--with-claude-md' => true]);

    expect($claudeMd)->toBeFile()
        ->and(File::get($claudeMd))->toContain('mcp:install-claude-instructions');

    if ($backup !== null) {
        File::put($claudeMd, $backup);
    } else {
        File::delete($claudeMd);
    }
});

test('mcp install antigravity skills command installs workspace skills', function () {
    $skillsPath = base_path('.agent/skills');

    if (File::isDirectory($skillsPath)) {
        File::deleteDirectory(base_path('.agent'));
    }

    Artisan::call('mcp:install-antigravity-skills');

    expect($skillsPath.'/mcp-session-startup/SKILL.md')->toBeFile()
        ->and($skillsPath.'/mcp-session-capture/SKILL.md')->toBeFile()
        ->and(File::get($skillsPath.'/mcp-session-startup/SKILL.md'))->toContain('name: mcp-session-startup');
});

test('mcp install agent instructions runs all client installers', function () {
    foreach (['.cursor/rules', '.claude/rules', '.agent/skills'] as $path) {
        $full = base_path($path);
        $parent = dirname($full);

        if (File::isDirectory($full)) {
            File::deleteDirectory($full);
        }

        if (str_ends_with($path, '.agent/skills') && File::isDirectory($parent) && count(File::allFiles($parent)) === 0) {
            File::deleteDirectory($parent);
        }
    }

    Artisan::call('mcp:install-agent-instructions');

    expect(base_path('.cursor/rules/mcp-session-startup.mdc'))->toBeFile()
        ->and(base_path('.claude/rules/mcp-session-startup.md'))->toBeFile()
        ->and(base_path('.agent/skills/mcp-session-startup/SKILL.md'))->toBeFile();
});
