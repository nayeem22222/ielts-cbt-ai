<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\Commerce\BillingInterval;
use App\Enums\Commerce\IeltsModule;
use App\Enums\Commerce\PackageDiscountType;
use App\Enums\Commerce\PackageStatus;
use App\Models\Package;
use Illuminate\Validation\Rule;

class UpdatePackageRequest extends CourseSlugRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('package')) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->prepareSlug('name');
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'is_public' => $this->boolean('is_public'),
            'module_access' => $this->input('module_access', []),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Package $package */
        $package = $this->route('package');

        return [
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:150', Rule::unique('packages', 'slug')->ignore($package->id)],
            'description' => ['nullable', 'string', 'max:5000'],
            'module_access' => ['nullable', 'array'],
            'module_access.*' => ['string', Rule::in(IeltsModule::values())],
            'attempt_limits' => ['nullable', 'array'],
            'attempt_limits.reading' => ['nullable', 'integer', 'min:0'],
            'attempt_limits.listening' => ['nullable', 'integer', 'min:0'],
            'attempt_limits.writing' => ['nullable', 'integer', 'min:0'],
            'attempt_limits.speaking' => ['nullable', 'integer', 'min:0'],
            'billing_interval' => ['required', 'string', Rule::in(BillingInterval::values())],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'discount_type' => ['required', 'string', Rule::in(PackageDiscountType::values())],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'trial_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'duration_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'is_active' => ['nullable', 'boolean'],
            'is_public' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'string', Rule::in(PackageStatus::values())],
            'course_ids' => ['nullable', 'array'],
            'course_ids.*' => ['integer', 'exists:courses,id'],
        ];
    }
}
