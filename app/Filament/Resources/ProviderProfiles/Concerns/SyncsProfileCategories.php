<?php

namespace App\Filament\Resources\ProviderProfiles\Concerns;

trait SyncsProfileCategories
{
    /** @var list<int> */
    private array $pendingCategoryIds = [];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function extractCategoryIds(array $data): array
    {
        $raw = [];
        try {
            $raw = $this->form->getRawState();
        } catch (\Throwable) {
            $raw = [];
        }

        $ids = $data['category_ids'] ?? $raw['category_ids'] ?? [];
        if (! is_array($ids)) {
            $ids = filled($ids) ? [$ids] : [];
        }

        $ids = array_values(array_unique(array_filter(
            array_map('intval', $ids),
            fn (int $id) => $id > 0,
        )));

        unset($data['category_ids']);
        $this->pendingCategoryIds = $ids;

        if ($ids !== []) {
            $data['category_id'] = $ids[0];
        } else {
            unset($data['category_id']);
        }

        return $data;
    }

    protected function persistCategoryIds(): void
    {
        if ($this->pendingCategoryIds === []) {
            return;
        }

        $this->record?->syncCategoryIds($this->pendingCategoryIds);
    }
}
