<?php

namespace LaravelMcpPusher\Services;

use InvalidArgumentException;
use LaravelMcpPusher\Support\KnowledgeScope;

class KnowledgeEntryValidator
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function validateForAppend(array $payload, KnowledgeScope $scope): void
    {
        $required = ['title', 'summary', 'category', 'subcategory', 'type', 'tags', 'content'];

        foreach ($required as $field) {
            if (! array_key_exists($field, $payload) || $payload[$field] === '' || $payload[$field] === null) {
                throw new InvalidArgumentException("Missing or empty required field: {$field}");
            }
        }

        if (! is_array($payload['tags'])) {
            throw new InvalidArgumentException('Field tags must be an array.');
        }

        if ($scope === KnowledgeScope::Project && ! in_array($payload['type'], ['cursor', 'ai_output', 'manual', 'markdown', 'project_detail'], true)) {
            throw new InvalidArgumentException('Invalid type for project knowledge entry.');
        }
    }
}
