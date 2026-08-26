<?php

namespace App\Filament\Resources\ProviderProfiles\Pages;

use App\Filament\Resources\ProviderProfiles\Concerns\SyncsProfileCategories;
use App\Filament\Resources\ProviderProfiles\ProviderProfileResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProviderProfile extends EditRecord
{
    use SyncsProfileCategories;

    protected static string $resource = ProviderProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $ids = $this->record?->categories()->pluck('categories.id')->all() ?? [];
        if ($ids === [] && $this->record?->category_id) {
            $ids = [$this->record->category_id];
        }
        $data['category_ids'] = $ids;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->extractCategoryIds($data);
    }

    protected function afterSave(): void
    {
        $this->persistCategoryIds();
    }
}
