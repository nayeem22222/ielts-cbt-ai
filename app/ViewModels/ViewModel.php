<?php

declare(strict_types=1);

namespace App\ViewModels;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, mixed>
 */
abstract class ViewModel implements Arrayable
{
    /**
     * @return array<string, mixed>
     */
    abstract public function toArray(): array;
}
