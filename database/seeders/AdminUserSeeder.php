<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        Admin::updateOrCreate(
            ['email' => 'admin@homeservice.local'],
            [
                'name' => 'Admin',
                'password' => 'password',
                'is_active' => true,
            ]
        );

        $this->command?->info('Admin panel: admin@homeservice.local / password');
    }
}
