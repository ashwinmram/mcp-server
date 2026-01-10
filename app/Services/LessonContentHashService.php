<?php

namespace App\Services;

class LessonContentHashService
{
    /**
     * Generate a SHA-256 hash for lesson content.
     */
    public function generateHash(string $content): string
    {
        return hash('sha256', $content);
    }

    /**
     * Compare two content hashes.
     */
    public function compare(string $hash1, string $hash2): bool
    {
        return hash_equals($hash1, $hash2);
    }
}
