<?php

namespace App\Filament\Resources\ProviderProfiles\Schemas;

use App\Filament\Support\FormComponents;
use App\Models\Category;
use App\Models\City;
use App\Models\District;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProviderProfileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                FormComponents::userSelect(),
                Select::make('category_ids')
                    ->label('Kateqoriyalar (maks. 3)')
                    ->multiple()
                    ->maxItems(3)
                    ->searchable()
                    ->preload()
                    ->options(fn () => Category::treeLabelMap(leavesOnly: true))
                    ->required()
                    ->dehydrated(false),
                TextInput::make('title')
                    ->label('Başlıq'),
                Textarea::make('bio')
                    ->label('Haqqında')
                    ->columnSpanFull(),
                TextInput::make('audio_intro_url')
                    ->label('Audio intro')
                    ->url(),
                Toggle::make('is_verified')
                    ->label('Təsdiqlənib'),
                Toggle::make('is_vip')
                    ->label('VIP'),
                TextInput::make('latitude')
                    ->label('Enlik')
                    ->required()
                    ->numeric(),
                TextInput::make('longitude')
                    ->label('Uzunluq')
                    ->required()
                    ->numeric(),
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
                    ->default(0.0),
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
            ]);
    }
}
