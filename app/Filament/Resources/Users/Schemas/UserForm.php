<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('phone')
                    ->label('Telefon')
                    ->tel()
                    ->required(),
                TextInput::make('name')
                    ->label('Ad'),
                TextInput::make('avatar_url')
                    ->label('Avatar URL')
                    ->url(),
                Select::make('active_role')
                    ->label('Rol')
                    ->options(['client' => 'Müştəri', 'provider' => 'İcraçı'])
                    ->default('client')
                    ->required(),
                TextInput::make('balance')
                    ->label('Balans')
                    ->required()
                    ->numeric()
                    ->suffix('AZN')
                    ->default(0.0),
                Select::make('status')
                    ->label('Status')
                    ->options(['active' => 'Aktiv', 'blocked' => 'Bloklanıb'])
                    ->default('active')
                    ->required(),
                Toggle::make('welcome_bonus_granted')
                    ->label('Xoşgəldin bonusu verilib'),
                DateTimePicker::make('phone_verified_at')
                    ->label('Telefon təsdiqi'),
            ]);
    }
}
