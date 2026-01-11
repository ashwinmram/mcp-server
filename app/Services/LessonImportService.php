<?php

namespace App\Services;

use App\Models\Lesson;

class LessonImportService
{
    public function __construct(
        protected LessonValidationService $validationService,
        protected LessonContentHashService $hashService
    ) {}

    /**
     * Process and store lessons with deduplication.
     *
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

                // Check for existing lesson across ALL projects (cross-project deduplication)
                $existingLesson = Lesson::findByContentHashAcrossProjects($contentHash);

                if ($existingLesson) {
                    // Merge tags from existing and new lesson
                    $mergedTags = Lesson::mergeTags(
                        $existingLesson->tags ?? [],
                        $lessonData['tags'] ?? []
                    );

                    // Merge metadata from existing and new lesson
                    $mergedMetadata = Lesson::mergeMetadata(
                        $existingLesson->metadata ?? [],
                        $lessonData['metadata'] ?? []
                    );

                    // Merge source projects
                    $existingSourceProjects = $existingLesson->source_projects ?? [$existingLesson->source_project];
                    $newSourceProjects = array_unique(array_merge($existingSourceProjects, [$sourceProject]));

                    // Check if anything has changed
                    $tagsChanged = $mergedTags !== ($existingLesson->tags ?? []);
                    $metadataChanged = $this->hasMetadataChanged(
                        $existingLesson->metadata ?? [],
                        $lessonData['metadata'] ?? []
                    );
                    $sourceProjectsChanged = $newSourceProjects !== $existingSourceProjects;
                    $categoryChanged = ($lessonData['category'] ?? null) !== $existingLesson->category;

                    if ($tagsChanged || $metadataChanged || $sourceProjectsChanged || $categoryChanged) {
                        // Update existing lesson with merged data
                        $existingLesson->update([
                            'category' => $lessonData['category'] ?? $existingLesson->category,
                            'tags' => $mergedTags,
                            'metadata' => $mergedMetadata,
                            'source_projects' => $newSourceProjects,
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
                        'source_projects' => [$sourceProject],
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
