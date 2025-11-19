<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Lunar\Models\Language;

class LunarLanguageSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed essential languages for Lunar.
     * Based on Lunar's InstallLunar command logic.
     */
    public function run(): void
    {
        if (! Language::count()) {
            $this->command->info('Adding default language');

            Language::create([
                'code' => 'en',
                'name' => 'English',
                'default' => true,
            ]);
        } else {
            $this->command->info('Languages already exist, skipping...');
        }
    }
}
