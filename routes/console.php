<?php

use App\Models\Lesson;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('mcp:list-source-projects', function () {
    $projects = Lesson::distinct()->pluck('source_project')->sort()->values();
    $count = $projects->count();

    $this->info("Total distinct source projects: {$count}");
    $this->newLine();

    if ($count > 0) {
        $this->info('Source projects:');
        foreach ($projects as $project) {
            $lessonCount = Lesson::where('source_project', $project)->count();
            $this->line("  - {$project} ({$lessonCount} lesson(s))");
        }
    }
})->purpose('List all distinct source projects that have pushed lessons');
