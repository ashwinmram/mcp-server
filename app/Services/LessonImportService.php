<?php

namespace App\Services;

use App\Models\Lesson;

class LessonImportService
{
    public function __construct(
        protected LessonValidationService $validationService,
        protected LessonContentHashService $hashService
    ) {
    }

    /**
     * Process and store lessons with deduplication.
     *
     * @param  array  $lessons
     * @param  string  $sourceProject
     * @return array{created: int, updated: int, skipped: int, errors: array}
     */
    public function processLessons(array $lessons, string $sourceProject): array
    {
        $result = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        foreach ($lessons as $index => $lessonData) {
            try {
                // Validate lesson structure
                if (empty($lessonData['content']) || empty($lessonData['type'])) {
                    $result['errors'][] = "Lesson at index {$index}: Missing required fields (content or type)";
                    continue;
                }

                // Validate content is generic
                $validation = $this->validationService->validateIsGeneric($lessonData['content']);
                if (! $validation['is_valid']) {
                    $result['errors'][] = "Lesson at index {$index}: ".implode(', ', $validation['errors']);
                    continue;
                }

                // Generate content hash
                $contentHash = $this->hashService->generateHash($lessonData['content']);

                // Check for existing lesson
                $existingLesson = Lesson::findByContentHash($contentHash, $sourceProject);

                if ($existingLesson) {
                    // Check if metadata has changed
                    $metadataChanged = $this->hasMetadataChanged(
                        $existingLesson->metadata ?? [],
                        $lessonData['metadata'] ?? []
                    );

                    if ($metadataChanged) {
                        // Update existing lesson
                        $existingLesson->update([
                            'category' => $lessonData['category'] ?? $existingLesson->category,
                            'tags' => $lessonData['tags'] ?? $existingLesson->tags,
                            'metadata' => array_merge($existingLesson->metadata ?? [], $lessonData['metadata'] ?? []),
                            'is_generic' => $validation['is_valid'],
                        ]);
                        $result['updated']++;
                    } else {
                        // Skip identical lesson
                        $result['skipped']++;
                    }
                } else {
                    // Create new lesson
                    Lesson::create([
                        'source_project' => $sourceProject,
                        'type' => $lessonData['type'],
                        'category' => $lessonData['category'] ?? null,
                        'tags' => $lessonData['tags'] ?? [],
                        'metadata' => $lessonData['metadata'] ?? [],
                        'content' => $lessonData['content'],
                        'content_hash' => $contentHash,
                        'is_generic' => true,
                    ]);
                    $result['created']++;
                }
            } catch (\Exception $e) {
                $result['errors'][] = "Lesson at index {$index}: {$e->getMessage()}";
            }
        }

        return $result;
    }

    /**
     * Check if metadata has changed.
     */
    protected function hasMetadataChanged(array $existing, array $new): bool
    {
        // Merge and compare - if new keys exist or values changed, metadata changed
        $merged = array_merge($existing, $new);
        ksort($merged);
        ksort($existing);

        return $merged !== $existing;
    }
}
