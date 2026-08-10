<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['slug' => 'nanny', 'name_az' => 'Dayə', 'name_en' => 'Nanny', 'icon' => 'nanny', 'sort_order' => 1],
            ['slug' => 'cleaner', 'name_az' => 'Təmizlikçi', 'name_en' => 'Cleaner', 'icon' => 'cleaner', 'sort_order' => 2],
            ['slug' => 'caregiver', 'name_az' => 'Baxıcı', 'name_en' => 'Caregiver', 'icon' => 'caregiver', 'sort_order' => 3],
            ['slug' => 'cook', 'name_az' => 'Aşpaz', 'name_en' => 'Cook', 'icon' => 'cook', 'sort_order' => 4],
            ['slug' => 'tutor', 'name_az' => 'Repetitor', 'name_en' => 'Tutor', 'icon' => 'tutor', 'sort_order' => 5],
        ];

        foreach ($items as $item) {
            Category::updateOrCreate(['slug' => $item['slug']], $item);
        }
    }
}
