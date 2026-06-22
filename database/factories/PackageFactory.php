<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Commerce\BillingInterval;
use App\Enums\Commerce\IeltsModule;
use App\Enums\Commerce\PackageDiscountType;
use App\Enums\Commerce\PackageStatus;
use App\Models\Package;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Package>
 */
class PackageFactory extends Factory
{
    protected $model = Package::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'slug' => Str::slug($name),
            'name' => ucwords($name),
            'description' => fake()->sentence(),
            'module_access' => IeltsModule::values(),
            'attempt_limits' => [],
            'billing_interval' => BillingInterval::Monthly->value,
            'price' => fake()->randomFloat(2, 500, 5000),
            'currency' => 'BDT',
            'discount_type' => PackageDiscountType::None->value,
            'discount_value' => 0,
            'trial_days' => 0,
            'duration_days' => 30,
            'is_active' => true,
            'is_public' => true,
            'sort_order' => fake()->numberBetween(0, 10),
            'status' => PackageStatus::Active->value,
        ];
    }
}
