<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $tree = [
            [
                'slug' => 'family-care',
                'name_az' => 'Ailə qayğısı',
                'name_en' => 'Family care',
                'icon' => 'family',
                'sort_order' => 1,
                'children' => [
                    [
                        'slug' => 'nanny',
                        'name_az' => 'Dayə',
                        'name_en' => 'Nanny',
                        'icon' => 'nanny',
                        'sort_order' => 1,
                        'children' => [
                            ['slug' => 'infant-nanny', 'name_az' => 'Körpə dayəsi', 'name_en' => 'Infant nanny', 'icon' => 'nanny', 'sort_order' => 1],
                            ['slug' => 'school-nanny', 'name_az' => 'Məktəbli dayəsi', 'name_en' => 'School-age nanny', 'icon' => 'nanny', 'sort_order' => 2],
                        ],
                    ],
                    [
                        'slug' => 'caregiver',
                        'name_az' => 'Baxıcı',
                        'name_en' => 'Caregiver',
                        'icon' => 'caregiver',
                        'sort_order' => 2,
                    ],
                ],
            ],
            [
                'slug' => 'home-services',
                'name_az' => 'Ev xidmətləri',
                'name_en' => 'Home services',
                'icon' => 'home',
                'sort_order' => 2,
                'children' => [
                    ['slug' => 'cleaner', 'name_az' => 'Təmizlikçi', 'name_en' => 'Cleaner', 'icon' => 'cleaner', 'sort_order' => 1],
                    ['slug' => 'cook', 'name_az' => 'Aşpaz', 'name_en' => 'Cook', 'icon' => 'cook', 'sort_order' => 2],
                    ['slug' => 'pet-walking', 'name_az' => 'İt gəzdirmə', 'name_en' => 'Dog walking', 'icon' => 'pet', 'sort_order' => 3],
                ],
            ],
            [
                'slug' => 'education',
                'name_az' => 'Təhsil',
                'name_en' => 'Education',
                'icon' => 'education',
                'sort_order' => 3,
                'children' => [
                    ['slug' => 'tutor', 'name_az' => 'Repetitor', 'name_en' => 'Tutor', 'icon' => 'tutor', 'sort_order' => 1],
                ],
            ],
        ];

        foreach ($tree as $node) {
            $this->upsertNode($node, null);
        }
    }

    private function upsertNode(array $node, ?int $parentId): void
    {
        $children = $node['children'] ?? [];
        unset($node['children']);

        $category = Category::updateOrCreate(
            ['slug' => $node['slug']],
            [
                ...$node,
                'parent_id' => $parentId,
                'is_active' => true,
            ]
        );

        foreach ($children as $child) {
            $this->upsertNode($child, $category->id);
        }
    }
}
