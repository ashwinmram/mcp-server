<?php

namespace LaravelMcpPusher\Support;

enum KnowledgeScope: string
{
    case Generic = 'generic';
    case Project = 'project';

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromPayload(array $payload): self
    {
        $scope = $payload['knowledge_scope'] ?? null;

        if (is_string($scope)) {
            $normalized = strtolower(trim($scope));

            if ($normalized === self::Project->value) {
                return self::Project;
            }

            if ($normalized === self::Generic->value) {
                return self::Generic;
            }
        }

        $type = $payload['type'] ?? null;

        if ($type === 'project_detail') {
            return self::Project;
        }

        return self::Generic;
    }

    public function draftPath(): string
    {
        return match ($this) {
            self::Generic => config('mcp-pusher.generic_draft_jsonl'),
            self::Project => config('mcp-pusher.project_draft_jsonl'),
        };
    }
}
