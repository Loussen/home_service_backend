<?php

namespace App\Filament\Resources\PushDispatches;

use App\Filament\Resources\PushDispatches\Pages\ListPushDispatches;
use App\Filament\Resources\PushDispatches\Pages\ViewPushDispatch;
use App\Filament\Resources\PushDispatches\RelationManagers\RecipientsRelationManager;
use App\Filament\Resources\PushDispatches\Schemas\PushDispatchInfolist;
use App\Filament\Resources\PushDispatches\Tables\PushDispatchesTable;
use App\Models\PushDispatch;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class PushDispatchResource extends Resource
{
    protected static ?string $model = PushDispatch::class;

    protected static ?string $navigationLabel = 'Push tarixçəsi';

    protected static ?string $modelLabel = 'push göndərişi';

    protected static ?string $pluralModelLabel = 'Push tarixçəsi';

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    protected static ?int $navigationSort = 6;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PushDispatchInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PushDispatchesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RecipientsRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['admin', 'serviceRequest'])
            ->withCount('recipients');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPushDispatches::route('/'),
            'view' => ViewPushDispatch::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
