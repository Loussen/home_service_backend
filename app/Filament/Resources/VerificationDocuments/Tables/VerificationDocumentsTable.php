<?php

namespace App\Filament\Resources\VerificationDocuments\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class VerificationDocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('İstifadəçi')
                    ->searchable(),
                TextColumn::make('providerProfile.title')
                    ->label('Profil')
                    ->searchable(),
                TextColumn::make('document_type')
                    ->label('Növ')
                    ->searchable(),
                ImageColumn::make('file_url')
                    ->label('Fayl')
                    ->disk('public')
                    ->height(48)
                    ->square()
                    ->defaultImageUrl(fn () => null)
                    ->checkFileExistence(false),
                TextColumn::make('file_link')
                    ->label('Aç')
                    ->getStateUsing(fn ($record) => filled($record->file_url) ? 'Bax' : null)
                    ->url(fn ($record) => filled($record->file_url)
                        ? Storage::disk('public')->url($record->file_url)
                        : null)
                    ->openUrlInNewTab()
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Gözləyir',
                        'approved' => 'Təsdiqlənib',
                        'rejected' => 'Rədd edilib',
                        default => $state,
                    }),
                TextColumn::make('reviewed_at')
                    ->label('Yoxlanılıb')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Yaradılıb')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Təsdiq et')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Sənədi təsdiqləmək?')
                    ->action(function ($record): void {
                        $record->update([
                            'status' => 'approved',
                            'reviewed_at' => now(),
                        ]);
                        $record->providerProfile?->update(['is_verified' => true]);
                    }),
                Action::make('reject')
                    ->label('Rədd et')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Sənədi rədd etmək?')
                    ->action(fn ($record) => $record->update([
                        'status' => 'rejected',
                        'reviewed_at' => now(),
                    ])),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
