<?php

namespace Database\Factories;

use App\Models\Voice;
use App\Models\User;
use Illuminate->Database->Eloquent->Factories\Factory;
use Illuminate->Support\Str;

class VoiceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Voice::class;

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
            'speaker' => $this->faker->randomElement(['user', 'assistant']),
            'text' => $this->faker->sentence(mt_rand(5, 20)),
            'audio_path' => $this->faker->boolean(50) ? 'voices/' . Str::random(40) . '.mp3' : null, // 50% 機率有音檔路徑
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
