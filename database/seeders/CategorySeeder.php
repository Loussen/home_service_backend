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
                            ['slug' => 'newborn-nanny', 'name_az' => 'Yeni doğulan dayəsi', 'name_en' => 'Newborn nanny', 'icon' => 'nanny', 'sort_order' => 3],
                            ['slug' => 'overnight-nanny', 'name_az' => 'Gecə dayəsi', 'name_en' => 'Overnight nanny', 'icon' => 'nanny', 'sort_order' => 4],
                            ['slug' => 'after-school-nanny', 'name_az' => 'Dərsdən sonra dayə', 'name_en' => 'After-school nanny', 'icon' => 'nanny', 'sort_order' => 5],
                        ],
                    ],
                    [
                        'slug' => 'caregiver',
                        'name_az' => 'Baxıcı',
                        'name_en' => 'Caregiver',
                        'icon' => 'caregiver',
                        'sort_order' => 2,
                        'children' => [
                            ['slug' => 'elderly-care', 'name_az' => 'Yaşlı baxımı', 'name_en' => 'Elderly care', 'icon' => 'caregiver', 'sort_order' => 1],
                            ['slug' => 'disability-care', 'name_az' => 'Əlil baxımı', 'name_en' => 'Disability care', 'icon' => 'caregiver', 'sort_order' => 2],
                            ['slug' => 'postpartum-care', 'name_az' => 'Doğuşdan sonra qulluq', 'name_en' => 'Postpartum care', 'icon' => 'caregiver', 'sort_order' => 3],
                            ['slug' => 'companion', 'name_az' => 'Yoldaş / müşayiət', 'name_en' => 'Companion', 'icon' => 'caregiver', 'sort_order' => 4],
                        ],
                    ],
                    [
                        'slug' => 'housework-help',
                        'name_az' => 'Ev köməyi',
                        'name_en' => 'House help',
                        'icon' => 'family',
                        'sort_order' => 3,
                        'children' => [
                            ['slug' => 'housekeeper', 'name_az' => 'Ev işçisi', 'name_en' => 'Housekeeper', 'icon' => 'home', 'sort_order' => 1],
                            ['slug' => 'laundry-help', 'name_az' => 'Paltar yuma / ütü', 'name_en' => 'Laundry & ironing', 'icon' => 'home', 'sort_order' => 2],
                            ['slug' => 'grocery-help', 'name_az' => 'Market / ev tapşırığı', 'name_en' => 'Errands & groceries', 'icon' => 'home', 'sort_order' => 3],
                        ],
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
                    ['slug' => 'deep-cleaning', 'name_az' => 'Dərin təmizlik', 'name_en' => 'Deep cleaning', 'icon' => 'cleaner', 'sort_order' => 2],
                    ['slug' => 'window-cleaning', 'name_az' => 'Pəncərə təmizliyi', 'name_en' => 'Window cleaning', 'icon' => 'cleaner', 'sort_order' => 3],
                    ['slug' => 'cook', 'name_az' => 'Aşpaz', 'name_en' => 'Cook', 'icon' => 'cook', 'sort_order' => 4],
                    ['slug' => 'weekly-cook', 'name_az' => 'Həftəlik yemək', 'name_en' => 'Weekly cook', 'icon' => 'cook', 'sort_order' => 5],
                    ['slug' => 'plumber', 'name_az' => 'Santexnik', 'name_en' => 'Plumber', 'icon' => 'home', 'sort_order' => 6],
                    ['slug' => 'electrician', 'name_az' => 'Elektrik', 'name_en' => 'Electrician', 'icon' => 'home', 'sort_order' => 7],
                    ['slug' => 'handyman', 'name_az' => 'Usta / xırda təmir', 'name_en' => 'Handyman', 'icon' => 'home', 'sort_order' => 8],
                    ['slug' => 'ac-service', 'name_az' => 'Kondisioner ustası', 'name_en' => 'AC technician', 'icon' => 'home', 'sort_order' => 9],
                    ['slug' => 'painter', 'name_az' => 'Rəngsaz', 'name_en' => 'Painter', 'icon' => 'home', 'sort_order' => 10],
                    ['slug' => 'mover', 'name_az' => 'Yükdaşıma / köç', 'name_en' => 'Movers', 'icon' => 'home', 'sort_order' => 11],
                    ['slug' => 'gardener', 'name_az' => 'Bağban', 'name_en' => 'Gardener', 'icon' => 'home', 'sort_order' => 12],
                ],
            ],
            [
                'slug' => 'pets',
                'name_az' => 'Ev heyvanı',
                'name_en' => 'Pets',
                'icon' => 'pet',
                'sort_order' => 3,
                'children' => [
                    ['slug' => 'pet-walking', 'name_az' => 'İt gəzdirmə', 'name_en' => 'Dog walking', 'icon' => 'pet', 'sort_order' => 1],
                    ['slug' => 'pet-sitting', 'name_az' => 'Heyvan baxımı', 'name_en' => 'Pet sitting', 'icon' => 'pet', 'sort_order' => 2],
                    ['slug' => 'pet-grooming', 'name_az' => 'Heyvan grooming', 'name_en' => 'Pet grooming', 'icon' => 'pet', 'sort_order' => 3],
                ],
            ],
            [
                'slug' => 'education',
                'name_az' => 'Təhsil',
                'name_en' => 'Education',
                'icon' => 'education',
                'sort_order' => 4,
                'children' => [
                    ['slug' => 'tutor', 'name_az' => 'Repetitor', 'name_en' => 'Tutor', 'icon' => 'tutor', 'sort_order' => 1],
                    ['slug' => 'math-tutor', 'name_az' => 'Riyaziyyat repetitoru', 'name_en' => 'Math tutor', 'icon' => 'tutor', 'sort_order' => 2],
                    ['slug' => 'english-tutor', 'name_az' => 'İngilis dili', 'name_en' => 'English tutor', 'icon' => 'tutor', 'sort_order' => 3],
                    ['slug' => 'az-tutor', 'name_az' => 'Azərbaycan dili / ədəbiyyat', 'name_en' => 'Azerbaijani tutor', 'icon' => 'tutor', 'sort_order' => 4],
                    ['slug' => 'music-teacher', 'name_az' => 'Musiqi müəllimi', 'name_en' => 'Music teacher', 'icon' => 'tutor', 'sort_order' => 5],
                    ['slug' => 'exam-prep', 'name_az' => 'İmtahan hazırlığı', 'name_en' => 'Exam prep', 'icon' => 'tutor', 'sort_order' => 6],
                ],
            ],
            [
                'slug' => 'personal',
                'name_az' => 'Şəxsi xidmətlər',
                'name_en' => 'Personal services',
                'icon' => 'family',
                'sort_order' => 5,
                'children' => [
                    ['slug' => 'driver', 'name_az' => 'Sürücü', 'name_en' => 'Driver', 'icon' => 'home', 'sort_order' => 1],
                    ['slug' => 'courier', 'name_az' => 'Kuryer', 'name_en' => 'Courier', 'icon' => 'home', 'sort_order' => 2],
                    ['slug' => 'massage', 'name_az' => 'Evə masaj', 'name_en' => 'Home massage', 'icon' => 'family', 'sort_order' => 3],
                    ['slug' => 'hairdresser-home', 'name_az' => 'Evə bərbər / stilist', 'name_en' => 'Home hairdresser', 'icon' => 'family', 'sort_order' => 4],
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
