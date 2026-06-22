<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Enrollment\StudentPackageStatus;
use App\Models\Package;
use App\Models\StudentPackage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentPackage>
 */
class StudentPackageFactory extends Factory
{
    protected $model = StudentPackage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'package_id' => Package::factory(),
            'order_id' => null,
            'status' => StudentPackageStatus::Pending->value,
            'starts_at' => null,
            'expires_at' => null,
            'activated_at' => null,
            'cancelled_at' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => StudentPackageStatus::Active->value,
            'starts_at' => now(),
            'expires_at' => now()->addDays(30),
            'activated_at' => now(),
        ]);
    }
}
