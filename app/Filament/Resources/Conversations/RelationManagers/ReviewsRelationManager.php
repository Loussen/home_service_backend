<?php

namespace App\Filament\Resources\Conversations\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReviewsRelationManager extends RelationManager
{
    protected static string $relationship = 'reviews';

    protected static ?string $title = 'Rəylər';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['reviewer', 'reviewee', 'offer']))
            ->columns([
                TextColumn::make('created_at')
                    ->label('Tarix')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                TextColumn::make('reviewer.name')
                    ->label('Kim yazıb')
                    ->placeholder('—'),
                TextColumn::make('reviewee.name')
                    ->label('Kimə')
                    ->placeholder('—'),
                TextColumn::make('rating')
                    ->label('Ulduz')
                    ->formatStateUsing(fn ($state): string => str_repeat('★', (int) $state).str_repeat('☆', max(0, 5 - (int) $state)))
                    ->sortable(),
                TextColumn::make('comment')
                    ->label('Rəy')
                    ->wrap()
                    ->placeholder('—')
                    ->limit(120),
                TextColumn::make('offer_id')
                    ->label('Təklif #')
                    ->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
