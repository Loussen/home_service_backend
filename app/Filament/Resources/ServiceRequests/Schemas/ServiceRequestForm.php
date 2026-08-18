<?php

namespace App\Filament\Resources\ServiceRequests\Schemas;

use App\Filament\Support\FormComponents;
use App\Models\Category;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ServiceRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                FormComponents::userSelect(label: 'Müştəri'),
                Select::make('category_id')
                    ->label('Kateqoriya')
                    ->options(fn () => Category::treeLabelMap())
                    ->searchable()
                    ->preload(),
                TextInput::make('raw_audio_url')
                    ->label('Audio URL')
                    ->helperText('Səs yazısını dinləmək üçün sorğunun baxış səhifəsini açın.'),
                Textarea::make('transcribed_text')
                    ->label('Transkripsiya')
                    ->columnSpanFull(),
                TextInput::make('parsed_criteria')
                    ->label('AI meyarlar'),
                Toggle::make('is_urgent')
                    ->label('Təcili'),
                TextInput::make('latitude')
                    ->label('Enlik')
                    ->required()
                    ->numeric(),
                TextInput::make('longitude')
                    ->label('Uzunluq')
                    ->required()
                    ->numeric(),
                TextInput::make('address')
                    ->label('Ünvan'),
                Select::make('status')
                    ->label('Status')
                    ->options([
                        'processing' => 'Emal olunur',
                        'active' => 'Aktiv',
                        'matched' => 'Uyğunlaşıb',
                        'completed' => 'Tamamlanıb',
                        'cancelled' => 'Ləğv edilib',
                    ])
                    ->default('processing')
                    ->required(),
                DateTimePicker::make('bumped_at')
                    ->label('Bump vaxtı'),
                DateTimePicker::make('urgent_until')
                    ->label('Təcili bitmə'),
            ]);
    }
}
