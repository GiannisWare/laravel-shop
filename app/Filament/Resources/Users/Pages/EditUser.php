<?php

namespace App\Filament\Resources\Users\Pages;

use App\Actions\Administrators\RevokeAdministratorAccess;
use App\Actions\Administrators\UpdateAdministrator;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use DomainException;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('revokeAccess')
                ->label('Revoke admin access')
                ->icon('phosphor-user-minus')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Revoke admin access?')
                ->modalDescription('The account and its customer data will be preserved, but it will no longer be able to access the admin panel.')
                ->authorize('update')
                ->hidden(fn (User $record): bool => $record->getKey() === Filament::auth()->id())
                ->action(function (): void {
                    $actor = Filament::auth()->user();

                    abort_unless($actor instanceof User && $this->record instanceof User, 403);
                    Gate::authorize('update', $this->record);

                    try {
                        app(RevokeAdministratorAccess::class)->handle($actor, $this->record);
                    } catch (DomainException $exception) {
                        Notification::make()
                            ->title('Admin access was not revoked')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Admin access revoked')
                        ->success()
                        ->send();

                    $this->redirect(UserResource::getUrl('index'));
                }),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $actor = Filament::auth()->user();

        abort_unless($actor instanceof User && $record instanceof User, 403);
        Gate::authorize('update', $record);

        return app(UpdateAdministrator::class)->handle($actor, $record, $data);
    }
}
