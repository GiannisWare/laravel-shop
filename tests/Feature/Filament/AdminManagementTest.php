<?php

use App\Actions\Administrators\RevokeAdministratorAccess;
use App\Actions\Administrators\UpdateAdministrator;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Favorite;
use App\Models\ProductHold;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('redirects the application root to the configured admin panel', function () {
    $this->get('/')->assertRedirect('/admin');
});

it('prevents regular admins from managing administrators', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/admins')
        ->assertForbidden();
});

it('shows only administrators to super admins', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();

    Livewire::actingAs($superAdmin)
        ->test(ListUsers::class)
        ->assertCanSeeTableRecords([$superAdmin, $admin])
        ->assertCanNotSeeTableRecords([$customer]);
});

it('allows a super admin to create an administrator', function () {
    $superAdmin = User::factory()->superAdmin()->create();

    Livewire::actingAs($superAdmin)
        ->test(CreateUser::class)
        ->fillForm([
            'name' => 'Catalogue Admin',
            'email' => 'catalogue@example.com',
            'password' => 'Secure-password-123',
            'passwordConfirmation' => 'Secure-password-123',
            'is_super_admin' => false,
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    $administrator = User::query()->where('email', 'catalogue@example.com')->sole();

    expect($administrator->is_admin)->toBeTrue()
        ->and($administrator->is_super_admin)->toBeFalse()
        ->and(Hash::check('Secure-password-123', $administrator->password))->toBeTrue();
});

it('preserves the password when an administrator is updated without a new one', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $administrator = User::factory()->admin()->create();
    $originalPassword = $administrator->password;

    app(UpdateAdministrator::class)->handle($superAdmin, $administrator, [
        'name' => 'Updated Admin',
        'email' => $administrator->email,
        'password' => null,
        'is_super_admin' => false,
    ]);

    expect($administrator->refresh()->name)->toBe('Updated Admin')
        ->and($administrator->password)->toBe($originalPassword);
});

it('prevents self-demotion and self-revocation', function () {
    $superAdmin = User::factory()->superAdmin()->create();

    app(UpdateAdministrator::class)->handle($superAdmin, $superAdmin, [
        'name' => $superAdmin->name,
        'email' => $superAdmin->email,
        'is_super_admin' => false,
    ]);

    expect($superAdmin->refresh()->isSuperAdmin())->toBeTrue();

    expect(fn () => app(RevokeAdministratorAccess::class)->handle($superAdmin, $superAdmin))
        ->toThrow(DomainException::class, 'You cannot revoke your own admin access.');
});

it('revokes panel access without deleting the account or its customer data', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $administrator = User::factory()->admin()->create();
    $favorite = Favorite::factory()->for($administrator)->create();
    $hold = ProductHold::factory()->for($administrator)->create();

    app(RevokeAdministratorAccess::class)->handle($superAdmin, $administrator);

    expect($administrator->refresh()->is_admin)->toBeFalse()
        ->and($administrator->is_super_admin)->toBeFalse()
        ->and(User::query()->find($administrator->id))->not->toBeNull()
        ->and(Favorite::query()->find($favorite->id))->not->toBeNull()
        ->and(ProductHold::query()->find($hold->id))->not->toBeNull();
});

it('rejects administrator changes from non-super admins at the action boundary', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->admin()->create();

    expect(fn () => app(RevokeAdministratorAccess::class)->handle($admin, $target))
        ->toThrow(AuthorizationException::class);
});
