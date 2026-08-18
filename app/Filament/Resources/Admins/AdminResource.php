<?php

namespace App\Filament\Resources\Admins;

use App\Filament\Resources\Admins\Pages\ManageAdmins;
use App\Models\Admin;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AdminResource extends Resource
{
    protected static ?string $model = Admin::class;

    protected static ?string $navigationLabel = 'Adminlər';

    protected static ?string $modelLabel = 'admin';

    protected static ?string $pluralModelLabel = 'Adminlər';

    protected static ?int $navigationSort = 10;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Ad')
                    ->required(),
                TextInput::make('email')
                    ->label('E-poçt')
                    ->email()
                    ->required(),
                TextInput::make('password')
                    ->label('Şifrə')
                    ->password()
                    ->revealable()
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->helperText('Boş buraxsanız, mövcud şifrə dəyişməz.'),
                Toggle::make('is_active')
                    ->label('Aktiv'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Ad')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('E-poçt')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean(),
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
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAdmins::route('/'),
        ];
    }
}
