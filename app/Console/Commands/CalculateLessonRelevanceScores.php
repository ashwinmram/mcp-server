<?php

namespace App\Console\Commands;

use App\Models\Lesson;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class CalculateLessonRelevanceScores extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lessons:calculate-relevance-scores
                            {--chunk=100 : Number of lessons to process at a time}
                            {--dry-run : Show what would be updated without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate and update relevance scores for lessons based on usage patterns';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $chunkSize = (int) $this->option('chunk');

        // Check if lesson_usages table exists
        if (! Schema::hasTable('lesson_usages')) {
            $this->error('lesson_usages table does not exist. Please run migrations first.');

            return Command::FAILURE;
        }

        // Check if relevance_score column exists
        if (! Schema::hasColumn('lessons', 'relevance_score')) {
            $this->error('relevance_score column does not exist. Please run migrations first.');

            return Command::FAILURE;
        }

        $this->info('Calculating relevance scores for all lessons...');
        $this->newLine();

        $totalLessons = Lesson::count();
        $processed = 0;
        $updated = 0;

        Lesson::query()->chunk($chunkSize, function ($lessons) use (&$processed, &$updated, $dryRun) {
            foreach ($lessons as $lesson) {
                $score = $this->calculateRelevanceScore($lesson);

                if ($dryRun) {
                    $currentScore = $lesson->relevance_score ?? 0.0;
                    if (abs($currentScore - $score) > 0.001) {
                        $this->line("Lesson {$lesson->id} ({$lesson->title}): {$currentScore} → {$score}");
                        $updated++;
                    }
                } else {
                    $lesson->update(['relevance_score' => $score]);
                    $updated++;
                }

                $processed++;
            }
        });

        $this->newLine();
        if ($dryRun) {
            $this->info("✓ Dry run complete: {$processed} lessons processed, {$updated} would be updated.");
        } else {
            $this->info("✓ Completed: {$processed} lessons processed, {$updated} relevance scores updated.");
        }

        return Command::SUCCESS;
    }

    /**
     * Calculate relevance score for a lesson.
     *
     * Formula: (usage_count * 0.4) + (helpfulness_rate * 0.4) + (recency_weight * 0.2)
     */
    protected function calculateRelevanceScore(Lesson $lesson): float
    {
        // Get usage statistics
        $usages = $lesson->usages();

        // Usage count: number of times lesson was retrieved
        $usageCount = $usages->count();

        // Helpfulness rate: percentage of positive feedback (0-1)
        $helpfulCount = $usages->where('was_helpful', true)->count();
        $helpfulnessRate = $usageCount > 0 ? ($helpfulCount / $usageCount) : 0.0;

        // Recency weight: newer lessons get slight boost (0-1)
        // Normalize age: lessons older than 365 days get 0, new lessons get 1
        $daysSinceCreation = $lesson->created_at->diffInDays(now());
        $maxAgeDays = 365;
        $recencyWeight = max(0, 1 - ($daysSinceCreation / $maxAgeDays));

        // Normalize usage count: scale to 0-1 range using logarithmic scale
        // This prevents one highly-used lesson from dominating all scores
        // We'll use a simple normalization: log(usage_count + 1) / log(max_usage + 1)
        // For simplicity, we'll assume a reasonable max (e.g., 1000 uses = 1.0)
        // This avoids expensive queries during calculation
        $maxUsageForNormalization = 1000;
        $normalizedUsageCount = $maxUsageForNormalization > 0
            ? min(1.0, log($usageCount + 1) / log($maxUsageForNormalization + 1))
            : 0.0;

        // Calculate final score
        $score = ($normalizedUsageCount * 0.4) + ($helpfulnessRate * 0.4) + ($recencyWeight * 0.2);

        // Ensure score is between 0 and 1
        return max(0.0, min(1.0, $score));
    }
}
