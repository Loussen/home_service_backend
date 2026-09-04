<?php

namespace App\Repositories;

use App\Models\ServiceRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
            ->withCount('matches')
            ->where('user_id', $userId)
            ->latest()
            ->get();
    }

    /**
     * @param  'all'|'matched'|'unmatched'  $filter
     */
    public function paginateForUser(
        int $userId,
        int $page = 1,
        int $perPage = 10,
        string $filter = 'all',
    ): LengthAwarePaginator {
        $perPage = max(1, min(50, $perPage));
        $page = max(1, $page);
        $filter = in_array($filter, ['matched', 'unmatched'], true) ? $filter : 'all';

        $query = ServiceRequest::with(['category'])
            ->withCount('matches')
            ->where('user_id', $userId);

        if ($filter === 'matched') {
            $query->where(function ($q) {
                $q->where('status', 'matched')->orWhereHas('matches');
            });
        } elseif ($filter === 'unmatched') {
            $query->where('status', '!=', 'matched')->whereDoesntHave('matches');
        }

        return $query->latest()->paginate($perPage, ['*'], 'page', $page);
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
            'matches.providerProfile.categories',
        ];
    }
}
