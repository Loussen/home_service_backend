<?php

namespace App\Filament\Resources\PushDispatches\RelationManagers;

use App\Filament\Resources\Users\UserResource;
use App\Models\PushDispatchRecipient;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RecipientsRelationManager extends RelationManager
{
    protected static string $relationship = 'recipients';

    protected static ?string $title = 'Alıcılar';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('user'))
            ->columns([
                TextColumn::make('user.name')
                    ->label('İstifadəçi')
                    ->description(fn (PushDispatchRecipient $record): string => trim(implode(' · ', array_filter([
                        $record->user?->phone,
                        $record->user?->active_role === 'provider' ? 'xidmətçi' : ($record->user?->active_role === 'client' ? 'ailə' : null),
                        $record->user_id ? '#'.$record->user_id : null,
                    ]))) ?: '—')
                    ->searchable(query: function ($query, string $search): void {
                        $query->whereHas('user', function ($q) use ($search): void {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        });
                    })
                    ->url(fn (PushDispatchRecipient $record): ?string => $record->user_id
                        ? UserResource::getUrl('edit', ['record' => $record->user_id])
                        : null),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (PushDispatchRecipient $record): string => $record->statusLabel())
                    ->color(fn (?string $state): string => match ($state) {
                        'delivered' => 'success',
                        'skipped_no_token' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('error')
                    ->label('Xəta')
                    ->placeholder('—')
                    ->wrap()
                    ->limit(80),
                TextColumn::make('created_at')
                    ->label('Vaxt')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable(),
            ])
            ->defaultSort('id', 'asc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'delivered' => 'Çatdı',
                        'skipped_no_token' => 'Tokensuz',
                        'failed' => 'Uğursuz',
                    ]),
            ])
            ->paginated([25, 50, 100, 200]);
    }
}
