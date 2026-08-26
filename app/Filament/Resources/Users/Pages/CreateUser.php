<?php

namespace App\Filament\Resources\Users\Pages;

use App\Actions\Administrators\CreateAdministrator;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $actor = Filament::auth()->user();

        abort_unless($actor instanceof User, 403);
        Gate::authorize('create', User::class);

        return app(CreateAdministrator::class)->handle($actor, $data);
    }
}
