<?php

namespace App\Filament\Resources\ProviderProfiles\Schemas;

use App\Filament\Support\FormComponents;
use App\Models\Category;
use App\Models\City;
use App\Models\District;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class ProviderProfileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                FormComponents::userSelect(),
                Placeholder::make('user_avatar')
                    ->label('İstifadəçi şəkli')
                    ->content(function ($record): HtmlString|string {
                        $avatar = $record?->user?->avatar_url;
                        if (! $avatar) {
                            return 'Şəkil yoxdur';
                        }
                        $url = str_starts_with($avatar, 'http')
                            ? $avatar
                            : Storage::disk('public')->url($avatar);

                        return new HtmlString(
                            '<img src="'.e($url).'" alt="" style="width:96px;height:96px;border-radius:999px;object-fit:cover;" />'
                        );
                    }),
                Placeholder::make('user_approval')
                    ->label('Hesab təsdiqi')
                    ->content(fn ($record) => match ($record?->user?->provider_approval_status) {
                        'approved' => 'Təsdiqli',
                        'pending' => 'Gözləyir',
                        'rejected' => 'Rədd edilib',
                        default => '—',
                    }),
                Select::make('category_ids')
                    ->label('Kateqoriyalar (maks. 3)')
                    ->multiple()
                    ->minItems(1)
                    ->maxItems(3)
                    ->searchable()
                    ->preload()
                    ->options(fn () => Category::treeLabelMap(leavesOnly: true))
                    ->required(),
                TextInput::make('title')
                    ->label('Başlıq'),
                Textarea::make('bio')
                    ->label('Haqqında')
                    ->columnSpanFull(),
                Placeholder::make('audio_player')
                    ->label('Audio intro (~20 san)')
                    ->content(function ($record): HtmlString|string {
                        if (! $record?->audio_intro_url) {
                            return 'Audio yoxdur';
                        }
                        $url = Storage::disk('public')->url($record->audio_intro_url);

                        return new HtmlString(
                            '<audio controls src="'.e($url).'" style="width:100%;max-width:420px;"></audio>'
                        );
                    }),
                TextInput::make('audio_intro_url')
                    ->label('Audio path')
                    ->disabled()
                    ->dehydrated(false),
                Toggle::make('is_verified')
                    ->label('Təsdiqlənib'),
                Toggle::make('is_vip')
                    ->label('VIP'),
                TextInput::make('latitude')
                    ->label('Enlik')
                    ->required()
                    ->numeric()
                    ->dehydrateStateUsing(fn ($state) => self::numericState($state)),
                TextInput::make('longitude')
                    ->label('Uzunluq')
                    ->required()
                    ->numeric()
                    ->dehydrateStateUsing(fn ($state) => self::numericState($state)),
                Select::make('city_id')
                    ->label('Şəhər')
                    ->options(fn () => City::query()->orderBy('sort_order')->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(fn ($set) => $set('district_id', null)),
                Select::make('district_id')
                    ->label('Rayon')
                    ->options(function ($get) {
                        $cityId = $get('city_id');
                        if (! $cityId) {
                            return [];
                        }

                        return District::query()
                            ->where('city_id', $cityId)
                            ->orderBy('sort_order')
                            ->orderBy('name')
                            ->pluck('name', 'id');
                    })
                    ->searchable()
                    ->preload(),
                TextInput::make('rating_avg')
                    ->label('Reytinq')
                    ->required()
                    ->numeric()
                    ->default(0.0)
                    ->dehydrateStateUsing(fn ($state) => self::numericState($state)),
                TextInput::make('rating_count')
                    ->label('Rəy sayı')
                    ->required()
                    ->numeric()
                    ->default(0),
                DateTimePicker::make('bumped_at')
                    ->label('Bump vaxtı'),
                DateTimePicker::make('vip_expires_at')
                    ->label('VIP bitmə'),
                Toggle::make('is_active')
                    ->label('Aktiv'),
                DateTimePicker::make('full_until')
                    ->label('Dolu bu tarixə qədər'),
                TimePicker::make('quiet_hours_start')
                    ->label('Səssiz başlanğıc')
                    ->seconds(false)
                    ->nullable(),
                TimePicker::make('quiet_hours_end')
                    ->label('Səssiz bitmə')
                    ->seconds(false)
                    ->nullable(),
            ]);
    }

    private static function numericState(mixed $state): ?float
    {
        if ($state === null || $state === '') {
            return null;
        }

        $normalized = str_replace([' ', ','], ['', '.'], (string) $state);

        return is_numeric($normalized) ? (float) $normalized : null;
    }
}
