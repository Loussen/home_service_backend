<?php

namespace App\Filament\Resources\ActivityLogs;

use App\Filament\Resources\ActivityLogs\Pages\ManageActivityLogs;
use App\Models\ActivityLog;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
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
            Section::make('Nə baş verdi')
                ->schema([
                    TextEntry::make('label')
                        ->label('Əməliyyat')
                        ->formatStateUsing(fn ($state, ActivityLog $record) => self::humanLabel($record))
                        ->columnSpanFull(),
                    TextEntry::make('created_at')
                        ->label('Vaxt')
                        ->dateTime('d.m.Y H:i:s'),
                    TextEntry::make('platform')
                        ->label('Haradan')
                        ->badge()
                        ->formatStateUsing(fn (?string $state): string => match ($state) {
                            'web' => 'Web sayt',
                            'app' => 'Mobil tətbiq',
                            'admin' => 'Admin panel',
                            default => $state ?? '—',
                        }),
                    TextEntry::make('user.name')
                        ->label('İstifadəçi')
                        ->placeholder('—'),
                    TextEntry::make('user.phone')
                        ->label('Telefon')
                        ->placeholder('—'),
                    TextEntry::make('properties')
                        ->label('Detallar')
                        ->html()
                        ->formatStateUsing(fn ($state) => self::formatDetailsHtml($state))
                        ->columnSpanFull(),
                ])
                ->columns(2),
            Section::make('Texniki məlumat')
                ->collapsed()
                ->schema([
                    TextEntry::make('action')->label('Kod')->placeholder('—'),
                    TextEntry::make('method')->label('HTTP metod')->placeholder('—'),
                    TextEntry::make('path')->label('API path')->placeholder('—'),
                    TextEntry::make('status_code')->label('Cavab kodu')->placeholder('—'),
                    TextEntry::make('ip')->label('IP')->placeholder('—'),
                    TextEntry::make('user_agent')
                        ->label('Brauzer / cihaz')
                        ->placeholder('—')
                        ->columnSpanFull(),
                ])
                ->columns(2),
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
                TextColumn::make('user.name')
                    ->label('İstifadəçi')
                    ->description(fn (ActivityLog $record): string => (string) ($record->user?->phone ?? ''))
                    ->searchable(query: function ($query, string $search): void {
                        $query->whereHas('user', function ($q) use ($search): void {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        });
                    })
                    ->placeholder('—'),
                TextColumn::make('label')
                    ->label('Nə etdi')
                    ->formatStateUsing(fn ($state, ActivityLog $record) => self::humanLabel($record))
                    ->wrap()
                    ->searchable(),
                TextColumn::make('platform')
                    ->label('Haradan')
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
                    ->label('Nəticə')
                    ->badge()
                    ->formatStateUsing(fn (?int $state): string => match (true) {
                        $state === null => '—',
                        $state < 300 => 'Uğurlu',
                        $state < 500 => 'Xəta '.$state,
                        default => 'Server xətası',
                    })
                    ->color(fn (?int $state): string => match (true) {
                        $state === null => 'gray',
                        $state < 300 => 'success',
                        $state < 500 => 'warning',
                        default => 'danger',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('platform')
                    ->label('Haradan')
                    ->options([
                        'web' => 'Web sayt',
                        'app' => 'Mobil tətbiq',
                        'admin' => 'Admin panel',
                        'system' => 'Sistem',
                        'unknown' => 'Naməlum',
                    ]),
                SelectFilter::make('action')
                    ->label('Əməliyyat növü')
                    ->searchable()
                    ->options([
                        'auth.otp_verify' => 'Giriş',
                        'auth.role' => 'Rol seçimi',
                        'auth.profile_update' => 'Profil yeniləmə',
                        'auth.avatar' => 'Şəkil yükləmə',
                        'auth.provider_resubmit' => 'Yenidən baxışa göndərmə',
                        'provider.categories' => 'Kateqoriya dəyişmə',
                        'provider.profile_update' => 'Profil yeniləmə',
                        'provider.audio' => 'Audio intro',
                        'request.text' => 'Mətn sorğusu',
                        'request.audio' => 'Səs sorğusu',
                        'chat.connect' => 'CONNECT',
                        'chat.message' => 'Mesaj',
                        'chat.offer' => 'Təklif',
                        'admin.provider_approve' => 'İcraçı təsdiqi',
                        'admin.provider_reject' => 'İcraçı rədd',
                        'wallet.top_up' => 'Balans artırma',
                    ]),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                ViewAction::make()->label('Ətraflı'),
            ])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageActivityLogs::route('/'),
        ];
    }

    public static function humanLabel(ActivityLog $record): string
    {
        $label = trim((string) ($record->label ?? ''));
        if ($label !== '' && ! str_starts_with($label, 'PUT ') && ! str_starts_with($label, 'POST ')
            && ! str_starts_with($label, 'PATCH ') && ! str_starts_with($label, 'DELETE ')
            && ! str_starts_with($label, 'Digər əməliyyat')) {
            return $label;
        }

        // Köhnə/texniki loglar üçün path-dən izah çıxar
        $path = ltrim((string) ($record->path ?? ''), '/');
        if (str_starts_with($path, 'api/')) {
            $path = substr($path, 4);
        }
        $method = strtoupper((string) ($record->method ?? ''));
        $key = $method.' '.$path;

        if (str_contains($path, 'provider-profiles') && is_array($record->properties)) {
            $props = $record->properties;
            $cats = $props['Kateqoriyalar'] ?? null;
            if (is_string($cats) && $cats !== '') {
                return 'Kateqoriyalarını dəyişdi: '.$cats;
            }
            $ids = $props['category_ids'] ?? null;
            if (is_array($ids) && $ids !== []) {
                return 'Kateqoriyalarını dəyişdi';
            }
            if (array_is_list($props) && $props !== [] && is_numeric($props[0] ?? null)) {
                return 'Kateqoriyalarını dəyişdi';
            }
        }
        if (preg_match('#provider-profiles/\d+/audio-intro#', $path)) {
            return 'Audio intro yüklədi';
        }
        if (preg_match('#PUT .+provider-profiles/\d+#', $key) || preg_match('#provider-profiles/\d+#', $path) && $method === 'PUT') {
            return 'Xidmətçi profilini yenilədi';
        }
        if (str_contains($path, 'auth/avatar')) {
            return 'Profil şəkli yüklədi';
        }
        if (str_contains($path, 'auth/role')) {
            return 'Rol seçdi';
        }
        if (str_contains($path, 'auth/otp/verify')) {
            return 'Sistemə daxil oldu (OTP)';
        }

        return $label !== '' ? $label : 'Əməliyyat qeydə alındı';
    }

    private static function formatDetailsHtml(mixed $state): string
    {
        if (! is_array($state) || $state === []) {
            return '<span style="color:#7A746C;">Əlavə detal yoxdur</span>';
        }

        if (array_is_list($state)) {
            $state = ['Detallar' => implode(', ', array_map(fn ($v) => is_scalar($v) ? (string) $v : json_encode($v), $state))];
        }

        $rows = '';
        foreach ($state as $key => $value) {
            if ($key === 'category_ids') {
                continue;
            }
            if (is_array($value)) {
                $value = implode(', ', array_map(fn ($v) => is_scalar($v) ? (string) $v : json_encode($v), $value));
            }
            $rows .= '<tr><td style="padding:4px 12px 4px 0;color:#7A746C;vertical-align:top;">'.e((string) $key).'</td>'
                .'<td style="padding:4px 0;font-weight:600;">'.e((string) $value).'</td></tr>';
        }

        if ($rows === '') {
            return '<span style="color:#7A746C;">Əlavə detal yoxdur</span>';
        }

        return '<table style="width:100%;border-collapse:collapse;">'.$rows.'</table>';
    }
}
