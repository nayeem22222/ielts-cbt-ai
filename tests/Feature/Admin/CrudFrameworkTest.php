<?php

declare(strict_types=1);

use App\Crud\CrudQuery;
use App\Enums\Auth\UserRole;
use App\Enums\Auth\UserStatus;
use App\Models\User;
use App\Services\Admin\UserCrudService;
use Illuminate\Http\UploadedFile;

beforeEach(function (): void {
    seedRbac();
});

it('paginates users with search filter and sorting', function (): void {
    createUserWithRole(UserRole::Student, ['name' => 'Alpha Student', 'email' => 'alpha@example.com']);
    createUserWithRole(UserRole::Teacher, ['name' => 'Beta Teacher', 'email' => 'beta@example.com']);

    $service = app(UserCrudService::class);
    $results = $service->paginate(new CrudQuery(
        search: 'Alpha',
        sort: 'name',
        direction: 'asc',
    ));

    expect($results->total())->toBe(1)
        ->and($results->first()->email)->toBe('alpha@example.com');
});

it('soft deletes and restores users through crud service', function (): void {
    $user = createUserWithRole(UserRole::Student, ['email' => 'crud-soft@example.com']);
    $service = app(UserCrudService::class);

    expect($service->delete($user))->toBeTrue();
    expect(User::query()->where('email', 'crud-soft@example.com')->exists())->toBeFalse();

    $trashed = $service->findOrFail($user->id, true);
    expect($service->restore($trashed))->toBeTrue();
    expect(User::query()->where('email', 'crud-soft@example.com')->exists())->toBeTrue();
});

it('exports users as csv from admin route', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'export-admin@example.com',
        'email_verified_at' => now(),
    ]);

    createUserWithRole(UserRole::Student, ['email' => 'export-student@example.com']);

    $response = $this->actingAs($admin)->get(route('admin.users.export'));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('text/csv');
    expect($response->streamedContent())->toContain('export-student@example.com');
});

it('imports users from csv spreadsheet', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'import-admin@example.com',
        'email_verified_at' => now(),
    ]);

    $file = UploadedFile::fake()->createWithContent(
        'users.csv',
        "name,email,phone,status,role\nImported User,imported-user@example.com,01700000001,active,student\n"
    );

    $response = $this->actingAs($admin)->post(route('admin.users.import'), [
        'file' => $file,
    ]);

    $response->assertRedirect(route('admin.users.index'));
    expect(User::query()->where('email', 'imported-user@example.com')->exists())->toBeTrue();
});

it('runs bulk delete on selected users', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'bulk-admin@example.com',
        'email_verified_at' => now(),
    ]);

    $student = createUserWithRole(UserRole::Student, ['email' => 'bulk-student@example.com']);

    $this->actingAs($admin)
        ->post(route('admin.users.bulk'), [
            'action' => 'delete',
            'ids' => [$student->id],
        ])
        ->assertRedirect();

    expect(User::withTrashed()->where('id', $student->id)->first()?->trashed())->toBeTrue();
});

it('lists trashed users and restores from trash route', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'trash-admin@example.com',
        'email_verified_at' => now(),
    ]);

    $student = createUserWithRole(UserRole::Student, ['email' => 'trash-student@example.com']);
    $student->delete();

    $this->actingAs($admin)
        ->get(route('admin.users.trash'))
        ->assertOk()
        ->assertSee('trash-student@example.com')
        ->assertSee('data-crud-toolbar', false);

    $this->actingAs($admin)
        ->put(route('admin.users.restore', $student->id))
        ->assertRedirect();

    expect(User::query()->where('email', 'trash-student@example.com')->exists())->toBeTrue();
});

it('renders crud toolbar on users index', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'toolbar-admin@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('data-crud-toolbar', false)
        ->assertSee('Export CSV')
        ->assertSee('Import Excel')
        ->assertSee(route('admin.users.trash'), false);
});
