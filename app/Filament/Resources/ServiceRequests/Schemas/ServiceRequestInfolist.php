<?php

namespace App\Filament\Resources\ServiceRequests\Schemas;

use App\Filament\Infolists\Components\AudioPlayerEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ServiceRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Sorğu')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id')
                            ->label('ID'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'processing' => 'Emal olunur',
                                'active' => 'Aktiv',
                                'matched' => 'Uyğunlaşıb',
                                'completed' => 'Tamamlanıb',
                                'cancelled' => 'Ləğv edilib',
                                default => $state,
                            }),
                        TextEntry::make('user.name')
                            ->label('Müştəri')
                            ->placeholder('Adsız'),
                        TextEntry::make('user.phone')
                            ->label('Telefon')
                            ->copyable(),
                        TextEntry::make('category.name_az')
                            ->label('Kateqoriya')
                            ->placeholder('—'),
                        IconEntry::make('is_urgent')
                            ->label('Təcili')
                            ->boolean(),
                        TextEntry::make('address')
                            ->label('Ünvan')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('latitude')
                            ->label('Enlik'),
                        TextEntry::make('longitude')
                            ->label('Uzunluq'),
                        TextEntry::make('maps_link')
                            ->label('Xəritə')
                            ->getStateUsing(fn ($record) => filled($record->latitude) && filled($record->longitude)
                                ? 'Google Maps-də aç'
                                : null)
                            ->url(fn ($record) => filled($record->latitude) && filled($record->longitude)
                                ? 'https://www.google.com/maps?q='.$record->latitude.','.$record->longitude
                                : null)
                            ->openUrlInNewTab()
                            ->placeholder('—'),
                        TextEntry::make('urgent_until')
                            ->label('Təcili bitmə')
                            ->dateTime('d.m.Y H:i')
                            ->placeholder('—')
                            ->visible(fn ($record) => (bool) $record?->is_urgent),
                        TextEntry::make('created_at')
                            ->label('Yaradılıb')
                            ->dateTime('d.m.Y H:i'),
                    ]),
                Section::make('Səs və mətn')
                    ->schema([
                        AudioPlayerEntry::make('audio_public_url')
                            ->label('Səs yazısı'),
                        TextEntry::make('transcribed_text')
                            ->label('Transkripsiya')
                            ->placeholder('Mətn yoxdur')
                            ->columnSpanFull(),
                        TextEntry::make('parsed_criteria')
                            ->label('AI meyarlar')
                            ->formatStateUsing(function ($state): string {
                                if (! is_array($state) || $state === []) {
                                    return '{}';
                                }

                                return json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}';
                            })
                            ->fontFamily('mono')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
