<?php

declare(strict_types=1);
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
| The closure you provide to your test functions is always bound to a
| specific PHPUnit test case class. By default, that class is
| "PHPUnit\Framework\TestCase". Of course, you may need to change it if
| you wish to use a feature from one of the built-in TestCase classes.
*/

uses(TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class)->in('Feature');

function createUserWithRole(\App\Enums\Auth\UserRole $role, array $attributes = []): \App\Models\User
{
    $user = \App\Models\User::factory()->create($attributes);
    $user->assignRole($role);

    if ($role === \App\Enums\Auth\UserRole::Student) {
        $user->studentProfile()->create([]);
    }

    return $user->fresh(['roles']);
}

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
| When you're writing tests, you often need to check that values meet
| certain conditions. The "expect()" function gives you access to a set
| of "expectations" methods that you can use to assert different things.
| Of course, you may extend the Expectation API at any time.
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
| While Pest is very powerful out-of-the-box, you may have test helpers
| that you wish to re-use in multiple test files. Here you can also
| specify your "global" helper functions to avoid re-writing common
| test helpers throughout your test suite.
*/
