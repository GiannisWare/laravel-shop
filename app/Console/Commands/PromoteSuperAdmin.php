<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('admin:promote {email : Email address of the existing user} {--force : Skip the production confirmation}')]
#[Description('Grant admin and super-admin access to an existing user')]
class PromoteSuperAdmin extends Command
{
    public function handle(): int
    {
        $email = $this->argument('email');

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $this->components->error("No user exists with the email {$email}.");

            return self::FAILURE;
        }

        if (app()->isProduction() && ! $this->option('force') && ! $this->confirm("Grant super-admin access to {$email}?")) {
            $this->components->warn('No changes were made.');

            return self::FAILURE;
        }

        $user->is_admin = true;
        $user->is_super_admin = true;
        $user->save();

        $this->components->info("{$email} is now a super admin.");

        return self::SUCCESS;
    }
}
