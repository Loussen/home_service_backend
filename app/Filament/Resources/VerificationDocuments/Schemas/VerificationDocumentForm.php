<?php

namespace App\Filament\Resources\VerificationDocuments\Schemas;

use App\Filament\Support\FormComponents;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class VerificationDocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                FormComponents::userSelect(),
                FormComponents::providerProfileSelect(),
                TextInput::make('document_type')
                    ->label('Sənəd növü')
                    ->required()
                    ->default('id_card'),
                TextInput::make('file_url')
                    ->label('Fayl URL')
                    ->url()
                    ->required(),
                Select::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Gözləyir',
                        'approved' => 'Təsdiqlənib',
                        'rejected' => 'Rədd edilib',
                    ])
                    ->default('pending')
                    ->required(),
                Textarea::make('admin_note')
                    ->label('Admin qeydi')
                    ->columnSpanFull(),
                DateTimePicker::make('reviewed_at')
                    ->label('Yoxlama vaxtı'),
            ]);
    }
}
