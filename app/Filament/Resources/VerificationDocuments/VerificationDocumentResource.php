<?php

namespace App\Filament\Resources\VerificationDocuments;

use App\Filament\Resources\VerificationDocuments\Pages\CreateVerificationDocument;
use App\Filament\Resources\VerificationDocuments\Pages\EditVerificationDocument;
use App\Filament\Resources\VerificationDocuments\Pages\ListVerificationDocuments;
use App\Filament\Resources\VerificationDocuments\Schemas\VerificationDocumentForm;
use App\Filament\Resources\VerificationDocuments\Tables\VerificationDocumentsTable;
use App\Models\VerificationDocument;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class VerificationDocumentResource extends Resource
{
    protected static ?string $model = VerificationDocument::class;

    protected static ?string $navigationLabel = 'Sənədlər';

    protected static ?string $modelLabel = 'sənəd';

    protected static ?string $pluralModelLabel = 'Sənədlər';

    protected static ?int $navigationSort = 5;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    public static function form(Schema $schema): Schema
    {
        return VerificationDocumentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VerificationDocumentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVerificationDocuments::route('/'),
            'create' => CreateVerificationDocument::route('/create'),
            'edit' => EditVerificationDocument::route('/{record}/edit'),
        ];
    }
}
