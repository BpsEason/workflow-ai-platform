<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\User;
use Illuminate->Database->Eloquent->Factories\Factory;
use Illuminate->Support\Str;

class DocumentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Document::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        // 確保至少有一個 User 存在，如果沒有則創建
        $user = User::first() ?? User::factory()->create();

        return [
            'user_id' => $user->id,
            'name' => $this->faker->word() . '.txt',
            'file_path' => 'documents/' . Str::random(40) . '.txt',
            'summary' => $this->faker->text(200),
            'status' => $this->faker->randomElement(['uploaded', 'processed_ai', 'ai_failed']),
            'category' => $this->faker->randomElement(['Contract', 'Report', 'FAQ', 'Policy']),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
