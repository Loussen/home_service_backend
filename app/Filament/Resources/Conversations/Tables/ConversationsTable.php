<?php

namespace App\Filament\Resources\Conversations\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ConversationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('client.name')
                    ->label('Ailə')
                    ->description(fn ($record) => $record->client?->phone)
                    ->searchable(['client.name', 'client.phone']),
                TextColumn::make('provider.name')
                    ->label('İcraçı')
                    ->description(fn ($record) => $record->provider?->phone)
                    ->searchable(['provider.name', 'provider.phone']),
                TextColumn::make('providerProfile.title')
                    ->label('Profil')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('lastMessage.body')
                    ->label('Son mesaj')
                    ->limit(40)
                    ->placeholder('—')
                    ->wrap(),
                TextColumn::make('messages_count')
                    ->label('Mesaj')
                    ->sortable(),
                TextColumn::make('offers_count')
                    ->label('Təklif')
                    ->sortable(),
                TextColumn::make('reviews_count')
                    ->label('Rəy')
                    ->sortable(),
                TextColumn::make('last_message_at')
                    ->label('Son aktivlik')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->placeholder('—'),
            ])
            ->defaultSort('last_message_at', 'desc')
            ->filters([
                SelectFilter::make('has_offers')
                    ->label('Təklif')
                    ->options([
                        'yes' => 'Var',
                        'no' => 'Yox',
                    ])
                    ->query(function ($query, array $data) {
                        return match ($data['value'] ?? null) {
                            'yes' => $query->has('offers'),
                            'no' => $query->doesntHave('offers'),
                            default => $query,
                        };
                    }),
            ])
            ->recordActions([
                ViewAction::make()->label('Bax'),
            ]);
    }
}
