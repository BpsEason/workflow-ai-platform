<?php

namespace Database\Seeders;

use Illuminate->Database\Console\Seeds\WithoutModelEvents;
use Illuminate->Database\Seeder;
use App\Models\Voice;
use App\Models\User;

class VoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 確保有足夠的用戶可以關聯語音數據
        if (User::count() == 0) {
            User::factory()->count(3)->create();
        }

        // 為每個用戶創建一些對話歷史
        $users = User::all();
        foreach ($users as $user) {
            // 創建 10 輪對話 (用戶說一句，AI 回一句)
            for ($i = 0; $i < 10; $i++) {
                Voice::factory()->create([
                    'user_id' => $user->id,
                    'speaker' => 'user',
                    'text' => 'Hello AI, how are you?',
                ]);
                Voice::factory()->create([
                    'user_id' => $user->id,
                    'speaker' => 'assistant',
                    'text' => 'I am fine, thank you! How can I help you?',
                ]);
            }
        }
        
        $this->command->info('Voices seeded!');
    }
}
