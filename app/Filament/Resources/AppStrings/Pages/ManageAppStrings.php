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
                    'Yeni açarlar əlavə olunur. EN/RU-da hələ AZ qalan köhnə sətirlər '
                    .'avtomatik fayldan düzəldilir. Əl ilə dəyişdiyiniz unikal mətnlər saxlanır.'
                )
                ->modalSubmitActionLabel('Sinxronla')
                ->action(function (AppStringSyncService $sync): void {
                    $result = $sync->syncFromFiles(overwriteExisting: false);

                    Notification::make()
                        ->title('Sinxron tamamlandı')
                        ->body(sprintf(
                            'Yeni: %d · AZ-copy düzəliş: %d · Cəmi DB: %d',
                            $result['created'],
                            $result['fixed_copies'],
                            $result['total'],
                        ))
                        ->success()
                        ->send();
                }),
            Action::make('syncOverwrite')
                ->label('Fayllarla tam yenilə')
                ->icon('heroicon-o-arrow-path-rounded-square')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Bütün mətnləri fayldan yaz?')
                ->modalDescription(
                    'az/en/ru PHP faylları DB-dəki bütün eyni açarları overwrite edir. '
                    .'Admin panelində əl ilə yazdığınız fərqli mətnlər silinə bilər.'
                )
                ->modalSubmitActionLabel('Overwrite et')
                ->action(function (AppStringSyncService $sync): void {
                    $result = $sync->syncFromFiles(overwriteExisting: true);

                    Notification::make()
                        ->title('Tam sinxron tamamlandı')
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
