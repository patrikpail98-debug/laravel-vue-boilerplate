<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Setting::updateOrCreate(
            ['key' => 'auth.registrations.enabled'],
            ['value' => '1']
        );

        // Media Settings
        Setting::updateOrCreate(
            ['key' => 'media.upload.max_size_kb'],
            ['value' => '5120']
        );

        // Organization settings (used for reservation payment slips/emails)
        Setting::updateOrCreate(
            ['key' => Setting::ORG_NAME_KEY],
            ['value' => 'Mestská časť Bratislava-Karlova Ves']
        );
        Setting::updateOrCreate(
            ['key' => Setting::ORG_IBAN_KEY],
            ['value' => '']
        );
        Setting::updateOrCreate(
            ['key' => Setting::ORG_BANK_NAME_KEY],
            ['value' => '']
        );

        // Contact settings (public footer / contact page)
        Setting::updateOrCreate(
            ['key' => Setting::CONTACT_ADDRESS_KEY],
            ['value' => 'Nám. sv. Františka 8, 842 17 Bratislava-Karlova Ves']
        );
        Setting::updateOrCreate(
            ['key' => Setting::CONTACT_PHONE_KEY],
            ['value' => '']
        );
        Setting::updateOrCreate(
            ['key' => Setting::CONTACT_EMAIL_KEY],
            ['value' => '']
        );
        Setting::updateOrCreate(
            ['key' => Setting::CONTACT_PERSON_KEY],
            ['value' => '']
        );
        Setting::updateOrCreate(
            ['key' => Setting::CONTACT_HOURS_KEY],
            ['value' => '']
        );
        Setting::updateOrCreate(
            ['key' => Setting::CONTACT_LATITUDE_KEY],
            ['value' => '']
        );
        Setting::updateOrCreate(
            ['key' => Setting::CONTACT_LONGITUDE_KEY],
            ['value' => '']
        );
    }
}
