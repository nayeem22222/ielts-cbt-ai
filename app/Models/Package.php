<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Commerce\BillingInterval;
use App\Enums\Commerce\IeltsModule;
use App\Enums\Commerce\PackageDiscountType;
use App\Enums\Commerce\PackageStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Package extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'slug',
        'name',
        'description',
        'module_access',
        'attempt_limits',
        'billing_interval',
        'price',
        'currency',
        'discount_type',
        'discount_value',
        'trial_days',
        'duration_days',
        'is_active',
        'is_public',
        'stripe_product_id',
        'stripe_price_id',
        'sort_order',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'module_access' => 'array',
            'attempt_limits' => 'array',
            'billing_interval' => BillingInterval::class,
            'price' => 'decimal:2',
            'discount_type' => PackageDiscountType::class,
            'discount_value' => 'decimal:2',
            'trial_days' => 'integer',
            'duration_days' => 'integer',
            'is_active' => 'boolean',
            'is_public' => 'boolean',
            'sort_order' => 'integer',
            'status' => PackageStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Package $package): void {
            if (empty($package->uuid)) {
                $package->uuid = (string) Str::uuid();
            }
        });
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class)
            ->withPivot(['sort_order', 'is_featured'])
            ->withTimestamps();
    }

    public function allowsModule(IeltsModule|string $module): bool
    {
        $value = $module instanceof IeltsModule ? $module->value : $module;
        $access = $this->module_access ?? [];

        if ($access === []) {
            return true;
        }

        return in_array($value, $access, true);
    }

    public function attemptLimitFor(IeltsModule|string $module): ?int
    {
        $value = $module instanceof IeltsModule ? $module->value : $module;
        $limits = $this->attempt_limits ?? [];

        if (! array_key_exists($value, $limits) || $limits[$value] === null || $limits[$value] === '') {
            return null;
        }

        return (int) $limits[$value];
    }

    public function effectivePrice(): float
    {
        $price = (float) $this->price;

        return match ($this->discount_type) {
            PackageDiscountType::Percent => max(0, round($price - ($price * ((float) $this->discount_value / 100)), 2)),
            PackageDiscountType::Fixed => max(0, round($price - (float) $this->discount_value, 2)),
            default => $price,
        };
    }

    /**
     * @return list<string>
     */
    public function enabledModules(): array
    {
        $access = $this->module_access ?? [];

        if ($access === []) {
            return array_map(static fn (IeltsModule $module): string => $module->value, IeltsModule::cases());
        }

        return $access;
    }
}
