<?php

namespace App\Services;

class LessonValidationService
{
    /**
     * Validate that lesson content is generic and doesn't contain project-specific information.
     */
    public function validateIsGeneric(string $content): array
    {
        $errors = [];
        $warnings = [];

        // Check for project-specific paths
        if (preg_match('/\/var\/www\/[^\/]+/', $content)) {
            $errors[] = 'Content contains project-specific path (/var/www/...)';
        }

        if (preg_match('/\/home\/[^\/]+\/[^\/]+/', $content)) {
            $errors[] = 'Content contains user-specific path (/home/username/...)';
        }

        // Check for hardcoded domain names (basic check)
        if (preg_match('/https?:\/\/[a-zA-Z0-9-]+\.(local|test|dev)/', $content)) {
            $warnings[] = 'Content may contain development domain reference';
        }

        // Check for specific project names in quotes or variables
        if (preg_match('/["\']([^"\']*project[^"\']*|my-app|my-project)[^"\']*["\']/i', $content)) {
            $warnings[] = 'Content may contain project-specific name references';
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Suggest how to make content more generic.
     */
    public function suggestGenericImprovements(string $content): array
    {
        $suggestions = [];
        $originalContent = $content;

        // Check for and suggest replacing specific paths with placeholders
        if (preg_match('/\/var\/www\/[^\/]+/', $content)) {
            $suggestions[] = 'Replace project-specific paths (/var/www/...) with generic placeholders like "/path/to/project"';
            $content = preg_replace('/\/var\/www\/[^\/]+/', '/path/to/project', $content);
        }

        if (preg_match('/\/home\/[^\/]+\/[^\/]+/', $content)) {
            $suggestions[] = 'Replace user-specific paths (/home/username/...) with generic placeholders like "/path/to/project"';
            $content = preg_replace('/\/home\/[^\/]+\/[^\/]+/', '/path/to/project', $content);
        }

        // Suggest removing specific domain references
        if (preg_match('/https?:\/\/[a-zA-Z0-9-]+\.[a-z]+/', $content)) {
            $suggestions[] = 'Replace specific domain names with placeholders like "example.com" or remove them entirely';
        }

        return $suggestions;
    }
}
