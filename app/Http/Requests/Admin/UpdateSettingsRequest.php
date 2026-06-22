<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\Settings\SettingsGroup;
use App\Support\Settings\SettingsSchema;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('settings.update');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $group = SettingsGroup::tryFrom((string) $this->route('group'));

        if ($group === null) {
            return [];
        }

        $rules = [];

        foreach (SettingsSchema::fields($group) as $key => $meta) {
            $rules[$key] = $meta['rules'];
        }

        return $rules;
    }

    /**
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated($key, $default);

        if (! is_array($validated)) {
            return [];
        }

        $group = SettingsGroup::from((string) $this->route('group'));

        foreach (SettingsSchema::fields($group) as $field => $meta) {
            if (! array_key_exists($field, $validated)) {
                continue;
            }

            $default = SettingsSchema::defaults($group)[$field] ?? null;

            if (is_bool($default)) {
                $validated[$field] = $this->boolean($field);
            }
        }

        return $validated;
    }

    protected function prepareForValidation(): void
    {
        $group = SettingsGroup::tryFrom((string) $this->route('group'));

        if ($group === null) {
            return;
        }

        $booleanFields = [];

        foreach (SettingsSchema::defaults($group) as $key => $default) {
            if (is_bool($default)) {
                $booleanFields[] = $key;
            }
        }

        if ($booleanFields !== []) {
            $this->merge([
                ...collect($booleanFields)->mapWithKeys(fn (string $field): array => [
                    $field => $this->boolean($field),
                ])->all(),
            ]);
        }
    }
}
