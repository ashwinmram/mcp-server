<?php

namespace LaravelMcpPusher\Services;

class ContentTagExtractor
{
    /**
     * @param  array<int, string>  $baseTags
     * @return array<int, string>
     */
    public function extract(string $content, array $baseTags = []): array
    {
        $tags = $baseTags;
        $contentLower = strtolower($content);

        $keywordTags = [
            'http::fake' => 'http-mocking',
            'facade' => 'facades',
            'pest' => 'pest',
            'phpunit' => 'phpunit',
            'refreshdatabase' => 'database',
            'feature test' => 'feature-test',
            'unit test' => 'unit-test',
            'artisan command' => 'artisan',
            'service provider' => 'service-provider',
            'composer' => 'composer',
            'package' => 'package',
            'string interpolation' => 'php',
            'null coalescing' => 'php',
        ];

        foreach ($keywordTags as $keyword => $tag) {
            if (str_contains($contentLower, $keyword) && ! in_array($tag, $tags, true)) {
                $tags[] = $tag;
            }
        }

        return array_values(array_unique($tags));
    }
}
