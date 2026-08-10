<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    public function findByPhone(string $phone): ?User
    {
        return User::where('phone', $phone)->first();
    }

    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(User $user, array $data): User
    {
        $user->update($data);

        return $user->fresh();
    }

    public function creditBalance(User $user, float $amount): User
    {
        $user->increment('balance', $amount);

        return $user->fresh();
    }

    public function debitBalance(User $user, float $amount): User
    {
        $user->decrement('balance', $amount);

        return $user->fresh();
    }
}
