<?php

namespace App\Filament\Resources\Conversations\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    protected static ?string $title = 'Mesajlar';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['sender', 'offer']))
            ->columns([
                TextColumn::make('created_at')
                    ->label('Vaxt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                TextColumn::make('sender.name')
                    ->label('Kim')
                    ->description(fn ($record) => $record->sender?->phone)
                    ->placeholder('Sistem')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Növ')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'offer' => 'Təklif',
                        'system' => 'Sistem',
                        default => 'Mətn',
                    }),
                TextColumn::make('body')
                    ->label('Mətn')
                    ->wrap()
                    ->limit(120)
                    ->searchable(),
                TextColumn::make('offer.status')
                    ->label('Təklif status')
                    ->badge()
                    ->placeholder('—')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'pending' => 'Gözləyir',
                        'accepted' => 'Qəbul',
                        'declined' => 'Rədd',
                        'completed' => 'Tamamlanıb',
                        'cancelled' => 'Ləğv',
                        default => $state ?? '—',
                    }),
            ])
            ->defaultSort('created_at', 'asc')
            ->paginated([25, 50, 100]);
    }
}
