<?php

use App\Models\User;
use App\Policies\UserPolicy;

it('only allows super admins to manage administrators', function () {
    $policy = new UserPolicy;
    $customer = User::factory()->make();
    $admin = User::factory()->admin()->make();
    $superAdmin = User::factory()->superAdmin()->make();
    $target = User::factory()->admin()->make();

    expect($policy->viewAny($customer))->toBeFalse()
        ->and($policy->viewAny($admin))->toBeFalse()
        ->and($policy->viewAny($superAdmin))->toBeTrue()
        ->and($policy->view($superAdmin, $target))->toBeTrue()
        ->and($policy->create($superAdmin))->toBeTrue()
        ->and($policy->update($superAdmin, $target))->toBeTrue();
});

it('never authorizes destructive user operations', function () {
    $policy = new UserPolicy;
    $superAdmin = User::factory()->superAdmin()->make();
    $target = User::factory()->admin()->make();

    expect($policy->delete($superAdmin, $target))->toBeFalse()
        ->and($policy->deleteAny($superAdmin))->toBeFalse()
        ->and($policy->restore($superAdmin, $target))->toBeFalse()
        ->and($policy->restoreAny($superAdmin))->toBeFalse()
        ->and($policy->forceDelete($superAdmin, $target))->toBeFalse()
        ->and($policy->forceDeleteAny($superAdmin))->toBeFalse()
        ->and($policy->replicate($superAdmin, $target))->toBeFalse()
        ->and($policy->reorder($superAdmin))->toBeFalse();
});
