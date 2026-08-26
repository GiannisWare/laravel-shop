<?php

namespace App\Actions\Administrators;

use App\Models\User;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class UpdateAdministrator
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(User $actor, User $administrator, array $data): User
    {
        if (! $actor->isSuperAdmin()) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($actor, $administrator, $data): User {
            $administrator = User::query()
                ->whereKey($administrator->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $superAdministrators = User::query()
                ->where('is_admin', true)
                ->where('is_super_admin', true)
                ->lockForUpdate()
                ->get();

            $name = $data['name'] ?? null;
            $email = $data['email'] ?? null;
            $password = $data['password'] ?? null;
            $willBeSuperAdministrator = $data['is_super_admin'] ?? $administrator->is_super_admin;

            if (! is_string($name) || ! is_string($email) || (! is_null($password) && ! is_string($password)) || ! is_bool($willBeSuperAdministrator)) {
                throw new InvalidArgumentException('Invalid administrator data.');
            }

            if ($actor->is($administrator)) {
                $willBeSuperAdministrator = true;
            }

            if ($administrator->isSuperAdmin() && ! $willBeSuperAdministrator && $superAdministrators->count() === 1) {
                throw new DomainException('The last super admin cannot be demoted.');
            }

            $administrator->name = $name;
            $administrator->email = $email;

            if (filled($password)) {
                $administrator->password = $password;
            }

            $administrator->is_admin = true;
            $administrator->is_super_admin = $willBeSuperAdministrator;
            $administrator->save();

            return $administrator;
        });
    }
}
