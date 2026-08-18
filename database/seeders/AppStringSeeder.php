<?php

namespace Database\Seeders;

use App\Services\AppStringSyncService;
use Illuminate\Database\Seeder;

class AppStringSeeder extends Seeder
{
    public function run(): void
    {
        app(AppStringSyncService::class)->syncFromFiles();
    }
}
