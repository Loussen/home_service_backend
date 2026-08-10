<?php

namespace App\Repositories;

use App\Models\ServiceRequest;
use Illuminate\Database\Eloquent\Collection;

class ServiceRequestRepository
{
    public function create(array $data): ServiceRequest
    {
        return ServiceRequest::create($data);
    }

    public function update(ServiceRequest $request, array $data): ServiceRequest
    {
        $request->update($data);

        return $this->findById($request->id) ?? $request->fresh();
    }

    public function findForUser(int $userId, int $id): ?ServiceRequest
    {
        return ServiceRequest::with($this->detailRelations())
            ->where('user_id', $userId)
            ->where('id', $id)
            ->first();
    }

    public function listForUser(int $userId): Collection
    {
        return ServiceRequest::with(['category'])
            ->where('user_id', $userId)
            ->latest()
            ->get();
    }

    public function findById(int $id): ?ServiceRequest
    {
        return ServiceRequest::with($this->detailRelations())->find($id);
    }

    private function detailRelations(): array
    {
        return [
            'category',
            'matches' => fn ($q) => $q->orderByDesc('match_score'),
            'matches.providerProfile.user',
            'matches.providerProfile.category',
        ];
    }
}
