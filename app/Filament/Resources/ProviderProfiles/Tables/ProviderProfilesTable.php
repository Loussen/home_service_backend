<?php

namespace App\Filament\Resources\ProviderProfiles\Tables;

use App\Models\Category;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
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
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Kateqoriya')
                    ->options(fn () => Category::treeLabelMap())
                    ->searchable()
                    ->preload()
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
            ])
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
