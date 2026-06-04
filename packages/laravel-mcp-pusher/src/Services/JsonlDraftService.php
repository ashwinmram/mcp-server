<?php

namespace LaravelMcpPusher\Services;

use Illuminate\Support\Facades\File;
use LaravelMcpPusher\Support\KnowledgeScope;

class JsonlDraftService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function read(string $path): array
    {
        if (! File::exists($path)) {
            return [];
        }

        $entries = [];
        $lines = preg_split('/\r\n|\r|\n/', File::get($path)) ?: [];

        foreach ($lines as $lineNumber => $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $decoded = json_decode($line, true);

            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
                continue;
            }

            $entries[] = $decoded;
        }

        return $entries;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function append(array $payload, KnowledgeScope $scope): void
    {
        $path = $scope->draftPath();
        $directory = dirname($path);

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $line = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($line === false) {
            throw new \RuntimeException('Failed to encode knowledge entry as JSON.');
        }

        File::append($path, $line.PHP_EOL);
    }

    public function clear(string $path): void
    {
        if (File::exists($path)) {
            File::put($path, '');
        }
    }
}
