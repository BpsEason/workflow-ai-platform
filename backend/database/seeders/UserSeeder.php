<?php

namespace Database\Seeders;

use Illuminate->Database\Console\Seeds\WithoutModelEvents;
use Illuminate->Database\Seeder;
use App\Models\User;
use Illuminate->Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'), // 預設密碼
                'email_verified_at' => now(),
            ]
        );

        User::factory()->count(5)->create(); // 創建更多假用戶

        $this->command->info('Users seeded!');
    }
}
