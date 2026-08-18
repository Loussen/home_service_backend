<?php

namespace App\Filament\Resources\Transactions;

use App\Filament\Support\FormComponents;
use App\Filament\Resources\Transactions\Pages\ManageTransactions;
use App\Models\Transaction;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationLabel = 'Tranzaksiyalar';

    protected static ?string $modelLabel = 'tranzaksiya';

    protected static ?string $pluralModelLabel = 'Tranzaksiyalar';

    protected static ?int $navigationSort = 6;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FormComponents::userSelect(),
                TextInput::make('amount')
                    ->label('Məbləğ')
                    ->required()
                    ->numeric()
                    ->suffix('AZN'),
                Select::make('type')
                    ->label('Növ')
                    ->options([
                        'credit_welcome_bonus' => 'Xoşgəldin bonusu',
                        'top_up' => 'Balans artırma',
                        'bump_up_fee' => 'Bump haqqı',
                        'urgent_fee' => 'Təcili haqqı',
                        'vip_fee' => 'VIP haqqı',
                        'verified_fee' => 'Təsdiq haqqı',
                    ])
                    ->required(),
                Select::make('payment_method')
                    ->label('Ödəniş üsulu')
                    ->options([
                        'system_bonus' => 'Sistem bonusu',
                        'card' => 'Kart',
                        'terminal' => 'Terminal',
                        'wallet' => 'Pul kisəsi',
                    ])
                    ->required(),
                Select::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Gözləyir',
                        'completed' => 'Tamamlanıb',
                        'failed' => 'Uğursuz',
                    ])
                    ->default('pending')
                    ->required(),
                TextInput::make('reference')
                    ->label('Referans'),
                TextInput::make('meta')
                    ->label('Əlavə məlumat'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('İstifadəçi')
                    ->searchable(),
                TextColumn::make('amount')
                    ->label('Məbləğ')
                    ->numeric()
                    ->suffix(' AZN')
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Növ')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'credit_welcome_bonus' => 'Xoşgəldin bonusu',
                        'top_up' => 'Balans artırma',
                        'bump_up_fee' => 'Bump haqqı',
                        'urgent_fee' => 'Təcili haqqı',
                        'vip_fee' => 'VIP haqqı',
                        'verified_fee' => 'Təsdiq haqqı',
                        default => $state,
                    }),
                TextColumn::make('payment_method')
                    ->label('Ödəniş')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'system_bonus' => 'Sistem',
                        'card' => 'Kart',
                        'terminal' => 'Terminal',
                        'wallet' => 'Pul kisəsi',
                        default => $state,
                    }),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Gözləyir',
                        'completed' => 'Tamamlanıb',
                        'failed' => 'Uğursuz',
                        default => $state,
                    }),
                TextColumn::make('reference')
                    ->label('Referans')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Yaradılıb')
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
            'index' => ManageTransactions::route('/'),
        ];
    }
}
