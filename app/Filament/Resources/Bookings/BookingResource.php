<?php

namespace App\Filament\Resources\Bookings;

use App\Filament\Resources\Bookings\Pages\ManageBookings;
use App\Models\Booking;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationLabel = 'Bookinglər';

    protected static ?string $modelLabel = 'booking';

    protected static ?string $pluralModelLabel = 'Bookinglər';

    protected static ?int $navigationSort = 8;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('client.name')->label('Müştəri')->searchable(),
                TextColumn::make('provider.name')->label('Provayder')->searchable(),
                TextColumn::make('providerProfile.title')->label('Profil'),
                TextColumn::make('scheduled_at')->label('Vaxt')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('price_azn')->label('Qiymət')->suffix(' AZN'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'completed' => 'Bitib',
                        'cancelled' => 'Ləğv',
                        default => 'Planlaşdırılıb',
                    }),
            ])
            ->defaultSort('scheduled_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBookings::route('/'),
        ];
    }
}
