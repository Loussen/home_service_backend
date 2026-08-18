<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('phone')
                    ->label('Telefon')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Ad')
                    ->searchable(),
                TextColumn::make('active_role')
                    ->label('Rol')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'client' => 'Müştəri',
                        'provider' => 'İcraçı',
                        default => $state,
                    }),
                TextColumn::make('balance')
                    ->label('Balans')
                    ->numeric()
                    ->suffix(' AZN')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Aktiv',
                        'blocked' => 'Bloklanıb',
                        default => $state,
                    }),
                TextColumn::make('created_at')
                    ->label('Yaradılıb')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Yenilənib')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
