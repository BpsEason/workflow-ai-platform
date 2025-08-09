<?php

namespace Database\Seeders;

use Illuminate->Database\Console\Seeds\WithoutModelEvents;
use Illuminate->Database\Seeder;
use App\Models\Document;
use App\Models\User;

class DocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 確保有足夠的用戶可以關聯文檔
        if (User::count() == 0) {
            User::factory()->count(3)->create();
        }
        
        // 創建 20 個假文件數據，並隨機關聯用戶
        Document::factory()->count(20)->create();

        $this->command->info('Documents seeded!');
    }
}
