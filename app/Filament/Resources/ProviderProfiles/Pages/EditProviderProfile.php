<?php

namespace App\Filament\Resources\ProviderProfiles\Pages;

use App\Filament\Resources\ProviderProfiles\ProviderProfileResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProviderProfile extends EditRecord
{
    protected static string $resource = ProviderProfileResource::class;

    /** @var list<int> */
    private array $pendingCategoryIds = [];

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
        $this->pendingCategoryIds = array_values($data['category_ids'] ?? []);
        $data['category_id'] = $this->pendingCategoryIds[0] ?? $data['category_id'] ?? null;
        unset($data['category_ids']);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record?->syncCategoryIds($this->pendingCategoryIds);
    }
}
