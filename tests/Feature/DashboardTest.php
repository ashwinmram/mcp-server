<?php

use App\Models\Lesson;
use App\Models\LessonUsage;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertStatus(200);
});

test('dashboard shares application name with inertia', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('name', config('app.name')));
});

test('dashboard includes stats with correct structure', function () {
    seedDashboardStatsData();

    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('stats.knowledgeBase', 3)
            ->has('stats.projectDetails', 3)
            ->has('stats.bySourceProject', 3)
            ->has('stats.knowledgeBase.0', fn (Assert $stat) => $stat
                ->hasAll(['name', 'stat', 'previousStat', 'change', 'changeType', 'comparisonType', 'changeFormat'])
            )
            ->where('stats.bySourceProject.0.name', 'legacy-project')
            ->where('stats.bySourceProject.0.stat', '0')
            ->where('stats.bySourceProject.1.name', 'mcp-server')
            ->where('stats.bySourceProject.1.stat', '2')
            ->where('stats.bySourceProject.2.name', 'my-app')
            ->where('stats.bySourceProject.2.stat', '1'));
});

test('dashboard stats reflect seeded knowledge base data', function () {
    seedDashboardStatsData();

    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats.knowledgeBase.0.name', 'Total Lessons')
            ->where('stats.knowledgeBase.0.stat', '5')
            ->where('stats.knowledgeBase.0.previousStat', '3')
            ->where('stats.knowledgeBase.1.name', 'Retrievals')
            ->where('stats.knowledgeBase.1.stat', '3')
            ->where('stats.knowledgeBase.1.previousStat', '2')
            ->where('stats.projectDetails.0.stat', '3')
            ->where('stats.projectDetails.0.previousStat', '2')
            ->where('stats.projectDetails.1.stat', '2')
            ->where('stats.projectDetails.1.previousStat', '2')
            ->where('stats.projectDetails.2.stat', '1')
            ->where('stats.projectDetails.2.previousStat', '2'));
});

test('dashboard stats include comparison metadata', function () {
    seedDashboardStatsData();

    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats.knowledgeBase.0.comparisonType', 'snapshot')
            ->where('stats.knowledgeBase.0.changeFormat', 'relative')
            ->where('stats.knowledgeBase.1.comparisonType', 'prior_period')
            ->where('stats.knowledgeBase.1.changeFormat', 'relative')
            ->where('stats.knowledgeBase.2.comparisonType', 'prior_period')
            ->where('stats.knowledgeBase.2.changeFormat', 'points')
            ->where('stats.projectDetails.0.comparisonType', 'snapshot')
            ->where('stats.projectDetails.2.comparisonType', 'prior_period')
            ->where('stats.bySourceProject.0.comparisonType', 'snapshot'));
});

test('dashboard shows empty state when no projects exist', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('stats.bySourceProject', 0));
});

/**
 * Seed lessons and usages for dashboard stat assertions.
 */
function seedDashboardStatsData(): void
{
    $oldGenericLessons = Lesson::factory()->count(3)->create([
        'is_generic' => true,
        'source_project' => 'legacy-project',
        'created_at' => now()->subDays(45),
        'updated_at' => now()->subDays(45),
    ]);

    $recentGenericLessons = Lesson::factory()->count(2)->create([
        'is_generic' => true,
        'source_project' => 'legacy-project',
        'created_at' => now()->subDays(15),
        'updated_at' => now()->subDays(15),
    ]);

    Lesson::factory()->create([
        'is_generic' => false,
        'source_project' => 'mcp-server',
        'created_at' => now()->subDays(45),
        'updated_at' => now()->subDays(45),
    ]);

    Lesson::factory()->create([
        'is_generic' => false,
        'source_project' => 'mcp-server',
        'created_at' => now()->subDays(15),
        'updated_at' => now()->subDays(15),
    ]);

    Lesson::factory()->create([
        'is_generic' => false,
        'source_project' => 'my-app',
        'created_at' => now()->subDays(45),
        'updated_at' => now()->subDays(45),
    ]);

    $allGenericLessons = $oldGenericLessons->merge($recentGenericLessons);

    LessonUsage::factory()->create([
        'lesson_id' => $allGenericLessons[0]->id,
        'created_at' => now()->subDays(10),
        'updated_at' => now()->subDays(10),
        'was_helpful' => true,
    ]);

    LessonUsage::factory()->create([
        'lesson_id' => $allGenericLessons[1]->id,
        'created_at' => now()->subDays(20),
        'updated_at' => now()->subDays(20),
        'was_helpful' => true,
    ]);

    LessonUsage::factory()->create([
        'lesson_id' => $allGenericLessons[2]->id,
        'created_at' => now()->subDays(5),
        'updated_at' => now()->subDays(5),
        'was_helpful' => false,
    ]);

    LessonUsage::factory()->create([
        'lesson_id' => $allGenericLessons[0]->id,
        'created_at' => now()->subDays(40),
        'updated_at' => now()->subDays(40),
        'was_helpful' => true,
    ]);

    LessonUsage::factory()->create([
        'lesson_id' => $allGenericLessons[1]->id,
        'created_at' => now()->subDays(50),
        'updated_at' => now()->subDays(50),
        'was_helpful' => false,
    ]);
}
