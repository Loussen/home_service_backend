<?php

namespace App\Filament\Resources\StaticPages;

use App\Filament\Resources\StaticPages\Pages\ManageStaticPages;
use App\Models\StaticPage;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class StaticPageResource extends Resource
{
    protected static ?string $model = StaticPage::class;

    protected static ?string $navigationLabel = 'Statik səhifələr';

    protected static ?string $modelLabel = 'statik səhifə';

    protected static ?string $pluralModelLabel = 'Statik səhifələr';

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('slug')
                ->label('Slug')
                ->required()
                ->maxLength(80)
                ->helperText('məs. about, terms, privacy')
                ->unique(ignoreRecord: true),
            TextInput::make('sort_order')
                ->label('Sıra')
                ->numeric()
                ->required()
                ->default(0),
            Toggle::make('is_published')
                ->label('Dərc olunub')
                ->default(true),
            Toggle::make('show_in_menu')
                ->label('Menyuda göstər')
                ->default(true),
            Tabs::make('Locales')
                ->columnSpanFull()
                ->tabs([
                    Tab::make('AZ')->schema([
                        TextInput::make('title_az')
                            ->label('Başlıq (AZ)')
                            ->required()
                            ->maxLength(191),
                        RichEditor::make('body_az')
                            ->label('Məzmun (AZ)')
                            ->columnSpanFull(),
                    ]),
                    Tab::make('EN')->schema([
                        TextInput::make('title_en')
                            ->label('Title (EN)')
                            ->maxLength(191),
                        RichEditor::make('body_en')
                            ->label('Body (EN)')
                            ->columnSpanFull(),
                    ]),
                    Tab::make('RU')->schema([
                        TextInput::make('title_ru')
                            ->label('Заголовок (RU)')
                            ->maxLength(191),
                        RichEditor::make('body_ru')
                            ->label('Текст (RU)')
                            ->columnSpanFull(),
                    ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')
                    ->label('Sıra')
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('title_az')
                    ->label('Başlıq (AZ)')
                    ->searchable()
                    ->description(fn (StaticPage $record) => $record->title_en),
                IconColumn::make('is_published')
                    ->label('Dərc')
                    ->boolean(),
                IconColumn::make('show_in_menu')
                    ->label('Menyu')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label('Yenilənib')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageStaticPages::route('/'),
        ];
    }
}
