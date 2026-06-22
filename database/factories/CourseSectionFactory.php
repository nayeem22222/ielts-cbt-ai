<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Course\PublishStatus;
use App\Models\Course;
use App\Models\CourseSection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CourseSection>
 */
class CourseSectionFactory extends Factory
{
    protected $model = CourseSection::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->words(3, true);

        return [
            'course_id' => Course::factory(),
            'title' => ucwords($title),
            'slug' => Str::slug($title),
            'description' => fake()->sentence(),
            'sort_order' => fake()->numberBetween(0, 10),
            'status' => PublishStatus::Published->value,
        ];
    }
}
