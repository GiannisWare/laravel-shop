<?php

use App\Models\User;

it('prevents non-admin users from accessing the admin panel', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin')
        ->assertForbidden();
});

it('allows admin users to access the admin panel', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk();
});

it('allows admins to render each catalog resource index', function (string $path) {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get($path)
        ->assertOk();
})->with([
    '/admin/products',
    '/admin/categories',
    '/admin/brands',
    '/admin/attributes',
]);
