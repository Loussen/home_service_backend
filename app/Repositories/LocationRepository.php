<?php

namespace App\Repositories;

use App\Models\City;
use Illuminate\Database\Eloquent\Collection;

class LocationRepository
{
    public function citiesWithDistricts(): Collection
    {
        return City::query()
            ->where('is_active', true)
            ->with('districts')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array{city_id: int|null, district_id: int|null, city: string|null, district: string|null}
     */
    public function resolveNames(?string $cityName, ?string $districtName): array
    {
        $fold = function (?string $s): string {
            if (! $s) {
                return '';
            }
            $s = mb_strtolower(trim($s));
            $from = ['ə', 'ı', 'ö', 'ü', 'ç', 'ş', 'ğ', 'baku', 'ganja', 'баку', 'нариманов', 'ясамал', 'насими'];
            $to = ['e', 'i', 'o', 'u', 'c', 's', 'g', 'baki', 'gence', 'baki', 'nerimanov', 'yasamal', 'nesimi'];

            return str_replace($from, $to, $s);
        };

        $cities = $this->citiesWithDistricts();
        $wantCity = $fold($cityName);
        $wantDistrict = $fold($districtName);

        $city = null;
        $district = null;

        if ($wantDistrict !== '') {
            foreach ($cities as $c) {
                foreach ($c->districts as $d) {
                    $dn = $fold($d->name);
                    if ($dn === $wantDistrict || str_contains($wantDistrict, $dn) || str_contains($dn, $wantDistrict)) {
                        $city = $c;
                        $district = $d;
                        break 2;
                    }
                }
            }
        }

        if (! $city && $wantCity !== '') {
            foreach ($cities as $c) {
                $cn = $fold($c->name);
                if ($cn === $wantCity || str_contains($wantCity, $cn) || str_contains($cn, $wantCity)) {
                    $city = $c;
                    break;
                }
            }
        }

        if ($city && ! $district && $wantDistrict !== '') {
            foreach ($city->districts as $d) {
                $dn = $fold($d->name);
                if ($dn === $wantDistrict || str_contains($wantDistrict, $dn) || str_contains($dn, $wantDistrict)) {
                    $district = $d;
                    break;
                }
            }
        }

        return [
            'city_id' => $city?->id,
            'district_id' => $district?->id,
            'city' => $city?->name,
            'district' => $district?->name,
        ];
    }
}
