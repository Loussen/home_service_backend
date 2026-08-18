<?php

namespace App\Filament\Resources\Categories;

use App\Filament\Resources\Categories\Pages\ManageCategories;
use App\Models\Category;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationLabel = 'Kateqoriyalar';

    protected static ?string $modelLabel = 'kateqoriya';

    protected static ?string $pluralModelLabel = 'Kateqoriyalar';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('parent_id')
                    ->label('Ana kateqoriya')
                    ->options(fn (?Category $record) => Category::treeLabelMap($record?->id))
                    ->searchable()
                    ->preload()
                    ->placeholder('Kök (ana kateqoriya)'),
                TextInput::make('slug')
                    ->label('Slug')
                    ->required(),
                TextInput::make('name_az')
                    ->label('Ad (AZ)')
                    ->required(),
                TextInput::make('name_en')
                    ->label('Ad (EN)'),
                TextInput::make('icon')
                    ->label('İkon'),
                Toggle::make('is_active')
                    ->label('Aktiv'),
                TextInput::make('sort_order')
                    ->label('Sıra')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable(),
                TextColumn::make('parent.name_az')
                    ->label('Ana')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('name_az')
                    ->label('Ad (AZ)')
                    ->searchable()
                    ->description(fn (Category $record) => Category::treeLabelMap()[$record->id] ?? null),
                TextColumn::make('name_en')
                    ->label('Ad (EN)')
                    ->searchable(),
                TextColumn::make('icon')
                    ->label('İkon')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->label('Sıra')
                    ->numeric()
                    ->sortable(),
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
            ->defaultSort('sort_order')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCategories::route('/'),
        ];
    }
}
