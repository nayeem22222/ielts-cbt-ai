<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Commerce\IeltsModule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuleAttemptUsage extends Model
{
    protected $fillable = [
        'user_id',
        'student_package_id',
        'module',
        'attempt_count',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'module' => IeltsModule::class,
            'attempt_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function studentPackage(): BelongsTo
    {
        return $this->belongsTo(StudentPackage::class);
    }
}
