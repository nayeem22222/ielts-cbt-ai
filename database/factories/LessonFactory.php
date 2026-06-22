<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Course\LessonContentType;
use App\Enums\Course\PublishStatus;
use App\Models\CourseSection;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Lesson>
 */
class LessonFactory extends Factory
{
    protected $model = Lesson::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(3);

        return [
            'course_section_id' => CourseSection::factory(),
            'slug' => Str::slug($title),
            'title' => rtrim($title, '.'),
            'description' => fake()->paragraph(),
            'content_type' => fake()->randomElement(LessonContentType::values()),
            'video_url' => 'https://example.com/demo/'.Str::uuid(),
            'duration_seconds' => fake()->numberBetween(300, 1800),
            'is_preview' => false,
            'sort_order' => fake()->numberBetween(0, 10),
            'status' => PublishStatus::Published->value,
            'published_at' => now(),
        ];
    }
}
