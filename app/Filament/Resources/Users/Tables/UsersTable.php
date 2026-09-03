<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use App\Services\AuthService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar_url')
                    ->label('Şəkil')
                    ->circular()
                    ->getStateUsing(function (User $record): ?string {
                        $avatar = $record->avatar_url;
                        if (! $avatar) {
                            return null;
                        }
                        if (str_starts_with($avatar, 'http')) {
                            return $avatar;
                        }

                        return Storage::disk('public')->url($avatar);
                    }),
                TextColumn::make('phone')
                    ->label('Telefon')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Ad')
                    ->searchable(),
                TextColumn::make('active_role')
                    ->label('Rol')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'client' => 'Ailə',
                        'provider' => 'İcraçı',
                        default => $state ?? '—',
                    }),
                TextColumn::make('provider_approval_status')
                    ->label('Təsdiq')
                    ->badge()
                    ->color(function (User $record): string {
                        if ($record->isProviderResubmitPending()) {
                            return 'info';
                        }

                        return match ($record->provider_approval_status) {
                            'approved' => 'success',
                            'pending' => 'warning',
                            'rejected' => 'danger',
                            default => 'gray',
                        };
                    })
                    ->getStateUsing(function (User $record): string {
                        if ($record->isProviderResubmitPending()) {
                            return 'resubmit';
                        }

                        return $record->provider_approval_status ?? '';
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'approved' => 'Təsdiqli',
                        'pending' => 'Gözləyir',
                        'resubmit' => 'Yenidən baxış',
                        'rejected' => 'Rədd',
                        default => '—',
                    }),
                TextColumn::make('provider_resubmitted_at')
                    ->label('Yenidən göndərildi')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('balance')
                    ->label('Balans')
                    ->numeric()
                    ->suffix(' AZN')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Aktiv',
                        'blocked' => 'Bloklanıb',
                        default => $state,
                    }),
                TextColumn::make('created_at')
                    ->label('Yaradılıb')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('active_role')
                    ->label('Rol')
                    ->options([
                        'client' => 'Ailə',
                        'provider' => 'İcraçı',
                    ]),
                SelectFilter::make('provider_approval_status')
                    ->label('İcraçı təsdiqi')
                    ->options([
                        'pending' => 'Gözləyir',
                        'approved' => 'Təsdiqli',
                        'rejected' => 'Rədd',
                    ]),
                TernaryFilter::make('provider_resubmit')
                    ->label('Yenidən baxış')
                    ->placeholder('Hamısı')
                    ->trueLabel('Yenidən göndərilənlər')
                    ->falseLabel('İlk dəfə gözləyənlər')
                    ->queries(
                        true: fn (Builder $query) => $query
                            ->where('active_role', 'provider')
                            ->where('provider_approval_status', 'pending')
                            ->whereNotNull('provider_resubmitted_at'),
                        false: fn (Builder $query) => $query
                            ->where('active_role', 'provider')
                            ->where('provider_approval_status', 'pending')
                            ->whereNull('provider_resubmitted_at'),
                        blank: fn (Builder $query) => $query,
                    ),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Aktiv',
                        'blocked' => 'Bloklanıb',
                    ]),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                Action::make('approve')
                    ->label('Təsdiqlə')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (User $record): bool => $record->isProviderPending()
                        || $record->provider_approval_status === 'rejected')
                    ->requiresConfirmation()
                    ->action(function (User $record): void {
                        app(AuthService::class)->approveProvider($record, auth('admin')->user());
                        Notification::make()->title('İcraçı təsdiqləndi')->success()->send();
                    }),
                Action::make('reject')
                    ->label('Rədd et')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (User $record): bool => $record->isProvider()
                        && $record->provider_approval_status !== 'rejected')
                    ->form([
                        Textarea::make('note')
                            ->label('Səbəb (istəyə bağlı)')
                            ->rows(3),
                    ])
                    ->action(function (User $record, array $data): void {
                        app(AuthService::class)->rejectProvider(
                            $record,
                            $data['note'] ?? null,
                            auth('admin')->user()
                        );
                        Notification::make()->title('İcraçı rədd edildi')->warning()->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
