<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('phone')
                    ->label('Telefon')
                    ->tel()
                    ->required(),
                TextInput::make('name')
                    ->label('Ad'),
                Placeholder::make('avatar_preview')
                    ->label('Profil şəkli')
                    ->content(function ($record): HtmlString|string {
                        if (! $record?->avatar_url) {
                            return 'Şəkil yoxdur';
                        }
                        $url = str_starts_with($record->avatar_url, 'http')
                            ? $record->avatar_url
                            : Storage::disk('public')->url($record->avatar_url);

                        return new HtmlString(
                            '<img src="'.e($url).'" alt="" style="width:96px;height:96px;border-radius:999px;object-fit:cover;border:1px solid #e4ded6;" />'
                        );
                    }),
                TextInput::make('avatar_url')
                    ->label('Avatar path/URL')
                    ->disabled()
                    ->dehydrated(false),
                Select::make('active_role')
                    ->label('Rol')
                    ->options(['client' => 'Ailə', 'provider' => 'İcraçı'])
                    ->default('client')
                    ->required()
                    ->helperText('İstifadəçi özü rolunu dəyişə bilməz. Yalnız admin dəyişə bilər.'),
                DateTimePicker::make('role_chosen_at')
                    ->label('Rol seçilib')
                    ->default(now()),
                Select::make('provider_approval_status')
                    ->label('İcraçı təsdiqi')
                    ->options([
                        'pending' => 'Gözləyir',
                        'approved' => 'Təsdiqli',
                        'rejected' => 'Rədd',
                    ])
                    ->visible(fn ($get) => $get('active_role') === 'provider'),
                DateTimePicker::make('provider_approved_at')
                    ->label('Təsdiq vaxtı')
                    ->visible(fn ($get) => $get('active_role') === 'provider'),
                Textarea::make('provider_rejection_note')
                    ->label('Rədd səbəbi')
                    ->rows(2)
                    ->visible(fn ($get) => $get('active_role') === 'provider'),
                Placeholder::make('provider_audio')
                    ->label('Audio intro')
                    ->content(function ($record): HtmlString|string {
                        if (! $record || $record->active_role !== 'provider') {
                            return '—';
                        }
                        $path = $record->providerProfiles()->latest()->value('audio_intro_url');
                        if (! $path) {
                            return 'Audio yoxdur';
                        }
                        $url = Storage::disk('public')->url($path);

                        return new HtmlString(
                            '<audio controls src="'.e($url).'" style="width:100%;max-width:360px;"></audio>'
                        );
                    }),
                Placeholder::make('provider_bio')
                    ->label('Bio')
                    ->content(function ($record): string {
                        if (! $record || $record->active_role !== 'provider') {
                            return '—';
                        }
                        $profile = self::latestProviderProfile($record);
                        $bio = trim((string) ($profile?->bio ?? ''));

                        return $bio !== '' ? $bio : 'Bio yoxdur';
                    }),
                Placeholder::make('provider_categories')
                    ->label('Kateqoriyalar')
                    ->content(function ($record): HtmlString|string {
                        if (! $record || $record->active_role !== 'provider') {
                            return '—';
                        }
                        $profile = self::latestProviderProfile($record);
                        if (! $profile) {
                            return 'Profil yoxdur';
                        }
                        $cats = $profile->relationLoaded('categories')
                            ? $profile->categories
                            : $profile->categories()->get();
                        if ($cats->isEmpty()) {
                            return 'Kateqoriya seçilməyib';
                        }
                        $chips = $cats->map(function ($c) {
                            $name = e($c->name_az ?? $c->name ?? '#'.$c->id);

                            return '<span style="display:inline-block;margin:0 6px 6px 0;padding:4px 10px;border-radius:999px;background:#F3E4DE;color:#9A3B22;font-size:12px;font-weight:700;">'.$name.'</span>';
                        })->implode('');

                        return new HtmlString('<div>'.$chips.'</div>');
                    }),
                Placeholder::make('location_city')
                    ->label('Şəhər')
                    ->content(fn ($record) => self::locationField($record, 'city')),
                Placeholder::make('location_district')
                    ->label('Rayon')
                    ->content(fn ($record) => self::locationField($record, 'district')),
                Placeholder::make('location_address')
                    ->label('Ünvan')
                    ->content(function ($record): HtmlString|string {
                        $loc = self::resolveLocation($record);
                        if ($loc === null) {
                            return 'Ünvan yoxdur';
                        }

                        $line = $loc['address']
                            ?: trim(implode(', ', array_filter([$loc['district'] ?? null, $loc['city'] ?? null])));
                        if ($line === '' && $loc['latitude'] !== null && $loc['longitude'] !== null) {
                            $line = number_format((float) $loc['latitude'], 5).', '.number_format((float) $loc['longitude'], 5);
                        }
                        if ($line === '') {
                            return 'Ünvan yoxdur';
                        }

                        if ($loc['latitude'] === null || $loc['longitude'] === null) {
                            return $line;
                        }

                        $maps = 'https://www.google.com/maps?q='.urlencode(
                            $loc['latitude'].','.$loc['longitude']
                        );

                        return new HtmlString(
                            '<div style="line-height:1.45;">'.e($line).
                            '<br><a href="'.e($maps).'" target="_blank" rel="noopener" style="color:#C24E2D;font-weight:600;">Xəritədə aç</a>'.
                            '<div style="margin-top:4px;color:#7A746C;font-size:12px;">'.
                            e(number_format((float) $loc['latitude'], 6).' · '.number_format((float) $loc['longitude'], 6)).
                            '</div></div>'
                        );
                    }),
                TextInput::make('balance')
                    ->label('Balans')
                    ->required()
                    ->numeric()
                    ->suffix('AZN')
                    ->default(0.0),
                Select::make('status')
                    ->label('Status')
                    ->options(['active' => 'Aktiv', 'blocked' => 'Bloklanıb'])
                    ->default('active')
                    ->required(),
                Toggle::make('welcome_bonus_granted')
                    ->label('Xoşgəldin bonusu verilib'),
                DateTimePicker::make('phone_verified_at')
                    ->label('Telefon təsdiqi'),
            ]);
    }

    private static function locationField($record, string $key): string
    {
        $loc = self::resolveLocation($record);
        $value = $loc[$key] ?? null;

        return filled($value) ? (string) $value : '—';
    }

    private static function latestProviderProfile($record)
    {
        if (! $record) {
            return null;
        }

        if ($record->relationLoaded('providerProfiles')) {
            return $record->providerProfiles->sortByDesc('id')->first();
        }

        return $record->providerProfiles()->with('categories')->latest('id')->first();
    }

    /**
     * @return array{city:?string,district:?string,address:?string,latitude:?float,longitude:?float}|null
     */
    private static function resolveLocation($record): ?array
    {
        if (! $record) {
            return null;
        }

        if ($record->active_role === 'provider') {
            $profile = self::latestProviderProfile($record);

            if (! $profile) {
                return null;
            }

            $address = trim(implode(', ', array_filter([
                $profile->district,
                $profile->city,
            ])));

            return [
                'city' => $profile->city,
                'district' => $profile->district,
                'address' => $address !== '' ? $address : null,
                'latitude' => $profile->latitude !== null ? (float) $profile->latitude : null,
                'longitude' => $profile->longitude !== null ? (float) $profile->longitude : null,
            ];
        }

        $request = $record->serviceRequests()->latest('id')->first();
        if (! $request) {
            return null;
        }

        return [
            'city' => null,
            'district' => null,
            'address' => $request->address,
            'latitude' => $request->latitude !== null ? (float) $request->latitude : null,
            'longitude' => $request->longitude !== null ? (float) $request->longitude : null,
        ];
    }
}
