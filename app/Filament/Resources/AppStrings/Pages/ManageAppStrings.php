<?php

namespace App\Filament\Resources\AppStrings\Pages;

use App\Filament\Resources\AppStrings\AppStringResource;
use App\Services\AppStringSyncService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;

class ManageAppStrings extends ManageRecords
{
    protected static string $resource = AppStringResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncFromFiles')
                ->label('Fayllardan sinxronla')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Fayllardan sinxronla?')
                ->modalDescription(
                    'Fayllardakı yeni açarlar (az/en/ru) DB-yə əlavə olunur. '
                    . 'Artıq redaktə etdiyiniz mətnlər dəyişmir.'
                )
                ->modalSubmitActionLabel('Sinxronla')
                ->action(function (AppStringSyncService $sync): void {
                    $result = $sync->syncFromFiles();

                    Notification::make()
                        ->title('Sinxron tamamlandı')
                        ->body(sprintf(
                            'Yeni: %d · Yenilənən: %d · Cəmi DB: %d',
                            $result['created'],
                            $result['updated'],
                            $result['total'],
                        ))
                        ->success()
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}
