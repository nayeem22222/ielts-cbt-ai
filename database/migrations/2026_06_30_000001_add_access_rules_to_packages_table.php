<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table): void {
            $table->json('module_access')->nullable()->after('description');
            $table->json('attempt_limits')->nullable()->after('module_access');
            $table->string('discount_type', 20)->default('none')->after('currency');
            $table->decimal('discount_value', 10, 2)->default(0)->after('discount_type');
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table): void {
            $table->dropColumn(['module_access', 'attempt_limits', 'discount_type', 'discount_value']);
        });
    }
};
