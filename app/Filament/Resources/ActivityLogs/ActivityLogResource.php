<?php

namespace App\Filament\Resources\ActivityLogs;

use App\Filament\Resources\ActivityLogs\Pages\ManageActivityLogs;
use App\Models\ActivityLog;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;

    protected static ?string $navigationLabel = 'Aktivlik loqu';

    protected static ?string $modelLabel = 'aktivlik';

    protected static ?string $pluralModelLabel = 'Aktivlik loqu';

    protected static ?int $navigationSort = 9;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('created_at')
                ->label('Vaxt')
                ->dateTime('d.m.Y H:i:s'),
            TextEntry::make('user.phone')
                ->label('Telefon')
                ->placeholder('—'),
            TextEntry::make('user.name')
                ->label('Ad')
                ->placeholder('—'),
            TextEntry::make('label')
                ->label('Əməliyyat'),
            TextEntry::make('action')
                ->label('Kod'),
            TextEntry::make('platform')
                ->label('Platforma')
                ->formatStateUsing(fn (?string $state): string => match ($state) {
                    'web' => 'Web',
                    'app' => 'App',
                    'admin' => 'Admin',
                    default => $state ?? '—',
                }),
            TextEntry::make('method')
                ->label('HTTP')
                ->placeholder('—'),
            TextEntry::make('path')
                ->label('Path')
                ->placeholder('—'),
            TextEntry::make('status_code')
                ->label('Status')
                ->placeholder('—'),
            TextEntry::make('ip')
                ->label('IP')
                ->placeholder('—'),
            TextEntry::make('user_agent')
                ->label('User-Agent')
                ->placeholder('—')
                ->columnSpanFull(),
            TextEntry::make('properties')
                ->label('Əlavə')
                ->formatStateUsing(function ($state): string {
                    if (! is_array($state) || $state === []) {
                        return '—';
                    }

                    return json_encode($state, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: '—';
                })
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Vaxt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                TextColumn::make('user.phone')
                    ->label('Telefon')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('user.name')
                    ->label('Ad')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('—'),
                TextColumn::make('label')
                    ->label('Əməliyyat')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('platform')
                    ->label('Platforma')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'web' => 'Web',
                        'app' => 'App',
                        'admin' => 'Admin',
                        default => $state ?? '?',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'web' => 'info',
                        'app' => 'success',
                        'admin' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('status_code')
                    ->label('HTTP')
                    ->badge()
                    ->color(fn (?int $state): string => match (true) {
                        $state === null => 'gray',
                        $state < 300 => 'success',
                        $state < 500 => 'warning',
                        default => 'danger',
                    })
                    ->placeholder('—'),
                TextColumn::make('path')
                    ->label('Path')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(40),
                TextColumn::make('ip')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('platform')
                    ->label('Platforma')
                    ->options([
                        'web' => 'Web',
                        'app' => 'App',
                        'admin' => 'Admin',
                        'system' => 'Sistem',
                        'unknown' => 'Naməlum',
                    ]),
                SelectFilter::make('action')
                    ->label('Əməliyyat')
                    ->searchable()
                    ->options([
                        'auth.otp_send' => 'OTP göndərildi',
                        'auth.otp_verify' => 'Giriş (OTP təsdiq)',
                        'auth.logout' => 'Çıxış',
                        'auth.role' => 'Rol seçildi',
                        'auth.profile_update' => 'Profil yeniləndi',
                        'auth.avatar' => 'Profil şəkli yükləndi',
                        'request.text' => 'Mətn sorğusu',
                        'request.audio' => 'Səs sorğusu',
                        'request.urgent' => 'Təcili sorğu',
                        'chat.connect' => 'CONNECT',
                        'chat.reply' => 'İşə cavab',
                        'chat.message' => 'Mesaj',
                        'chat.offer' => 'Təklif',
                        'provider.profile_create' => 'Profil yaradıldı',
                        'provider.profile_update' => 'Profil yeniləndi',
                        'provider.audio' => 'Audio intro',
                        'admin.provider_approve' => 'İcraçı təsdiqi',
                        'admin.provider_reject' => 'İcraçı rədd',
                        'wallet.top_up' => 'Balans artırma',
                    ]),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                ViewAction::make()->label('Bax'),
            ])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageActivityLogs::route('/'),
        ];
    }
}
