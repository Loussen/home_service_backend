<?php

namespace App\Filament\Resources\ProviderProfiles\Pages;

use App\Filament\Resources\ProviderProfiles\ProviderProfileResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProviderProfile extends CreateRecord
{
    protected static string $resource = ProviderProfileResource::class;

    /** @var list<int> */
    private array $pendingCategoryIds = [];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->pendingCategoryIds = array_values($data['category_ids'] ?? []);
        $data['category_id'] = $this->pendingCategoryIds[0] ?? null;
        unset($data['category_ids']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record?->syncCategoryIds($this->pendingCategoryIds);
    }
}
