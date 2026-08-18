<?php

namespace App\Filament\Resources\ServiceRequests\Tables;

use App\Filament\Resources\ServiceRequests\ServiceRequestResource;
use App\Models\Category;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ServiceRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('user.phone')
                    ->label('Müştəri')
                    ->description(fn ($record) => $record->user?->name)
                    ->searchable(),
                TextColumn::make('category.name_az')
                    ->label('Kateqoriya')
                    ->placeholder('—')
                    ->searchable(),
                IconColumn::make('raw_audio_url')
                    ->label('Səs')
                    ->boolean()
                    ->getStateUsing(fn ($record) => filled($record->raw_audio_url)),
                TextColumn::make('transcribed_text')
                    ->label('Transkripsiya')
                    ->limit(40)
                    ->wrap()
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(),
                IconColumn::make('is_urgent')
                    ->label('Təcili')
                    ->boolean(),
                TextColumn::make('matches_count')
                    ->label('Match')
                    ->sortable(),
                TextColumn::make('status')
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
                TextColumn::make('created_at')
                    ->label('Yaradılıb')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'processing' => 'Emal olunur',
                        'active' => 'Aktiv',
                        'matched' => 'Uyğunlaşıb',
                        'completed' => 'Tamamlanıb',
                        'cancelled' => 'Ləğv edilib',
                    ]),
                SelectFilter::make('category_id')
                    ->label('Kateqoriya')
                    ->options(fn () => Category::treeLabelMap())
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('is_urgent')
                    ->label('Təcili'),
                TernaryFilter::make('has_audio')
                    ->label('Səs var')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('raw_audio_url')->where('raw_audio_url', '!=', ''),
                        false: fn ($query) => $query->where(fn ($q) => $q->whereNull('raw_audio_url')->orWhere('raw_audio_url', '')),
                    ),
            ])
            ->recordUrl(fn ($record) => ServiceRequestResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
