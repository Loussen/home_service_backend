<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\District;
use App\Models\ProviderProfile;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $sort = 0;

        foreach ($this->tree() as $node) {
            $sort++;
            $city = City::updateOrCreate(
                ['slug' => $node['slug']],
                [
                    'name' => $node['name'],
                    'type' => $node['type'],
                    'sort_order' => $sort,
                    'is_active' => true,
                ]
            );

            $dSort = 0;
            foreach ($node['districts'] as $district) {
                $dSort++;
                District::updateOrCreate(
                    [
                        'city_id' => $city->id,
                        'slug' => $district['slug'],
                    ],
                    [
                        'name' => $district['name'],
                        'sort_order' => $dSort,
                        'is_active' => true,
                    ]
                );
            }
        }

        ProviderProfile::query()->each(function (ProviderProfile $profile) {
            if (! $profile->city) {
                return;
            }
            $city = City::query()->where('name', $profile->city)->first();
            if (! $city) {
                return;
            }
            $district = $profile->district
                ? District::query()->where('city_id', $city->id)->where('name', $profile->district)->first()
                : $city->districts()->first();
            $profile->updateQuietly([
                'city_id' => $city->id,
                'district_id' => $district?->id,
            ]);
        });
    }

    /**
     * @return list<array{slug: string, name: string, type: string, districts: list<array{slug: string, name: string}>}>
     */
    private function tree(): array
    {
        $d = fn (string $slug, string $name) => ['slug' => $slug, 'name' => $name];
        $self = fn (string $slug, string $name) => [$d($slug, $name)];

        return [
            [
                'slug' => 'baki',
                'name' => 'Bakı',
                'type' => 'city',
                'districts' => [
                    $d('bineqedi', 'Binəqədi'),
                    $d('xetai', 'Xətai'),
                    $d('xezer', 'Xəzər'),
                    $d('qaradag', 'Qaradağ'),
                    $d('nerimanov', 'Nərimanov'),
                    $d('nesimi', 'Nəsimi'),
                    $d('nizami', 'Nizami'),
                    $d('pirallahi', 'Pirallahı'),
                    $d('sabuncu', 'Sabunçu'),
                    $d('sebail', 'Səbail'),
                    $d('suraxani', 'Suraxanı'),
                    $d('yasamal', 'Yasamal'),
                ],
            ],
            [
                'slug' => 'gence',
                'name' => 'Gəncə',
                'type' => 'city',
                'districts' => [
                    $d('kepaz', 'Kəpəz'),
                    $d('nizami', 'Nizami'),
                ],
            ],
            [
                'slug' => 'sumqayit',
                'name' => 'Sumqayıt',
                'type' => 'city',
                'districts' => $self('sumqayit', 'Sumqayıt'),
            ],
            [
                'slug' => 'mingecevir',
                'name' => 'Mingəçevir',
                'type' => 'city',
                'districts' => $self('mingecevir', 'Mingəçevir'),
            ],
            [
                'slug' => 'naftalan',
                'name' => 'Naftalan',
                'type' => 'city',
                'districts' => $self('naftalan', 'Naftalan'),
            ],
            [
                'slug' => 'seki-city',
                'name' => 'Şəki',
                'type' => 'city',
                'districts' => $self('seki', 'Şəki'),
            ],
            [
                'slug' => 'sirvan',
                'name' => 'Şirvan',
                'type' => 'city',
                'districts' => $self('sirvan', 'Şirvan'),
            ],
            [
                'slug' => 'yevlax-city',
                'name' => 'Yevlax',
                'type' => 'city',
                'districts' => $self('yevlax', 'Yevlax'),
            ],
            [
                'slug' => 'lenkeran-city',
                'name' => 'Lənkəran',
                'type' => 'city',
                'districts' => $self('lenkeran', 'Lənkəran'),
            ],
            [
                'slug' => 'naxcivan',
                'name' => 'Naxçıvan',
                'type' => 'city',
                'districts' => $self('naxcivan', 'Naxçıvan'),
            ],
            [
                'slug' => 'xankendi',
                'name' => 'Xankəndi',
                'type' => 'city',
                'districts' => $self('xankendi', 'Xankəndi'),
            ],
            ...$this->rayons($self),
        ];
    }

    /**
     * @param  callable(string, string): list<array{slug: string, name: string}>  $self
     * @return list<array{slug: string, name: string, type: string, districts: list<array{slug: string, name: string}>}>
     */
    private function rayons(callable $self): array
    {
        $names = [
            'absheron' => 'Abşeron',
            'agcabedi' => 'Ağcabədi',
            'agdam' => 'Ağdam',
            'agdas' => 'Ağdaş',
            'agstafa' => 'Ağstafa',
            'agsu' => 'Ağsu',
            'astara' => 'Astara',
            'babek' => 'Babək',
            'balaken' => 'Balakən',
            'beyleqan' => 'Beyləqan',
            'berde' => 'Bərdə',
            'bilesuvar' => 'Biləsuvar',
            'cebrayil' => 'Cəbrayıl',
            'celilabad' => 'Cəlilabad',
            'culfa' => 'Culfa',
            'daskesen' => 'Daşkəsən',
            'fuzuli' => 'Füzuli',
            'gedebey' => 'Gədəbəy',
            'goranboy' => 'Goranboy',
            'goycay' => 'Göyçay',
            'goygol' => 'Göygöl',
            'haciqabul' => 'Hacıqabul',
            'xacmaz' => 'Xaçmaz',
            'xizi' => 'Xızı',
            'xocali' => 'Xocalı',
            'xocavend' => 'Xocavənd',
            'imisli' => 'İmişli',
            'ismayilli' => 'İsmayıllı',
            'kelbecer' => 'Kəlbəcər',
            'kengerli' => 'Kəngərli',
            'kurdemir' => 'Kürdəmir',
            'qax' => 'Qax',
            'qazax' => 'Qazax',
            'qebele' => 'Qəbələ',
            'qobustan' => 'Qobustan',
            'quba' => 'Quba',
            'qubadli' => 'Qubadlı',
            'qusar' => 'Qusar',
            'lacin' => 'Laçın',
            'lerik' => 'Lerik',
            'masalli' => 'Masallı',
            'neftcala' => 'Neftçala',
            'oguz' => 'Oğuz',
            'ordubad' => 'Ordubad',
            'saatli' => 'Saatlı',
            'sabirabad' => 'Sabirabad',
            'salyan' => 'Salyan',
            'samux' => 'Samux',
            'sederek' => 'Sədərək',
            'siyezen' => 'Siyəzən',
            'sabran' => 'Şabran',
            'sahbuz' => 'Şahbuz',
            'samaxi' => 'Şamaxı',
            'semkir' => 'Şəmkir',
            'serur' => 'Şərur',
            'susa' => 'Şuşa',
            'terter' => 'Tərtər',
            'tovuz' => 'Tovuz',
            'ucar' => 'Ucar',
            'yardimli' => 'Yardımlı',
            'zaqatala' => 'Zaqatala',
            'zengilan' => 'Zəngilan',
            'zerdab' => 'Zərdab',
        ];

        $out = [];
        foreach ($names as $slug => $name) {
            $out[] = [
                'slug' => $slug,
                'name' => $name,
                'type' => 'rayon',
                'districts' => $self($slug, $name),
            ];
        }

        return $out;
    }
}
