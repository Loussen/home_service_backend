<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\ProviderProfile;
use App\Models\RequestMatch;
use App\Models\Review;
use App\Models\Schedule;
use App\Models\ServiceRequest;
use App\Models\Transaction;
use App\Models\User;
use App\Models\VerificationDocument;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    /**
     * Rich demo data for local/device debug.
     * OTP in non-production is always 123456.
     */
    public function run(): void
    {
        $categories = Category::query()->get()->keyBy('slug');
        if ($categories->isEmpty()) {
            $this->call(CategorySeeder::class);
            $categories = Category::query()->get()->keyBy('slug');
        }

        DB::transaction(function () use ($categories) {
            $this->seedClients();
            $this->seedExtraClients();
            $providers = $this->seedProviders($categories);
            $this->seedExtraProviders($providers);
            $this->seedServiceRequestsAndMatches($providers, $categories);
        });

        $clientCount = User::query()->where('active_role', 'client')->count();
        $providerCount = User::query()->where('active_role', 'provider')->count();
        $leafCount = Category::query()
            ->whereDoesntHave('children')
            ->count();

        $this->command?->newLine();
        $this->command?->info('=== Demo login (OTP: 123456) ===');
        $this->command?->table(
            ['Role', 'Phone', 'Name', 'Notes'],
            [
                ['client', '+994501111111', 'Aysel Məmmədova', 'Balans 25 AZN · aktiv sorğular'],
                ['client', '+994502222222', 'Rəşad Əliyev', 'Balans 5 AZN'],
                ['client', '+994511000001 … 020', 'Bulk ailə', '20 əlavə müştəri'],
                ['provider', '+994503333333', 'Nərminə Həsənova', 'Dayə · verified · VIP'],
                ['provider', '+994504444444', 'Kamran Quliyev', 'Təmizlik · multi-profile'],
                ['provider', '+994505555555', 'Leyla İsmayılova', 'Yaşlı baxımı + dayə'],
                ['provider', '+994506666666', 'Orxan Məlikov', 'Aşpaz'],
                ['provider', '+994507777777', 'Günel Rəhimli', 'Repetitor'],
                ['provider', '+994512000001 … 030', 'Bulk icraçı', '30 əlavə icraçı'],
            ]
        );
        $this->command?->info("Kateqoriya (yarpaq): {$leafCount} · Ailə: {$clientCount} · İcraçı: {$providerCount}");
    }

    private function seedClients(): void
    {
        $clients = [
            [
                'phone' => '+994501111111',
                'name' => 'Aysel Məmmədova',
                'balance' => 25.00,
            ],
            [
                'phone' => '+994502222222',
                'name' => 'Rəşad Əliyev',
                'balance' => 5.00,
            ],
        ];

        foreach ($clients as $data) {
            User::updateOrCreate(
                ['phone' => $data['phone']],
                [
                    'name' => $data['name'],
                    'active_role' => 'client',
                    'role_chosen_at' => now(),
                    'balance' => $data['balance'],
                    'status' => 'active',
                    'welcome_bonus_granted' => true,
                    'phone_verified_at' => now(),
                ]
            );
        }
    }

    private function seedExtraClients(): void
    {
        $first = ['Nigar', 'Elvin', 'Aytən', 'Tural', 'Sevinc', 'Rauf', 'Günay', 'Farid', 'Ləman', 'Kamal', 'Aynurə', 'Vüsal', 'Nərmin', 'Elnur', 'Fidan', 'Murad', 'Könül', 'Səid', 'Ülviyyə', 'Cavid'];
        $last = ['Hüseynov', 'Əliyeva', 'Qasımov', 'Məmmədova', 'İbrahimov', 'Rzayeva', 'Nəsirov', 'Səfərova', 'Abbasov', 'Karimova'];

        for ($i = 1; $i <= 20; $i++) {
            $name = $first[($i - 1) % count($first)].' '.$last[($i - 1) % count($last)];
            User::updateOrCreate(
                ['phone' => sprintf('+994511%06d', $i)],
                [
                    'name' => $name,
                    'active_role' => 'client',
                    'role_chosen_at' => now(),
                    'balance' => [10, 15, 20, 30, 50][$i % 5],
                    'status' => 'active',
                    'welcome_bonus_granted' => true,
                    'phone_verified_at' => now(),
                ]
            );
        }
    }

    /**
     * @param  array<int, ProviderProfile>  $createdProfiles
     */
    private function seedExtraProviders(array &$createdProfiles): void
    {
        $leaves = Category::query()
            ->whereDoesntHave('children')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($leaves->isEmpty()) {
            return;
        }

        $first = ['Aygün', 'Rəşad', 'Könül', 'Elçin', 'Səbinə', 'Tərlan', 'Nəzakət', 'Orxan', 'Gülşən', 'Kamran', 'Lalə', 'Vüqar', 'Sevda', 'Anar', 'Mehriban', 'Zaur', 'Arzu', 'Elvin', 'Nigar', 'Samir', 'Fəridə', 'Ramil', 'Şəhla', 'Ilqar', 'Aytac', 'Namiq', 'Günel', 'Kənan', 'Ülviyyə', 'Tofiq'];
        $last = ['Məmmədov', 'Əliyev', 'Hüseynova', 'Quliyeva', 'İsmayılov', 'Rəhimova', 'Mustafayev', 'Həsənova', 'Karimov', 'Nəsirova'];
        $districts = [
            ['Nərimanov', 40.4093, 49.8671],
            ['Yasamal', 40.3777, 49.8920],
            ['Nəsimi', 40.3950, 49.8500],
            ['Səbail', 40.3660, 49.8350],
            ['Xətai', 40.3830, 49.8720],
            ['Binəqədi', 40.4520, 49.8260],
            ['Nizami', 40.4180, 49.9400],
            ['Sabunçu', 40.4420, 49.9480],
        ];
        $slots = ['morning', 'afternoon', 'evening', 'night'];

        for ($i = 1; $i <= 30; $i++) {
            $leaf = $leaves[($i - 1) % $leaves->count()];
            $place = $districts[($i - 1) % count($districts)];
            $name = $first[($i - 1) % count($first)].' '.$last[($i - 1) % count($last)];
            $jitter = (($i % 7) - 3) * 0.004;

            $user = User::updateOrCreate(
                ['phone' => sprintf('+994512%06d', $i)],
                [
                    'name' => $name,
                    'active_role' => 'provider',
                    'role_chosen_at' => now(),
                    'balance' => 8 + ($i % 6) * 5,
                    'status' => 'active',
                    'welcome_bonus_granted' => true,
                    'phone_verified_at' => now(),
                ]
            );

            $title = $leaf->name_az.' — '.$place[0];
            $profile = ProviderProfile::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'category_id' => $leaf->id,
                    'title' => $title,
                ],
                [
                    'bio' => $place[0].' ərazisində '.$leaf->name_az.' xidməti. Seed profil.',
                    'is_verified' => $i % 3 === 0,
                    'is_vip' => $i % 7 === 0,
                    'latitude' => $place[1] + $jitter,
                    'longitude' => $place[2] + $jitter,
                    'city' => 'Bakı',
                    'district' => $place[0],
                    'rating_avg' => round(4.0 + ($i % 10) * 0.09, 2),
                    'rating_count' => 3 + ($i % 25),
                    'bumped_at' => $i % 5 === 0 ? now()->subHours($i % 12) : null,
                    'vip_expires_at' => $i % 7 === 0 ? now()->addDays(10) : null,
                    'is_active' => true,
                ]
            );

            $profile->syncCategoryIds([$leaf->id]);

            Schedule::query()->where('provider_profile_id', $profile->id)->delete();
            $days = $i % 2 === 0 ? [1, 2, 3, 4, 5] : [1, 2, 3, 4, 5, 6, 7];
            $timeSlots = $i % 3 === 0 ? $slots : ['morning', 'afternoon', 'evening'];
            $rows = [];
            foreach ($days as $day) {
                foreach ($timeSlots as $slot) {
                    $rows[] = [
                        'provider_profile_id' => $profile->id,
                        'day_of_week' => $day,
                        'time_slot' => $slot,
                        'is_available' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
            Schedule::insert($rows);

            $createdProfiles[] = $profile;
        }
    }

    /** @return array<int, ProviderProfile> */
    private function seedProviders($categories): array
    {
        $slots = ['morning', 'afternoon', 'evening', 'night'];

        $providers = [
            [
                'phone' => '+994503333333',
                'name' => 'Nərminə Həsənova',
                'balance' => 42.50,
                'profiles' => [
                    [
                        'slug' => 'infant-nanny',
                        'title' => 'Təcrübəli dayə — 8 il',
                        'bio' => 'Pedaqoji təhsilli dayə. Uşaqlarla məşğul olmaq, ev tapşırığına nəzarət.',
                        'lat' => 40.4093,
                        'lng' => 49.8671,
                        'district' => 'Nərimanov',
                        'verified' => true,
                        'vip' => true,
                        'rating' => 4.9,
                        'rating_count' => 28,
                        'bumped' => true,
                        'days' => [1, 2, 3, 4, 5],
                        'time_slots' => ['morning', 'afternoon', 'evening'],
                    ],
                    [
                        'slug' => 'pet-walking',
                        'title' => 'İt gəzdirmə — Nərimanov',
                        'bio' => 'Gündəlik və saatlıq it gəzintisi. Nərimanov / Gənclik.',
                        'lat' => 40.4093,
                        'lng' => 49.8671,
                        'district' => 'Nərimanov',
                        'verified' => true,
                        'vip' => false,
                        'rating' => 4.8,
                        'rating_count' => 12,
                        'bumped' => false,
                        'days' => [1, 2, 3, 4, 5, 6],
                        'time_slots' => ['morning', 'afternoon', 'evening'],
                    ],
                ],
            ],
            [
                'phone' => '+994504444444',
                'name' => 'Kamran Quliyev',
                'balance' => 18.00,
                'profiles' => [
                    [
                        'slug' => 'cleaner',
                        'title' => 'Evdə peşəkar təmizlik',
                        'bio' => 'Mənzil, ofis, after-repair təmizlik. Material özümüzdən.',
                        'lat' => 40.3777,
                        'lng' => 49.8920,
                        'district' => 'Yasamal',
                        'verified' => true,
                        'vip' => false,
                        'rating' => 4.7,
                        'rating_count' => 41,
                        'bumped' => false,
                        'days' => [1, 2, 3, 4, 5, 6],
                        'time_slots' => ['morning', 'afternoon'],
                    ],
                    [
                        'slug' => 'cook',
                        'title' => 'Ev yeməkləri / weekly cook',
                        'bio' => 'Həftəlik menyu, porsiya hazırlığı. Diyet variantları da var.',
                        'lat' => 40.3777,
                        'lng' => 49.8920,
                        'district' => 'Yasamal',
                        'verified' => false,
                        'vip' => false,
                        'rating' => 4.4,
                        'rating_count' => 12,
                        'bumped' => false,
                        'days' => [1, 3, 5],
                        'time_slots' => ['afternoon', 'evening'],
                    ],
                ],
            ],
            [
                'phone' => '+994505555555',
                'name' => 'Leyla İsmayılova',
                'balance' => 12.00,
                'profiles' => [
                    [
                        'slug' => 'elderly-care',
                        'title' => 'Yaşlı baxımı (evdə)',
                        'bio' => 'Qocalara qulluq, dərman nəzarəti, gəzinti yoldaşı.',
                        'lat' => 40.3950,
                        'lng' => 49.8500,
                        'district' => 'Nəsimi',
                        'verified' => true,
                        'vip' => false,
                        'rating' => 4.8,
                        'rating_count' => 15,
                        'bumped' => true,
                        'days' => [1, 2, 3, 4, 5, 6, 7],
                        'time_slots' => ['morning', 'afternoon', 'evening', 'night'],
                    ],
                    [
                        'slug' => 'infant-nanny',
                        'title' => 'Körpə dayəsi (0–3 yaş)',
                        'bio' => 'Yeni doğulmuş və körpə uşaqlara baxış təcrübəsi.',
                        'lat' => 40.3980,
                        'lng' => 49.8450,
                        'district' => 'Nəsimi',
                        'verified' => false,
                        'vip' => false,
                        'rating' => 4.5,
                        'rating_count' => 7,
                        'bumped' => false,
                        'days' => [1, 2, 3, 4, 5],
                        'time_slots' => ['morning', 'afternoon'],
                    ],
                ],
            ],
            [
                'phone' => '+994506666666',
                'name' => 'Orxan Məlikov',
                'balance' => 30.00,
                'profiles' => [
                    [
                        'slug' => 'cook',
                        'title' => 'Şəxsi aşpaz — restoran təcrübəsi',
                        'bio' => 'Azeri + Avropa mətbəxi. Tədbir və ailə nahar/şərab menyuları.',
                        'lat' => 40.4200,
                        'lng' => 49.8400,
                        'district' => 'Səbail',
                        'verified' => true,
                        'vip' => true,
                        'rating' => 4.95,
                        'rating_count' => 33,
                        'bumped' => true,
                        'days' => [5, 6, 7],
                        'time_slots' => ['afternoon', 'evening', 'night'],
                    ],
                ],
            ],
            [
                'phone' => '+994507777777',
                'name' => 'Günel Rəhimli',
                'balance' => 8.00,
                'profiles' => [
                    [
                        'slug' => 'tutor',
                        'title' => 'Riyaziyyat / İngilis dili',
                        'bio' => 'Məktəb və imtahan hazırlığı (SAT, daxili qəbul). Online & evdə.',
                        'lat' => 40.4300,
                        'lng' => 49.9000,
                        'district' => 'Xətai',
                        'verified' => false,
                        'vip' => false,
                        'rating' => 4.6,
                        'rating_count' => 19,
                        'bumped' => false,
                        'days' => [1, 2, 3, 4, 5, 6],
                        'time_slots' => ['afternoon', 'evening'],
                    ],
                ],
            ],
            // Extra nearby for map density
            [
                'phone' => '+994508888888',
                'name' => 'Səbinə Əliyeva',
                'balance' => 15.00,
                'profiles' => [
                    [
                        'slug' => 'infant-nanny',
                        'title' => 'Dayə — Nərimanov / Gənclik',
                        'bio' => 'Məktəbə qədər və məktəbli uşaqlar.',
                        'lat' => 40.4050,
                        'lng' => 49.8750,
                        'district' => 'Nərimanov',
                        'verified' => false,
                        'vip' => false,
                        'rating' => 4.2,
                        'rating_count' => 5,
                        'bumped' => false,
                        'days' => [1, 3, 5, 6],
                        'time_slots' => ['afternoon', 'evening'],
                    ],
                ],
            ],
            [
                'phone' => '+994509999999',
                'name' => 'Elçin Mustafayev',
                'balance' => 6.00,
                'profiles' => [
                    [
                        'slug' => 'cleaner',
                        'title' => 'Express təmizlik 2–4 saat',
                        'bio' => 'Təcili sifariş qəbul edirəm.',
                        'lat' => 40.4150,
                        'lng' => 49.8550,
                        'district' => 'Nəsimi',
                        'verified' => true,
                        'vip' => false,
                        'rating' => 4.3,
                        'rating_count' => 22,
                        'bumped' => false,
                        'days' => [1, 2, 3, 4, 5, 6, 7],
                        'time_slots' => $slots,
                    ],
                ],
            ],
        ];

        $createdProfiles = [];

        foreach ($providers as $p) {
            $user = User::updateOrCreate(
                ['phone' => $p['phone']],
                [
                    'name' => $p['name'],
                    'active_role' => 'provider',
                    'role_chosen_at' => now(),
                    'balance' => $p['balance'],
                    'status' => 'active',
                    'welcome_bonus_granted' => true,
                    'phone_verified_at' => now(),
                ]
            );

            Transaction::query()
                ->where('user_id', $user->id)
                ->where('type', 'credit_welcome_bonus')
                ->delete();

            Transaction::create([
                'user_id' => $user->id,
                'amount' => 10,
                'type' => 'credit_welcome_bonus',
                'payment_method' => 'system_bonus',
                'status' => 'completed',
                'reference' => 'seed_welcome_'.$user->id,
                'created_at' => now()->subDays(14),
            ]);

            if ($p['balance'] > 10) {
                Transaction::create([
                    'user_id' => $user->id,
                    'amount' => $p['balance'] - 10,
                    'type' => 'top_up',
                    'payment_method' => 'card',
                    'status' => 'completed',
                    'reference' => 'seed_topup_'.$user->id,
                    'created_at' => now()->subDays(7),
                ]);
            }

            foreach ($p['profiles'] as $profileData) {
                $category = $categories[$profileData['slug']];

                $profile = ProviderProfile::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'category_id' => $category->id,
                        'title' => $profileData['title'],
                    ],
                    [
                        'bio' => $profileData['bio'],
                        'is_verified' => $profileData['verified'],
                        'is_vip' => $profileData['vip'],
                        'latitude' => $profileData['lat'],
                        'longitude' => $profileData['lng'],
                        'city' => 'Bakı',
                        'district' => $profileData['district'],
                        'rating_avg' => $profileData['rating'],
                        'rating_count' => $profileData['rating_count'],
                        'bumped_at' => $profileData['bumped'] ? now()->subHours(3) : null,
                        'vip_expires_at' => $profileData['vip'] ? now()->addDays(20) : null,
                        'is_active' => true,
                    ]
                );

                $profile->syncCategoryIds([$category->id]);

                Schedule::query()->where('provider_profile_id', $profile->id)->delete();
                $rows = [];
                foreach ($profileData['days'] as $day) {
                    foreach ($profileData['time_slots'] as $slot) {
                        $rows[] = [
                            'provider_profile_id' => $profile->id,
                            'day_of_week' => $day,
                            'time_slot' => $slot,
                            'is_available' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
                Schedule::insert($rows);

                if ($profileData['verified']) {
                    VerificationDocument::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'provider_profile_id' => $profile->id,
                            'document_type' => 'id_card',
                        ],
                        [
                            'file_url' => 'demo/id_'.$user->id.'.jpg',
                            'status' => 'approved',
                            'admin_note' => 'Seed: auto-approved',
                            'reviewed_at' => now()->subDays(5),
                        ]
                    );
                }

                $createdProfiles[] = $profile;
            }
        }

        return $createdProfiles;
    }

    /** @param  array<int, ProviderProfile>  $profiles */
    private function seedServiceRequestsAndMatches(array $profiles, $categories): void
    {
        $client = User::where('phone', '+994501111111')->first();
        $client2 = User::where('phone', '+994502222222')->first();
        if (! $client) {
            return;
        }

        $nanny = $categories['nanny'];
        $cleaner = $categories['cleaner'];
        $nannyFamily = Category::idsWithDescendants((int) $nanny->id);

        $nannyProfiles = collect($profiles)->filter(
            fn (ProviderProfile $p) => in_array((int) $p->category_id, $nannyFamily, true)
        )->values();

        $cleanerProfiles = collect($profiles)->filter(
            fn (ProviderProfile $p) => (int) $p->category_id === (int) $cleaner->id
        )->values();

        // Active matched nanny search
        $req1 = ServiceRequest::updateOrCreate(
            [
                'user_id' => $client->id,
                'transcribed_text' => 'Nərimanovda sabah günorta 2-3 saata dayə axtarıram',
            ],
            [
                'category_id' => $nanny->id,
                'parsed_criteria' => [
                    'category_slug' => 'nanny',
                    'time_slot' => 'afternoon',
                    'raw_text' => 'Nərimanovda sabah günorta 2-3 saata dayə axtarıram',
                ],
                'is_urgent' => false,
                'latitude' => 40.4093,
                'longitude' => 49.8671,
                'address' => 'Bakı, Nərimanov',
                'status' => 'matched',
            ]
        );

        RequestMatch::query()->where('service_request_id', $req1->id)->delete();
        foreach ($nannyProfiles as $i => $profile) {
            $distance = round(0.3 + $i * 0.8, 2);
            $score = round(92 - $i * 7.5, 2);
            RequestMatch::create([
                'service_request_id' => $req1->id,
                'provider_profile_id' => $profile->id,
                'match_score' => max(55, $score),
                'distance_km' => $distance,
                'score_breakdown' => [
                    'distance' => 90 - $i * 5,
                    'schedule' => 100,
                    'verified' => $profile->is_verified ? 10 : 0,
                    'vip' => $profile->is_vip ? 8 : 0,
                    'rating' => min(10, $profile->rating_avg * 2),
                ],
                'notified' => $i === 0,
            ]);
        }

        // Urgent cleaner request
        $req2 = ServiceRequest::updateOrCreate(
            [
                'user_id' => $client->id,
                'transcribed_text' => 'Bu axşam təcili təmizlik lazımdır, 3 otaqlı mənzil',
            ],
            [
                'category_id' => $cleaner->id,
                'parsed_criteria' => [
                    'category_slug' => 'cleaner',
                    'time_slot' => 'evening',
                    'raw_text' => 'Bu axşam təcili təmizlik lazımdır',
                ],
                'is_urgent' => true,
                'urgent_until' => now()->addHours(2),
                'latitude' => 40.3950,
                'longitude' => 49.8500,
                'address' => 'Bakı, Nəsimi',
                'status' => 'matched',
            ]
        );

        Transaction::create([
            'user_id' => $client->id,
            'amount' => -2,
            'type' => 'urgent_fee',
            'payment_method' => 'wallet',
            'status' => 'completed',
            'meta' => ['service_request_id' => $req2->id],
            'created_at' => now()->subHour(),
        ]);

        RequestMatch::query()->where('service_request_id', $req2->id)->delete();
        foreach ($cleanerProfiles as $i => $profile) {
            RequestMatch::create([
                'service_request_id' => $req2->id,
                'provider_profile_id' => $profile->id,
                'match_score' => 88 - $i * 6,
                'distance_km' => round(1.2 + $i, 2),
                'score_breakdown' => ['distance' => 75, 'schedule' => 100],
                'notified' => true,
            ]);
        }

        // Completed request + review
        if ($client2 && $nannyProfiles->isNotEmpty()) {
            $profile = $nannyProfiles->first();
            $req3 = ServiceRequest::updateOrCreate(
                [
                    'user_id' => $client2->id,
                    'transcribed_text' => 'Keçən həftə dayə xidməti (tamamlanıb)',
                ],
                [
                    'category_id' => $nanny->id,
                    'parsed_criteria' => ['category_slug' => 'nanny', 'time_slot' => 'morning'],
                    'is_urgent' => false,
                    'latitude' => 40.4100,
                    'longitude' => 49.8700,
                    'address' => 'Bakı',
                    'status' => 'completed',
                ]
            );

            Review::updateOrCreate(
                [
                    'service_request_id' => $req3->id,
                    'provider_profile_id' => $profile->id,
                    'client_id' => $client2->id,
                ],
                [
                    'reviewer_id' => $client2->id,
                    'reviewee_id' => $profile->user_id,
                    'rating' => 5,
                    'comment' => 'Əla dayə idi, uşaq razı qaldı. Seed review.',
                ]
            );

            Transaction::create([
                'user_id' => $client2->id,
                'amount' => 20,
                'type' => 'top_up',
                'payment_method' => 'card',
                'status' => 'completed',
                'reference' => 'seed_client2_topup',
                'created_at' => now()->subDays(10),
            ]);
        }
    }
}
