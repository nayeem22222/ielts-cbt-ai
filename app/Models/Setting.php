<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Settings\SettingsGroup;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'group',
        'key',
        'value',
        'is_encrypted',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_encrypted' => 'boolean',
        ];
    }

    public function groupEnum(): SettingsGroup
    {
        return SettingsGroup::from($this->group);
    }
}
