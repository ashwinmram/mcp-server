<?php

namespace Database\Factories;

use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lesson>
 */
class LessonFactory extends Factory
{
    protected $model = Lesson::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $content = $this->faker->paragraph();

        return [
            'source_project' => $this->faker->word(),
            'type' => $this->faker->randomElement(['cursor', 'ai_output', 'manual', 'markdown']),
            'category' => $this->faker->optional()->randomElement(['validation', 'routing', 'security', 'coding']),
            'tags' => $this->faker->optional()->randomElements(['php', 'laravel', 'api', 'best-practices'], $this->faker->numberBetween(1, 3)),
            'metadata' => $this->faker->optional()->randomElement([
                ['file' => 'lessons-learned.md'],
                ['file' => 'lessons_learned.json'],
                [],
            ]),
            'content' => $content,
            // content_hash will be generated automatically by model boot method
            'is_generic' => true,
        ];
    }

    /**
     * Indicate that the lesson is not generic.
     */
    public function nonGeneric(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_generic' => false,
        ]);
    }

    /**
     * Indicate that the lesson is of type cursor.
     */
    public function cursor(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'cursor',
        ]);
    }

    /**
     * Indicate that the lesson is of type ai_output.
     */
    public function aiOutput(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'ai_output',
        ]);
    }

    /**
     * Indicate that the lesson is of type markdown.
     */
    public function markdown(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'markdown',
        ]);
    }
}
