<?php

namespace App\Console\Commands;

use App\Models\Lesson;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeduplicateLessons extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcp:deduplicate-lessons
                            {--dry-run : Show what would be done without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find and merge duplicate lessons across different source projects';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        $this->info('Finding duplicate lessons...');
        $this->newLine();

        // Find all content hashes that appear more than once
        $duplicateHashes = DB::table('lessons')
            ->select('content_hash', DB::raw('COUNT(*) as count'))
            ->groupBy('content_hash')
            ->having('count', '>', 1)
            ->pluck('content_hash');

        if ($duplicateHashes->isEmpty()) {
            $this->info('✓ No duplicate lessons found!');

            return Command::SUCCESS;
        }

        $this->info("Found {$duplicateHashes->count()} content hash(es) with duplicates");
        $this->newLine();

        $totalMerged = 0;
        $totalDeleted = 0;

        foreach ($duplicateHashes as $contentHash) {
            $duplicates = Lesson::findDuplicatesByContentHash($contentHash);

            if ($duplicates->count() <= 1) {
                continue;
            }

            // Keep the oldest lesson as canonical
            $canonical = $duplicates->first();
            $toDelete = $duplicates->skip(1);

            $this->line("  Processing content hash: {$contentHash}");
            $this->line("    Canonical lesson ID: {$canonical->id} (from {$canonical->source_project}, created {$canonical->created_at})");
            $this->line("    Duplicates to merge: {$toDelete->count()}");

            // Collect all source projects
            $allSourceProjects = $duplicates->pluck('source_project')->unique()->values()->toArray();

            // Merge tags from all duplicates
            $allTagsArrays = $duplicates->pluck('tags')->filter()->toArray();
            $mergedTags = Lesson::mergeTags(...$allTagsArrays);

            // Merge metadata from all duplicates
            $allMetadataArrays = $duplicates->pluck('metadata')->filter()->toArray();
            $mergedMetadata = Lesson::mergeMetadata(...$allMetadataArrays);

            // Show what will be merged
            $this->line('    Source projects: '.implode(', ', $allSourceProjects));
            $this->line('    Merged tags: '.count($mergedTags).' tag(s)');
            $this->line('    Merged metadata keys: '.count($mergedMetadata).' key(s)');

            if (! $dryRun) {
                // Update canonical lesson
                $canonical->update([
                    'source_projects' => $allSourceProjects,
                    'tags' => $mergedTags,
                    'metadata' => $mergedMetadata,
                ]);

                // Delete duplicates
                $deletedCount = $toDelete->count();
                foreach ($toDelete as $duplicate) {
                    $duplicate->delete();
                }

                $this->info("    ✓ Merged and deleted {$deletedCount} duplicate(s)");
                $totalDeleted += $deletedCount;
            } else {
                $this->line("    [DRY RUN] Would merge and delete {$toDelete->count()} duplicate(s)");
            }

            $totalMerged++;
            $this->newLine();
        }

        $this->newLine();
        if ($dryRun) {
            $this->info('Summary (DRY RUN):');
            $this->line("  Duplicate groups found: {$totalMerged}");
            $this->line("  Would delete: {$totalDeleted} duplicate lesson(s)");
            $this->newLine();
            $this->comment('Run without --dry-run to apply these changes');
        } else {
            $this->info('✓ Deduplication completed successfully!');
            $this->line("  Merged: {$totalMerged} duplicate group(s)");
            $this->line("  Deleted: {$totalDeleted} duplicate lesson(s)");
        }

        return Command::SUCCESS;
    }
}
