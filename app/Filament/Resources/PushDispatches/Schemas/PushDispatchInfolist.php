<?php

namespace App\Filament\Resources\PushDispatches\Schemas;

use App\Models\PushDispatch;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PushDispatchInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Mesaj')
                ->schema([
                    TextEntry::make('title')
                        ->label('Başlıq')
                        ->columnSpanFull(),
                    TextEntry::make('body')
                        ->label('Mətn')
                        ->columnSpanFull(),
                    TextEntry::make('created_at')
                        ->label('Vaxt')
                        ->dateTime('d.m.Y H:i:s'),
                    TextEntry::make('source')
                        ->label('Mənbə')
                        ->badge()
                        ->formatStateUsing(fn (PushDispatch $record): string => $record->sourceLabel()),
                    TextEntry::make('type')
                        ->label('Növ')
                        ->formatStateUsing(fn (PushDispatch $record): string => $record->typeLabel()),
                    TextEntry::make('audience')
                        ->label('Auditoriya')
                        ->formatStateUsing(fn (PushDispatch $record): string => $record->audienceLabel() ?? '—')
                        ->placeholder('—'),
                ])
                ->columns(2),
            Section::make('Kim göndərdi / bağlıdır')
                ->schema([
                    TextEntry::make('admin.name')
                        ->label('Admin')
                        ->placeholder('Sistem'),
                    TextEntry::make('service_request_id')
                        ->label('Sorğu')
                        ->formatStateUsing(fn (?int $state): string => $state ? '#'.$state : '—')
                        ->url(fn (PushDispatch $record): ?string => $record->service_request_id
                            ? \App\Filament\Resources\ServiceRequests\ServiceRequestResource::getUrl('view', ['record' => $record->service_request_id])
                            : null),
                    TextEntry::make('conversation_id')
                        ->label('Söhbət')
                        ->formatStateUsing(fn (?int $state): string => $state ? '#'.$state : '—')
                        ->url(fn (PushDispatch $record): ?string => $record->conversation_id
                            ? \App\Filament\Resources\Conversations\ConversationResource::getUrl('view', ['record' => $record->conversation_id])
                            : null),
                ])
                ->columns(3),
            Section::make('Statistika')
                ->schema([
                    TextEntry::make('targeted_count')->label('Hədəf'),
                    TextEntry::make('delivered_count')->label('Çatdı (FCM)'),
                    TextEntry::make('skipped_count')->label('Tokensuz'),
                    TextEntry::make('failed_count')->label('Uğursuz'),
                ])
                ->columns(4),
            Section::make('Payload')
                ->collapsed()
                ->schema([
                    TextEntry::make('payload')
                        ->label('Data')
                        ->formatStateUsing(function ($state): string {
                            if (! is_array($state) || $state === []) {
                                return '—';
                            }

                            return collect($state)
                                ->map(fn ($v, $k) => $k.': '.(is_scalar($v) ? (string) $v : json_encode($v)))
                                ->implode("\n");
                        })
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
