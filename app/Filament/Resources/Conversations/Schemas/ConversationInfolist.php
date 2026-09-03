<?php

namespace App\Filament\Resources\Conversations\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ConversationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Söhbət')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id')
                            ->label('ID'),
                        TextEntry::make('service_request_id')
                            ->label('Sorğu ID')
                            ->placeholder('—'),
                        TextEntry::make('client.name')
                            ->label('Ailə')
                            ->placeholder('—'),
                        TextEntry::make('client.phone')
                            ->label('Ailə telefon')
                            ->copyable()
                            ->placeholder('—'),
                        TextEntry::make('provider.name')
                            ->label('İcraçı')
                            ->placeholder('—'),
                        TextEntry::make('provider.phone')
                            ->label('İcraçı telefon')
                            ->copyable()
                            ->placeholder('—'),
                        TextEntry::make('providerProfile.title')
                            ->label('Profil')
                            ->placeholder('—'),
                        TextEntry::make('last_message_at')
                            ->label('Son aktivlik')
                            ->dateTime('d.m.Y H:i')
                            ->placeholder('—'),
                        TextEntry::make('messages_count')
                            ->label('Mesaj sayı')
                            ->state(fn ($record) => $record->messages()->count()),
                        TextEntry::make('offers_count')
                            ->label('Təklif sayı')
                            ->state(fn ($record) => $record->offers()->count()),
                        TextEntry::make('created_at')
                            ->label('Yaradılıb')
                            ->dateTime('d.m.Y H:i'),
                    ]),
            ]);
    }
}
