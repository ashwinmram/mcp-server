<?php

namespace LaravelMcpPusher\Data;

class CollectedKnowledge
{
    /**
     * @param  array<int, array<string, mixed>>  $lessons
     * @param  array<int, array{path: string, kind: string}>  $sourcesToTruncate
     */
    public function __construct(
        public array $lessons,
        public array $sourcesToTruncate,
    ) {}

    public function isEmpty(): bool
    {
        return $this->lessons === [];
    }
}
