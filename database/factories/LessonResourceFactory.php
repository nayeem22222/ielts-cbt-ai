<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Course\ResourceType;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonResource;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LessonResource>
 */
class LessonResourceFactory extends Factory
{
    protected $model = LessonResource::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $slug = Str::slug(fake()->words(2, true));

        return [
            'course_id' => Course::factory(),
            'lesson_id' => null,
            'title' => fake()->words(3, true),
            'file_path' => 'demo/resources/'.$slug.'.pdf',
            'file_type' => ResourceType::Pdf->value,
            'external_url' => null,
            'sort_order' => fake()->numberBetween(0, 5),
            'is_downloadable' => true,
        ];
    }

    public function forLesson(Lesson $lesson): static
    {
        return $this->state(fn (array $attributes): array => [
            'course_id' => $lesson->section->course_id,
            'lesson_id' => $lesson->id,
        ]);
    }
}
