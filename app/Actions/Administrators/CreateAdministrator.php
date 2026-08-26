<?php

namespace App\Actions\Administrators;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use InvalidArgumentException;

final class CreateAdministrator
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(User $actor, array $data): User
    {
        if (! $actor->isSuperAdmin()) {
            throw new AuthorizationException;
        }

        $name = $data['name'] ?? null;
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;
        $isSuperAdministrator = $data['is_super_admin'] ?? false;

        if (! is_string($name) || ! is_string($email) || ! is_string($password) || ! is_bool($isSuperAdministrator)) {
            throw new InvalidArgumentException('Invalid administrator data.');
        }

        $administrator = new User;
        $administrator->name = $name;
        $administrator->email = $email;
        $administrator->password = $password;
        $administrator->is_admin = true;
        $administrator->is_super_admin = $isSuperAdministrator;
        $administrator->save();

        return $administrator;
    }
}
