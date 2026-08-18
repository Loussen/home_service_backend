<?php

namespace App\Filament\Resources\ServiceRequests\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MatchesRelationManager extends RelationManager
{
    protected static string $relationship = 'matches';

    protected static ?string $title = 'Uyğun icraçılar';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['providerProfile.user']))
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('providerProfile.title')
                    ->label('Profil')
                    ->placeholder(fn ($record) => 'Profil #'.$record->provider_profile_id),
                TextColumn::make('providerProfile.user.name')
                    ->label('İcraçı')
                    ->placeholder('—'),
                TextColumn::make('providerProfile.user.phone')
                    ->label('Telefon'),
                TextColumn::make('match_score')
                    ->label('Uyğunluq')
                    ->suffix('%')
                    ->sortable(),
                TextColumn::make('distance_km')
                    ->label('Məsafə')
                    ->suffix(' km')
                    ->sortable(),
                TextColumn::make('score_breakdown')
                    ->label('Bal detalları')
                    ->formatStateUsing(function ($state): string {
                        if (! is_array($state) || $state === []) {
                            return '—';
                        }

                        return collect($state)
                            ->map(fn ($v, $k) => $k.': '.$v)
                            ->implode(' · ');
                    })
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('notified')
                    ->label('Bildiriş')
                    ->formatStateUsing(fn ($state): string => $state ? 'Bəli' : 'Xeyr'),
            ])
            ->defaultSort('match_score', 'desc')
            ->paginated(false);
    }
}
