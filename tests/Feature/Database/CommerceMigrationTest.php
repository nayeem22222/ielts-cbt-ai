<?php

declare(strict_types=1);

use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

it('creates commerce tables with expected columns', function (): void {
    $tables = [
        'course_categories',
        'courses',
        'packages',
        'course_package',
        'student_packages',
        'orders',
        'order_items',
        'payments',
        'payment_logs',
        'coupons',
        'coupon_usages',
        'invoices',
    ];

    foreach ($tables as $table) {
        expect(Schema::hasTable($table))->toBeTrue("Missing table: {$table}");
    }

    expect(Schema::hasColumn('courses', 'uuid'))->toBeTrue();
    expect(Schema::hasColumn('packages', 'stripe_price_id'))->toBeTrue();
    expect(Schema::hasColumn('orders', 'order_number'))->toBeTrue();
    expect(Schema::hasColumn('payment_logs', 'payload'))->toBeTrue();
});

it('supports course package and payment relationships', function (): void {
    $student = createUserWithRole(\App\Enums\Auth\UserRole::Student, [
        'email' => 'commerce-student@example.com',
    ]);

    $categoryId = DB::table('course_categories')->insertGetId([
        'name' => 'Academic IELTS',
        'slug' => 'academic-ielts',
        'status' => 'active',
        'sort_order' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $courseId = DB::table('courses')->insertGetId([
        'uuid' => (string) Str::uuid(),
        'course_category_id' => $categoryId,
        'slug' => 'complete-ielts-course',
        'title' => 'Complete IELTS Course',
        'exam_type' => 'academic',
        'level' => 'intermediate',
        'status' => 'published',
        'sort_order' => 1,
        'published_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $packageId = DB::table('packages')->insertGetId([
        'uuid' => (string) Str::uuid(),
        'slug' => 'pro-monthly',
        'name' => 'Pro Monthly',
        'billing_interval' => 'monthly',
        'price' => 29.99,
        'currency' => 'USD',
        'is_active' => true,
        'is_public' => true,
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('course_package')->insert([
        'course_id' => $courseId,
        'package_id' => $packageId,
        'sort_order' => 1,
        'is_featured' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $couponId = DB::table('coupons')->insertGetId([
        'code' => 'WELCOME10',
        'name' => 'Welcome 10',
        'discount_type' => 'percent',
        'discount_value' => 10,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $orderId = DB::table('orders')->insertGetId([
        'uuid' => (string) Str::uuid(),
        'order_number' => 'ORD-'.Str::upper(Str::random(8)),
        'user_id' => $student->id,
        'coupon_id' => $couponId,
        'status' => 'paid',
        'subtotal' => 29.99,
        'discount_amount' => 3.00,
        'tax_amount' => 0,
        'total' => 26.99,
        'currency' => 'USD',
        'paid_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('order_items')->insert([
        'order_id' => $orderId,
        'item_type' => 'package',
        'item_id' => $packageId,
        'name' => 'Pro Monthly',
        'quantity' => 1,
        'unit_price' => 29.99,
        'total_price' => 29.99,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $studentPackageId = DB::table('student_packages')->insertGetId([
        'user_id' => $student->id,
        'package_id' => $packageId,
        'order_id' => $orderId,
        'status' => 'active',
        'starts_at' => now(),
        'expires_at' => now()->addMonth(),
        'activated_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $paymentId = DB::table('payments')->insertGetId([
        'uuid' => (string) Str::uuid(),
        'order_id' => $orderId,
        'user_id' => $student->id,
        'amount' => 26.99,
        'currency' => 'USD',
        'status' => 'succeeded',
        'payment_method' => 'card',
        'gateway' => 'stripe',
        'gateway_payment_id' => 'pi_test_123',
        'paid_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('payment_logs')->insert([
        'payment_id' => $paymentId,
        'event_type' => 'payment.succeeded',
        'status' => 'succeeded',
        'message' => 'Payment captured successfully.',
        'payload' => json_encode(['gateway' => 'stripe']),
        'ip_address' => '127.0.0.1',
        'created_at' => now(),
    ]);

    DB::table('coupon_usages')->insert([
        'coupon_id' => $couponId,
        'user_id' => $student->id,
        'order_id' => $orderId,
        'discount_applied' => 3.00,
        'used_at' => now(),
        'created_at' => now(),
    ]);

    DB::table('invoices')->insert([
        'uuid' => (string) Str::uuid(),
        'invoice_number' => 'INV-'.Str::upper(Str::random(8)),
        'order_id' => $orderId,
        'payment_id' => $paymentId,
        'status' => 'paid',
        'subtotal' => 29.99,
        'discount_amount' => 3.00,
        'tax_amount' => 0,
        'total' => 26.99,
        'currency' => 'USD',
        'billing_name' => $student->name,
        'billing_email' => $student->email,
        'issued_at' => now(),
        'paid_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(DB::table('course_package')->where('course_id', $courseId)->count())->toBe(1)
        ->and(DB::table('student_packages')->where('id', $studentPackageId)->value('status'))->toBe('active')
        ->and(DB::table('payments')->where('id', $paymentId)->value('status'))->toBe('succeeded')
        ->and(DB::table('payment_logs')->where('payment_id', $paymentId)->count())->toBe(1)
        ->and(DB::table('invoices')->where('order_id', $orderId)->count())->toBe(1);
});

it('soft deletes catalog entities without breaking foreign keys', function (): void {
    $categoryId = DB::table('course_categories')->insertGetId([
        'name' => 'General Training',
        'slug' => 'general-training',
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $courseId = DB::table('courses')->insertGetId([
        'uuid' => (string) Str::uuid(),
        'course_category_id' => $categoryId,
        'slug' => 'gt-course',
        'title' => 'GT Course',
        'status' => 'draft',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('courses')->where('id', $courseId)->update(['deleted_at' => now()]);
    DB::table('course_categories')->where('id', $categoryId)->update(['deleted_at' => now()]);

    expect(DB::table('courses')->where('id', $courseId)->whereNotNull('deleted_at')->exists())->toBeTrue()
        ->and(DB::table('course_categories')->where('id', $categoryId)->whereNotNull('deleted_at')->exists())->toBeTrue();
});
