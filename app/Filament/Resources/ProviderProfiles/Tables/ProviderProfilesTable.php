<?php

namespace App\Filament\Resources\ProviderProfiles\Tables;

use App\Models\Category;
use App\Models\City;
use App\Models\District;
use App\Support\BumpQuota;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProviderProfilesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('İstifadəçi')
                    ->searchable(),
                TextColumn::make('user.phone')
                    ->label('Telefon')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('categories.name_az')
                    ->label('Kateqoriyalar')
                    ->badge()
                    ->separator(','),
                TextColumn::make('title')
                    ->label('Başlıq')
                    ->searchable(),
                IconColumn::make('is_verified')
                    ->label('Təsdiq')
                    ->boolean(),
                IconColumn::make('is_vip')
                    ->label('VIP')
                    ->boolean(),
                TextColumn::make('city')
                    ->label('Şəhər')
                    ->searchable(),
                TextColumn::make('district')
                    ->label('Rayon')
                    ->searchable(),
                TextColumn::make('rating_avg')
                    ->label('Reytinq')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean(),
                TextColumn::make('full_until')
                    ->label('Dolu')
                    ->dateTime()
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('bumped_at')
                    ->label('Bump')
                    ->dateTime()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Yaradılıb')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Yenilənib')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Kateqoriya')
                    ->options(fn () => Category::treeLabelMap())
                    ->searchable()
                    ->preload()
                    ->indicateUsing(function (array $data): ?string {
                        $value = $data['value'] ?? null;
                        if (! $value) {
                            return null;
                        }
                        $label = Category::treeLabelMap()[(int) $value] ?? ('#'.$value);

                        return 'Kateqoriya: '.$label;
                    })
                    ->query(function ($query, array $data) {
                        $value = $data['value'] ?? null;
                        if (! $value) {
                            return;
                        }
                        $ids = Category::idsWithDescendants((int) $value);
                        $query->where(function ($q) use ($ids) {
                            $q->whereIn('category_id', $ids)
                                ->orWhereHas(
                                    'categories',
                                    fn ($c) => $c->whereIn('categories.id', $ids)
                                );
                        });
                    }),
                SelectFilter::make('city_id')
                    ->label('Şəhər')
                    ->options(fn () => City::query()
                        ->where('is_active', true)
                        ->orderBy('sort_order')
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->preload()
                    ->query(function ($query, array $data) {
                        $value = $data['value'] ?? null;
                        if (! $value) {
                            return;
                        }
                        $city = City::query()->find((int) $value);
                        $query->where(function ($q) use ($value, $city) {
                            $q->where('city_id', (int) $value);
                            if ($city?->name) {
                                $q->orWhere('city', $city->name);
                            }
                        });
                    }),
                SelectFilter::make('district_id')
                    ->label('Rayon')
                    ->options(fn () => District::query()
                        ->where('is_active', true)
                        ->orderBy('sort_order')
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->preload()
                    ->query(function ($query, array $data) {
                        $value = $data['value'] ?? null;
                        if (! $value) {
                            return;
                        }
                        $district = District::query()->find((int) $value);
                        $query->where(function ($q) use ($value, $district) {
                            $q->where('district_id', (int) $value);
                            if ($district?->name) {
                                $q->orWhere('district', $district->name);
                            }
                        });
                    }),
                TernaryFilter::make('is_verified')
                    ->label('Təsdiqlənmiş'),
                TernaryFilter::make('is_vip')
                    ->label('VIP'),
                TernaryFilter::make('is_active')
                    ->label('Aktiv'),
                TernaryFilter::make('bump_active')
                    ->label('Önə çıxıb (bump)')
                    ->queries(
                        true: fn ($query) => $query->where(
                            'bumped_at',
                            '>',
                            now()->subHours(BumpQuota::hours())
                        ),
                        false: fn ($query) => $query->where(function ($q) {
                            $q->whereNull('bumped_at')
                                ->orWhere(
                                    'bumped_at',
                                    '<=',
                                    now()->subHours(BumpQuota::hours())
                                );
                        }),
                    ),
                TernaryFilter::make('is_full')
                    ->label('Bu həftə dolu')
                    ->queries(
                        true: fn ($query) => $query
                            ->whereNotNull('full_until')
                            ->where('full_until', '>', now()),
                        false: fn ($query) => $query->where(function ($q) {
                            $q->whereNull('full_until')
                                ->orWhere('full_until', '<=', now());
                        }),
                    ),
                TernaryFilter::make('has_audio')
                    ->label('Audio intro')
                    ->queries(
                        true: fn ($query) => $query
                            ->whereNotNull('audio_intro_url')
                            ->where('audio_intro_url', '!=', ''),
                        false: fn ($query) => $query->where(function ($q) {
                            $q->whereNull('audio_intro_url')
                                ->orWhere('audio_intro_url', '');
                        }),
                    ),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3)
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
