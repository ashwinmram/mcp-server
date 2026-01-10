<?php

use App\Services\LessonContentHashService;

test('generates hash for content', function () {
    $service = new LessonContentHashService();
    $content = 'Test lesson content';

    $hash = $service->generateHash($content);

    expect($hash)->toBeString()
        ->and(strlen($hash))->toBe(64) // SHA-256 produces 64 character hex string
        ->and($hash)->toBe(hash('sha256', $content));
});

test('generates consistent hash for same content', function () {
    $service = new LessonContentHashService();
    $content = 'Test lesson content';

    $hash1 = $service->generateHash($content);
    $hash2 = $service->generateHash($content);

    expect($hash1)->toBe($hash2);
});

test('generates different hash for different content', function () {
    $service = new LessonContentHashService();

    $hash1 = $service->generateHash('Content one');
    $hash2 = $service->generateHash('Content two');

    expect($hash1)->not->toBe($hash2);
});

test('compares hashes correctly', function () {
    $service = new LessonContentHashService();
    $hash = $service->generateHash('Test content');

    expect($service->compare($hash, $hash))->toBeTrue();
});

test('compares different hashes correctly', function () {
    $service = new LessonContentHashService();
    $hash1 = $service->generateHash('Content one');
    $hash2 = $service->generateHash('Content two');

    expect($service->compare($hash1, $hash2))->toBeFalse();
});
