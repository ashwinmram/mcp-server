<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\LessonUsage;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class DashboardStatsService
{
    /**
     * @return array{knowledgeBase: array<int, array<string, mixed>>, projectDetails: array<int, array<string, mixed>>, bySourceProject: array<int, array<string, mixed>>}
     */
    public function getStats(): array
    {
        return [
            'knowledgeBase' => $this->buildKnowledgeBaseStats(),
            'projectDetails' => $this->buildProjectDetailsStats(),
            'bySourceProject' => $this->buildSourceProjectCards(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildKnowledgeBaseStats(): array
    {
        $now = now();
        $thirtyDaysAgo = $now->copy()->subDays(30);
        $sixtyDaysAgo = $now->copy()->subDays(60);

        $genericQuery = fn (): Builder => Lesson::query()->generic()->active();

        $totalLessonsCurrent = $this->countLessons($genericQuery());
        $totalLessonsPrevious = $this->countLessons($genericQuery(), $thirtyDaysAgo);

        $retrievalsCurrent = $this->countUsagesForLessons($genericQuery(), $thirtyDaysAgo, $now);
        $retrievalsPrevious = $this->countUsagesForLessons($genericQuery(), $sixtyDaysAgo, $thirtyDaysAgo);

        $helpfulnessCurrent = $this->helpfulnessRate($genericQuery(), $thirtyDaysAgo, $now);
        $helpfulnessPrevious = $this->helpfulnessRate($genericQuery(), $sixtyDaysAgo, $thirtyDaysAgo);

        return [
            $this->formatStatCard(__('messages.dashboard.total_lessons'), $totalLessonsCurrent, $totalLessonsPrevious, 'snapshot'),
            $this->formatStatCard(__('messages.dashboard.retrievals'), $retrievalsCurrent, $retrievalsPrevious, 'prior_period'),
            $this->formatHelpfulnessCard($helpfulnessCurrent, $helpfulnessPrevious),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildProjectDetailsStats(): array
    {
        $now = now();
        $thirtyDaysAgo = $now->copy()->subDays(30);
        $sixtyDaysAgo = $now->copy()->subDays(60);

        $projectDetailsQuery = fn (): Builder => Lesson::query()->projectDetails()->active();

        $totalCurrent = $this->countLessons($projectDetailsQuery());
        $totalPrevious = $this->countLessons($projectDetailsQuery(), $thirtyDaysAgo);

        $sourceProjectsCurrent = $this->countDistinctSourceProjects($projectDetailsQuery());
        $sourceProjectsPrevious = $this->countDistinctSourceProjects($projectDetailsQuery(), $thirtyDaysAgo);

        $addedCurrent = $this->countLessonsCreatedBetween($projectDetailsQuery(), $thirtyDaysAgo, $now);
        $addedPrevious = $this->countLessonsCreatedBetween($projectDetailsQuery(), $sixtyDaysAgo, $thirtyDaysAgo);

        return [
            $this->formatStatCard(__('messages.dashboard.total_project_details'), $totalCurrent, $totalPrevious, 'snapshot'),
            $this->formatStatCard(__('messages.dashboard.source_projects'), $sourceProjectsCurrent, $sourceProjectsPrevious, 'snapshot'),
            $this->formatStatCard(__('messages.dashboard.details_added'), $addedCurrent, $addedPrevious, 'prior_period'),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildSourceProjectCards(): array
    {
        $thirtyDaysAgo = now()->subDays(30);

        $projects = Lesson::query()
            ->active()
            ->distinct()
            ->orderBy('source_project')
            ->pluck('source_project');

        return $projects->map(function (string $slug) use ($thirtyDaysAgo) {
            $current = $this->countProjectDetailsForSource($slug);
            $previous = $this->countProjectDetailsForSource($slug, $thirtyDaysAgo);

            return $this->formatStatCard($slug, $current, $previous, 'snapshot');
        })->values()->all();
    }

    private function countLessons(Builder $query, ?Carbon $asOf = null): int
    {
        if ($asOf !== null) {
            $query->where('created_at', '<=', $asOf);
        }

        return $query->count();
    }

    private function countProjectDetailsForSource(string $sourceProject, ?Carbon $asOf = null): int
    {
        $query = Lesson::query()
            ->projectDetails()
            ->active()
            ->bySourceProject($sourceProject);

        if ($asOf !== null) {
            $query->where('created_at', '<=', $asOf);
        }

        return $query->count();
    }

    private function countDistinctSourceProjects(Builder $query, ?Carbon $asOf = null): int
    {
        if ($asOf !== null) {
            $query->where('created_at', '<=', $asOf);
        }

        return $query->distinct()->count('source_project');
    }

    private function countLessonsCreatedBetween(Builder $query, Carbon $from, Carbon $to): int
    {
        return (clone $query)
            ->where('created_at', '>', $from)
            ->where('created_at', '<=', $to)
            ->count();
    }

    private function countUsagesForLessons(Builder $lessonQuery, Carbon $from, Carbon $to): int
    {
        $lessonIds = (clone $lessonQuery)->pluck('id');

        if ($lessonIds->isEmpty()) {
            return 0;
        }

        return LessonUsage::query()
            ->whereIn('lesson_id', $lessonIds)
            ->where('created_at', '>', $from)
            ->where('created_at', '<=', $to)
            ->count();
    }

    private function helpfulnessRate(Builder $lessonQuery, Carbon $from, Carbon $to): ?float
    {
        $lessonIds = (clone $lessonQuery)->pluck('id');

        if ($lessonIds->isEmpty()) {
            return null;
        }

        $usages = LessonUsage::query()
            ->whereIn('lesson_id', $lessonIds)
            ->whereNotNull('was_helpful')
            ->where('created_at', '>', $from)
            ->where('created_at', '<=', $to);

        $total = (clone $usages)->count();

        if ($total === 0) {
            return null;
        }

        $helpful = (clone $usages)->where('was_helpful', true)->count();

        return ($helpful / $total) * 100;
    }

    /**
     * @return array{name: string, stat: string, previousStat: string, change: string, changeType: 'increase'|'decrease', comparisonType: 'snapshot'|'prior_period', changeFormat: 'relative'|'points'}
     */
    private function formatStatCard(string $name, int|float $current, int|float $previous, string $comparisonType): array
    {
        return [
            'name' => $name,
            'stat' => $this->formatNumber($current),
            'previousStat' => $this->formatNumber($previous),
            'change' => $this->formatPercentChange($current, $previous),
            'changeType' => $this->determineChangeType($current, $previous),
            'comparisonType' => $comparisonType,
            'changeFormat' => 'relative',
        ];
    }

    /**
     * @return array{name: string, stat: string, previousStat: string, change: string, changeType: 'increase'|'decrease', comparisonType: 'snapshot'|'prior_period', changeFormat: 'relative'|'points'}
     */
    private function formatHelpfulnessCard(?float $current, ?float $previous): array
    {
        if ($current === null) {
            return [
                'name' => __('messages.dashboard.helpfulness_rate'),
                'stat' => 'N/A',
                'previousStat' => $previous !== null ? $this->formatPercent($previous) : 'N/A',
                'change' => '—',
                'changeType' => 'increase',
                'comparisonType' => 'prior_period',
                'changeFormat' => 'points',
            ];
        }

        $pointChange = $previous !== null ? $current - $previous : null;

        return [
            'name' => __('messages.dashboard.helpfulness_rate'),
            'stat' => $this->formatPercent($current),
            'previousStat' => $previous !== null ? $this->formatPercent($previous) : 'N/A',
            'change' => $pointChange !== null ? sprintf('%+.1f%%', $pointChange) : '—',
            'changeType' => ($pointChange ?? 0) >= 0 ? 'increase' : 'decrease',
            'comparisonType' => 'prior_period',
            'changeFormat' => 'points',
        ];
    }

    private function formatNumber(int|float $value): string
    {
        return number_format($value);
    }

    private function formatPercent(float $value): string
    {
        return number_format($value, 1).'%';
    }

    private function formatPercentChange(int|float $current, int|float $previous): string
    {
        if ($previous == 0) {
            return $current > 0 ? '100%' : '—';
        }

        $change = (($current - $previous) / $previous) * 100;

        return number_format(abs($change), 1).'%';
    }

    /**
     * @return 'increase'|'decrease'
     */
    private function determineChangeType(int|float $current, int|float $previous): string
    {
        return $current >= $previous ? 'increase' : 'decrease';
    }
}
