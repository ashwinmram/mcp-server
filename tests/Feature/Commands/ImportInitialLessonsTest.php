<?php

use App\Models\Lesson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create temporary directories
    Storage::fake('local');

    // Clean up any existing .cursorrules file to ensure test isolation
    $cursorRulesPath = base_path('.cursorrules');
    if (File::exists($cursorRulesPath)) {
        File::delete($cursorRulesPath);
    }

    // Clean up any existing AI_*.json files in docs directory
    $docsDir = base_path('docs');
    if (File::isDirectory($docsDir)) {
        $aiFiles = File::glob($docsDir.'/AI_*.json');
        foreach ($aiFiles as $file) {
            File::delete($file);
        }
    }
});

test('imports lessons from cursorrules file', function () {
    // Create a temporary .cursorrules file
    $content = 'Always use type hints in PHP functions.';
    $path = base_path('.cursorrules');
    File::put($path, $content);

    $this->artisan('mcp:import-initial', [
        '--source' => 'test-project',
    ])
        ->expectsOutput('Importing lessons from source project: test-project')
        ->expectsOutputToContain('Reading .cursorrules file:')
        ->assertExitCode(0);

    $this->assertDatabaseHas('lessons', [
        'source_project' => 'test-project',
        'type' => 'cursor',
    ]);

    // Cleanup
    if (File::exists($path)) {
        File::delete($path);
    }
});

test('imports lessons from ai json files', function () {
    // Create docs directory and AI JSON file
    $docsDir = base_path('docs');
    if (! File::isDirectory($docsDir)) {
        File::makeDirectory($docsDir, 0755, true);
    }

    $jsonContent = json_encode([
        'lesson1' => 'Always validate user input',
        'lesson2' => 'Use dependency injection',
    ]);
    File::put($docsDir.'/AI_lessons.json', $jsonContent);

    $this->artisan('mcp:import-initial', [
        '--source' => 'test-project',
        '--ai-json-dir' => 'docs',
    ])
        ->assertExitCode(0);

    $this->assertDatabaseHas('lessons', [
        'source_project' => 'test-project',
        'type' => 'ai_output',
    ]);

    // Cleanup
    if (File::exists($docsDir.'/AI_lessons.json')) {
        File::delete($docsDir.'/AI_lessons.json');
    }
});

test('handles missing cursorrules file gracefully', function () {
    $path = base_path('.cursorrules');
    if (File::exists($path)) {
        File::delete($path);
    }

    // Create at least one AI JSON file so the command doesn't fail completely
    $docsDir = base_path('docs');
    if (! File::isDirectory($docsDir)) {
        File::makeDirectory($docsDir, 0755, true);
    }
    File::put($docsDir.'/AI_test.json', json_encode(['test' => 'content']));

    $this->artisan('mcp:import-initial', [
        '--source' => 'test-project',
    ])
        ->expectsOutputToContain('⚠ .cursorrules file not found')
        ->assertExitCode(0);

    // Cleanup
    if (File::exists($docsDir.'/AI_test.json')) {
        File::delete($docsDir.'/AI_test.json');
    }
});

test('handles invalid json files gracefully', function () {
    $docsDir = base_path('docs');
    if (! File::isDirectory($docsDir)) {
        File::makeDirectory($docsDir, 0755, true);
    }

    File::put($docsDir.'/AI_invalid.json', 'invalid json content {');

    // Create a valid .cursorrules file so command doesn't fail completely
    $path = base_path('.cursorrules');
    File::put($path, 'Test content');

    $this->artisan('mcp:import-initial', [
        '--source' => 'test-project',
        '--ai-json-dir' => 'docs',
    ])
        ->expectsOutputToContain('✗ Invalid JSON:')
        ->assertExitCode(0); // Command succeeds because it processes .cursorrules

    // Cleanup
    if (File::exists($docsDir.'/AI_invalid.json')) {
        File::delete($docsDir.'/AI_invalid.json');
    }
    if (File::exists($path)) {
        File::delete($path);
    }
});

test('skips duplicate lessons', function () {
    $content = 'This is duplicate content';
    $path = base_path('.cursorrules');
    File::put($path, $content);

    // First import
    $this->artisan('mcp:import-initial', [
        '--source' => 'test-project',
    ])->assertExitCode(0);

    // Second import (should skip duplicate)
    $this->artisan('mcp:import-initial', [
        '--source' => 'test-project',
    ])
        ->expectsOutputToContain('Skipped:')
        ->assertExitCode(0);

    $this->assertDatabaseCount('lessons', 1);

    // Cleanup
    if (File::exists($path)) {
        File::delete($path);
    }
});

test('outputs import summary', function () {
    $content = 'Test lesson content';
    $path = base_path('.cursorrules');
    File::put($path, $content);

    $this->artisan('mcp:import-initial', [
        '--source' => 'test-project',
    ])
        ->expectsOutput('Import Summary:')
        ->expectsOutputToContain('Created:')
        ->assertExitCode(0);

    // Cleanup
    if (File::exists($path)) {
        File::delete($path);
    }
});
