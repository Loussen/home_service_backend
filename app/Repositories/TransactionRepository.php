<?php

namespace App\Repositories;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class TransactionRepository
{
    public function create(array $data): Transaction
    {
        $data['created_at'] = $data['created_at'] ?? now();

        return Transaction::create($data);
    }

    public function listForUser(User $user, int $limit = 50): Collection
    {
        return Transaction::where('user_id', $user->id)
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }
}
