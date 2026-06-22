@php
    $selectedModules = old('module_access', $package->module_access ?? array_map(fn ($m) => $m->value, $modules));
    $limits = old('attempt_limits', $package->attempt_limits ?? []);
    $selectedCourses = $selectedCourses ?? old('course_ids', []);
@endphp

<div class="grid gap-4 md:grid-cols-2">
    <x-ui.input name="name" label="Package Name" :value="old('name', $package->name ?? '')" required class="md:col-span-2" />
    <x-ui.input name="slug" label="Slug" :value="old('slug', $package->slug ?? '')" required />
    <x-ui.select name="billing_interval" label="Billing Interval">
        @foreach ($intervals as $interval)
            <option value="{{ $interval->value }}" @selected(old('billing_interval', $package->billing_interval->value ?? 'monthly') === $interval->value)>{{ $interval->label() }}</option>
        @endforeach
    </x-ui.select>
    <x-ui.select name="status" label="Status">
        @foreach ($statuses as $status)
            <option value="{{ $status->value }}" @selected(old('status', $package->status->value ?? 'active') === $status->value)>{{ $status->label() }}</option>
        @endforeach
    </x-ui.select>
    <x-ui.input name="price" type="number" step="0.01" label="Price" :value="old('price', $package->price ?? 0)" required />
    <x-ui.input name="currency" label="Currency" maxlength="3" :value="old('currency', $package->currency ?? 'BDT')" required />
    <x-ui.select name="discount_type" label="Discount Type">
        @foreach ($discountTypes as $type)
            <option value="{{ $type->value }}" @selected(old('discount_type', $package->discount_type->value ?? 'none') === $type->value)>{{ $type->label() }}</option>
        @endforeach
    </x-ui.select>
    <x-ui.input name="discount_value" type="number" step="0.01" label="Discount Value" :value="old('discount_value', $package->discount_value ?? 0)" help="Percent or fixed amount depending on discount type" />
    <x-ui.input name="duration_days" type="number" label="Duration (days)" :value="old('duration_days', $package->duration_days ?? '')" help="Leave empty for unlimited access" />
    <x-ui.input name="trial_days" type="number" label="Trial Days" :value="old('trial_days', $package->trial_days ?? 0)" />
    <x-ui.input name="sort_order" type="number" label="Sort Order" :value="old('sort_order', $package->sort_order ?? 0)" />
    <x-ui.textarea name="description" label="Description" class="md:col-span-2" rows="3">{{ old('description', $package->description ?? '') }}</x-ui.textarea>
</div>

<x-ui.card title="Module Access Rules" subtitle="Select which IELTS modules this package unlocks" class="mt-6">
    <div class="grid gap-3 md:grid-cols-2">
        @foreach ($modules as $module)
            <x-ui.checkbox name="module_access[]" value="{{ $module->value }}" :checked="in_array($module->value, $selectedModules, true)">
                {{ $module->label() }}
            </x-ui.checkbox>
        @endforeach
    </div>
</x-ui.card>

<x-ui.card title="Attempt Limits" subtitle="Leave blank for unlimited attempts per module" class="mt-6">
    <div class="grid gap-4 md:grid-cols-2">
        @foreach ($modules as $module)
            <x-ui.input
                name="attempt_limits[{{ $module->value }}]"
                type="number"
                :label="$module->label().' attempts'"
                :value="old('attempt_limits.'.$module->value, $limits[$module->value] ?? '')"
                min="0"
            />
        @endforeach
    </div>
</x-ui.card>

<x-ui.card title="Included Courses" subtitle="Optional course bundle for this package" class="mt-6">
    <div class="grid gap-2 md:grid-cols-2">
        @foreach ($courses as $course)
            <x-ui.checkbox name="course_ids[]" value="{{ $course->id }}" :checked="in_array($course->id, $selectedCourses, true)">
                {{ $course->title }}
            </x-ui.checkbox>
        @endforeach
    </div>
</x-ui.card>

<div class="mt-6 flex flex-wrap gap-4">
    <x-ui.checkbox name="is_active" value="1" :checked="old('is_active', $package->is_active ?? true)">Active for purchase</x-ui.checkbox>
    <x-ui.checkbox name="is_public" value="1" :checked="old('is_public', $package->is_public ?? true)">Visible on public catalog</x-ui.checkbox>
</div>

<div class="mt-6 flex gap-3">
    <x-ui.button type="submit">{{ $submitLabel }}</x-ui.button>
    <x-ui.button href="{{ route('admin.packages.index') }}" variant="outline">Cancel</x-ui.button>
</div>
