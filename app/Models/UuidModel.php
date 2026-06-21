<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

/**
 * Base model for entities that use UUID primary keys.
 */
abstract class UuidModel extends Model
{
    use HasUuid;
}
