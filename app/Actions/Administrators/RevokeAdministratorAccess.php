<?php

namespace App\Actions\Administrators;

use App\Models\User;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final class RevokeAdministratorAccess
{
    public function handle(User $actor, User $administrator): User
    {
        if (! $actor->isSuperAdmin()) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($actor, $administrator): User {
            $administrator = User::query()
                ->whereKey($administrator->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $superAdministrators = User::query()
                ->where('is_admin', true)
                ->where('is_super_admin', true)
                ->lockForUpdate()
                ->get();

            if ($actor->is($administrator)) {
                throw new DomainException('You cannot revoke your own admin access.');
            }

            if ($administrator->isSuperAdmin() && $superAdministrators->count() === 1) {
                throw new DomainException('The last super admin cannot be revoked.');
            }

            $administrator->is_admin = false;
            $administrator->is_super_admin = false;
            $administrator->save();

            return $administrator;
        });
    }
}
