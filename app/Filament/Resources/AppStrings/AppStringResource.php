<?php

namespace App\Filament\Resources\AppStrings;

use App\Filament\Resources\AppStrings\Pages\ManageAppStrings;
use App\Models\AppString;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class AppStringResource extends Resource
{
    protected static ?string $model = AppString::class;

    protected static ?string $navigationLabel = 'App mətnləri';

    protected static ?string $modelLabel = 'mətn';

    protected static ?string $pluralModelLabel = 'App mətnləri';

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLanguage;

    public static function form(Schema $schema): Schema
    {
        $locales = config('app_locales.supported', ['az']);

        return $schema->components([
            TextInput::make('key')
                ->label('Açar')
                ->required()
                ->maxLength(191)
                ->disabledOn('edit')
                ->helperText('məs. search.headline'),
            Select::make('locale')
                ->label('Dil')
                ->required()
                ->options(collect($locales)->mapWithKeys(
                    fn (string $code) => [$code => config("app_locales.labels.{$code}", $code)]
                )->all())
                ->disabledOn('edit'),
            Textarea::make('value')
                ->label('Mətn')
                ->required()
                ->rows(4)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')
                    ->label('Açar')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('locale')
                    ->label('Dil')
                    ->badge()
                    ->sortable(),
                TextColumn::make('value')
                    ->label('Mətn')
                    ->limit(80)
                    ->wrap()
                    ->searchable(),
                TextColumn::make('updated_at')
                    ->label('Yenilənib')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('key')
            ->filters([
                SelectFilter::make('locale')
                    ->label('Dil')
                    ->options(collect(config('app_locales.supported', ['az']))->mapWithKeys(
                        fn (string $code) => [$code => config("app_locales.labels.{$code}", $code)]
                    )->all()),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAppStrings::route('/'),
        ];
    }
}
