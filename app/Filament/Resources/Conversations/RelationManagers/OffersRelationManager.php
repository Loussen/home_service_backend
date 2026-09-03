<?php

namespace App\Filament\Resources\Conversations\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OffersRelationManager extends RelationManager
{
    protected static string $relationship = 'offers';

    protected static ?string $title = 'Təkliflər';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['proposer', 'reviews']))
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('proposer.name')
                    ->label('Təklif edən')
                    ->placeholder('—'),
                TextColumn::make('scheduled_at')
                    ->label('Vaxt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                TextColumn::make('duration_hours')
                    ->label('Müddət')
                    ->suffix(' saat')
                    ->placeholder('—'),
                TextColumn::make('price_azn')
                    ->label('Qiymət')
                    ->suffix(' AZN')
                    ->sortable(),
                TextColumn::make('note')
                    ->label('Qeyd')
                    ->limit(40)
                    ->placeholder('—')
                    ->wrap(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'accepted', 'completed' => 'success',
                        'declined', 'cancelled' => 'danger',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Gözləyir',
                        'accepted' => 'Qəbul olunub',
                        'declined' => 'Rədd edilib',
                        'completed' => 'Tamamlanıb',
                        'cancelled' => 'Ləğv edilib',
                        default => $state,
                    }),
                TextColumn::make('accepted_at')
                    ->label('Qəbul')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('completed_at')
                    ->label('Tamamlanma')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Göndərilib')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Gözləyir',
                        'accepted' => 'Qəbul',
                        'declined' => 'Rədd',
                        'completed' => 'Tamamlanıb',
                        'cancelled' => 'Ləğv',
                    ]),
            ]);
    }
}
