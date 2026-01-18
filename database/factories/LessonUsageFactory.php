<?php

namespace Database\Factories;

use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LessonUsage>
 */
class LessonUsageFactory extends Factory
{
    protected $model = \App\Models\LessonUsage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lesson_id' => Lesson::factory(),
            'query_context' => fake()->sentence(),
            'was_helpful' => null,
            'session_id' => fake()->uuid(),
        ];
    }

    /**
     * Indicate that the usage was helpful.
     */
    public function helpful(): static
    {
        return $this->state(fn (array $attributes) => [
            'was_helpful' => true,
        ]);
    }

    /**
     * Indicate that the usage was not helpful.
     */
    public function notHelpful(): static
    {
        return $this->state(fn (array $attributes) => [
            'was_helpful' => false,
        ]);
    }
}
