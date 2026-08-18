<?php

namespace App\Filament\Resources\VerificationDocuments\Pages;

use App\Filament\Resources\VerificationDocuments\VerificationDocumentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVerificationDocument extends EditRecord
{
    protected static string $resource = VerificationDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
