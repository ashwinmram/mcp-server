<?php

namespace LaravelMcpPusher\Services;

use Illuminate\Support\Facades\File;

class KnowledgeSourceTruncator
{
    /**
     * @param  array<int, array{path: string, kind: string}>  $sources
     */
    public function truncate(array $sources): void
    {
        $processed = [];

        foreach ($sources as $source) {
            $path = $source['path'];

            if (in_array($path, $processed, true)) {
                continue;
            }

            $processed[] = $path;

            match ($source['kind']) {
                'markdown_empty' => File::put($path, ''),
                'json_array' => File::put($path, "[]\n"),
                'jsonl_clear' => File::put($path, ''),
                default => null,
            };
        }
    }
}
