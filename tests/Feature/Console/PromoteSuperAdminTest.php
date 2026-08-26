<?php

use App\Models\User;

it('promotes an existing user to super admin', function () {
    $user = User::factory()->create();

    $this->artisan('admin:promote', ['email' => $user->email])
        ->expectsOutputToContain('is now a super admin')
        ->assertSuccessful();

    expect($user->refresh()->is_admin)->toBeTrue()
        ->and($user->is_super_admin)->toBeTrue();
});

it('fails when the user does not exist', function () {
    $this->artisan('admin:promote', ['email' => 'missing@example.com'])
        ->expectsOutputToContain('No user exists')
        ->assertFailed();
});
