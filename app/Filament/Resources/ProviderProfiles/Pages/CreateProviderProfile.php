<?php

namespace App\Filament\Resources\ProviderProfiles\Pages;

use App\Filament\Resources\ProviderProfiles\Concerns\SyncsProfileCategories;
use App\Filament\Resources\ProviderProfiles\ProviderProfileResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProviderProfile extends CreateRecord
{
    use SyncsProfileCategories;

    protected static string $resource = ProviderProfileResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->extractCategoryIds($data);
    }

    protected function afterCreate(): void
    {
        $this->persistCategoryIds();
    }
}
