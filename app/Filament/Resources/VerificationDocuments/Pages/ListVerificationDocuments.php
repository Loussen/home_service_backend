<?php

namespace App\Filament\Resources\VerificationDocuments\Pages;

use App\Filament\Resources\VerificationDocuments\VerificationDocumentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVerificationDocuments extends ListRecords
{
    protected static string $resource = VerificationDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
