<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Playground;
use Illuminate\Database\Seeder;

class FacilitiesSeeder extends Seeder
{
    /**
     * Demo areas/playgrounds for local development - illustrative names, not
     * real registered facilities. Gives a freshly seeded environment enough
     * data to exercise booking, availability and the admin facilities screens
     * without having to create everything by hand first.
     */
    public function run(): void
    {
        $standardHours = [
            'mon' => ['is_closed' => false, 'opens_at' => '08:00', 'closes_at' => '21:00'],
            'tue' => ['is_closed' => false, 'opens_at' => '08:00', 'closes_at' => '21:00'],
            'wed' => ['is_closed' => false, 'opens_at' => '08:00', 'closes_at' => '21:00'],
            'thu' => ['is_closed' => false, 'opens_at' => '08:00', 'closes_at' => '21:00'],
            'fri' => ['is_closed' => false, 'opens_at' => '08:00', 'closes_at' => '21:00'],
            'sat' => ['is_closed' => false, 'opens_at' => '09:00', 'closes_at' => '20:00'],
            'sun' => ['is_closed' => false, 'opens_at' => '09:00', 'closes_at' => '20:00'],
        ];

        $areaSever = Area::query()->updateOrCreate(
            ['name' => 'Športový areál Karlova Ves - Sever'],
            [
                'description' => 'Demo areál pre lokálny vývoj s tenisovým kurtom a multifunkčným ihriskom.',
                'address' => 'Molecova, 841 08 Bratislava-Karlova Ves',
            ]
        );

        $areaJuh = Area::query()->updateOrCreate(
            ['name' => 'Športový areál Karlova Ves - Juh'],
            [
                'description' => 'Demo areál pre lokálny vývoj s futbalovým a basketbalovým ihriskom.',
                'address' => 'Líščie údolie, 841 04 Bratislava-Karlova Ves',
            ]
        );

        Playground::query()->updateOrCreate(
            ['area_id' => $areaSever->id, 'name' => 'Tenisový kurt 1'],
            [
                'description' => 'Antukový tenisový kurt.',
                'price_per_30min' => 6.00,
                'max_horizon_days' => 60,
                'max_duration_minutes' => 120,
                'is_active' => true,
                'latitude' => 48.1751,
                'longitude' => 17.0645,
                'opening_hours' => $standardHours,
                'allow_card_payment' => true,
            ]
        );

        Playground::query()->updateOrCreate(
            ['area_id' => $areaSever->id, 'name' => 'Multifunkčné ihrisko'],
            [
                'description' => 'Umelý povrch, vhodné na basketbal a malý futbal.',
                'price_per_30min' => 8.00,
                'max_horizon_days' => 60,
                'max_duration_minutes' => 90,
                'is_active' => true,
                'latitude' => 48.1755,
                'longitude' => 17.0651,
                'opening_hours' => $standardHours,
                'allow_card_payment' => false,
            ]
        );

        Playground::query()->updateOrCreate(
            ['area_id' => $areaJuh->id, 'name' => 'Futbalové ihrisko'],
            [
                'description' => 'Prírodná tráva, plnohodnotné rozmery.',
                'price_per_30min' => 15.00,
                'max_horizon_days' => 60,
                'max_duration_minutes' => 120,
                'is_active' => true,
                'latitude' => 48.1668,
                'longitude' => 17.0598,
                'opening_hours' => $standardHours,
                'allow_card_payment' => true,
            ]
        );

        Playground::query()->updateOrCreate(
            ['area_id' => $areaJuh->id, 'name' => 'Basketbalové ihrisko'],
            [
                'description' => 'Vonkajšie ihrisko s dvoma košmi.',
                'price_per_30min' => 4.50,
                'max_horizon_days' => 60,
                'max_duration_minutes' => 90,
                'is_active' => true,
                'latitude' => 48.1662,
                'longitude' => 17.0604,
                'opening_hours' => $standardHours,
                'allow_card_payment' => false,
            ]
        );
    }
}
