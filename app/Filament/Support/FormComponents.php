<?php

namespace App\Filament\Support;

use App\Models\ProviderProfile;
use App\Models\User;
use Filament\Forms\Components\Select;

final class FormComponents
{
    public static function userSelect(string $name = 'user_id', string $label = 'İstifadəçi'): Select
    {
        return Select::make($name)
            ->label($label)
            ->relationship('user', 'phone')
            ->getOptionLabelFromRecordUsing(
                fn (User $record): string => filled($record->name)
                    ? "{$record->name} · {$record->phone}"
                    : (string) $record->phone,
            )
            ->searchable(['name', 'phone'])
            ->preload()
            ->required();
    }

    public static function providerProfileSelect(string $name = 'provider_profile_id'): Select
    {
        return Select::make($name)
            ->label('Profil')
            ->relationship('providerProfile', 'id')
            ->getOptionLabelFromRecordUsing(
                fn (ProviderProfile $record): string => filled($record->title)
                    ? (string) $record->title
                    : "Profil #{$record->id}",
            )
            ->searchable(['title'])
            ->preload();
    }
}
