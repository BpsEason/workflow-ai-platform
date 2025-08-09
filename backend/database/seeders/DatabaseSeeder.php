<?php

namespace Database\Seeders;

use Illuminate->Database\Console\Seeds\WithoutModelEvents;
use Illuminate->Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            \Database\Seeders\UserSeeder::class, // 確保 User Seeder 在前，為其他 Seeder 提供用戶
            \Database\Seeders\DocumentSeeder::class,
            \Database\Seeders\VoiceSeeder::class,
        ]);
    }
}
