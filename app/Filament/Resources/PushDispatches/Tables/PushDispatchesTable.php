<?php

namespace App\Filament\Resources\PushDispatches\Tables;

use App\Models\PushDispatch;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PushDispatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Vaxt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                TextColumn::make('source')
                    ->label('Mənbə')
                    ->badge()
                    ->formatStateUsing(fn (PushDispatch $record): string => $record->sourceLabel())
                    ->color(fn (?string $state): string => match ($state) {
                        'admin' => 'warning',
                        'request' => 'info',
                        'chat' => 'success',
                        'test' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('type')
                    ->label('Növ')
                    ->formatStateUsing(fn (PushDispatch $record): string => $record->typeLabel())
                    ->toggleable(),
                TextColumn::make('title')
                    ->label('Başlıq')
                    ->description(fn (PushDispatch $record): string => \Illuminate\Support\Str::limit($record->body, 80))
                    ->wrap()
                    ->searchable(),
                TextColumn::make('admin.name')
                    ->label('Admin')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('audience')
                    ->label('Auditoriya')
                    ->formatStateUsing(fn (PushDispatch $record): string => $record->audienceLabel() ?? '—')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('service_request_id')
                    ->label('Sorğu')
                    ->formatStateUsing(fn (?int $state): string => $state ? '#'.$state : '—')
                    ->url(fn (PushDispatch $record): ?string => $record->service_request_id
                        ? \App\Filament\Resources\ServiceRequests\ServiceRequestResource::getUrl('view', ['record' => $record->service_request_id])
                        : null)
                    ->toggleable(),
                TextColumn::make('delivered_count')
                    ->label('Çatdı')
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('targeted_count')
                    ->label('Hədəf')
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('skipped_count')
                    ->label('Tokensuz')
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('failed_count')
                    ->label('Uğursuz')
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('source')
                    ->label('Mənbə')
                    ->options([
                        'admin' => 'Admin',
                        'request' => 'Sorğu',
                        'chat' => 'Chat',
                        'system' => 'Sistem',
                        'test' => 'Test',
                    ]),
                SelectFilter::make('type')
                    ->label('Növ')
                    ->options([
                        'admin' => 'Admin elanı',
                        'new_job' => 'Yeni sorğu',
                        'urgent_job' => 'Təcili sorğu',
                        'chat_connect' => 'CONNECT',
                        'chat_message' => 'Mesaj',
                    ]),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                ViewAction::make()->label('Ətraflı'),
            ])
            ->toolbarActions([]);
    }
}
