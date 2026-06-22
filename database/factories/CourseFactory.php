<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Course\CourseLevel;
use App\Enums\Course\ExamType;
use App\Enums\Course\PublishStatus;
use App\Models\Course;
use App\Models\CourseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(4);

        return [
            'course_category_id' => CourseCategory::factory(),
            'slug' => Str::slug($title),
            'title' => rtrim($title, '.'),
            'description' => fake()->paragraphs(2, true),
            'exam_type' => fake()->randomElement(ExamType::values()),
            'level' => fake()->randomElement(CourseLevel::values()),
            'thumbnail_path' => 'placeholders/courses/'.Str::slug($title).'.jpg',
            'status' => PublishStatus::Published->value,
            'sort_order' => fake()->numberBetween(0, 10),
            'published_at' => now(),
        ];
    }
}
