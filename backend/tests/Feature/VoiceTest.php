<?php

namespace Tests\Feature;

use Illuminate->Foundation->Testing->RefreshDatabase;
use Illuminate->Foundation->Testing->WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Voice;
use Illuminate->Http\UploadedFile;
use Illuminate->Support->Facades\Storage;
use Illuminate->Support\Facades\Http;

class VoiceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // 創建一個測試用戶
        $this->user = User::factory()->create(['id' => 1]); // 確保 ID 與 Voice 測試中的 userId 一致

        // 模擬 AI Orchestrator 的回應
        Http::fake([
            // 模擬語音轉錄
            env('AI_ORCHESTRATOR_URL') . '/voice/transcribe' => Http::response([
                'transcribed_text' => '你好，AI助理。'
            ], 200),
            // 模擬語音回應生成
            env('AI_ORCHESTRATOR_URL') . '/voice/respond' => Http::response([
                'user_id' => (string)$this->user->id,
                'response_text' => '您好，有什麼我可以幫您的嗎？'
            ], 200),
        ]);
    }

    /**
     * 測試語音處理 API 是否成功。
     *
     * @return void
     */
    public function test_authenticated_user_can_process_voice()
    {
        Storage::fake('local'); // 模擬文件存儲

        $audioFile = UploadedFile::fake()->create('test_audio.mp3', 500, 'audio/mp3'); // 500KB 音檔

        $response = $this->actingAs($this->user, 'sanctum') // 假設使用 Sanctum 認證
                         ->postJson('/api/voice/process', [
                             'audio' => $audioFile,
                             'user_id' => (string)$this->user->id, // 注意這裡傳遞用戶 ID
                         ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'message',
                     'transcribed_text',
                     'response_text',
                 ])
                 ->assertJsonFragment([
                     'message' => '語音處理成功',
                     'transcribed_text' => '你好，AI助理。',
                     'response_text' => '您好，有什麼我可以幫您的嗎？'
                 ]);

        Storage::disk('local')->assertExists('voices/' . $audioFile->hashName());
        $this->assertDatabaseHas('voices', [
            'user_id' => $this->user->id,
            'speaker' => 'user',
            'text' => '你好，AI助理。',
            'audio_path' => 'voices/' . $audioFile->hashName(),
        ]);
        $this->assertDatabaseHas('voices', [
            'user_id' => $this->user->id,
            'speaker' => 'assistant',
            'text' => '您好，有什麼我可以幫您的嗎？',
            'audio_path' => null,
        ]);
    }

    /**
     * 測試未認證用戶無法處理語音。
     *
     * @return void
     */
    public function test_unauthenticated_user_cannot_process_voice()
    {
        Storage::fake('local');
        $audioFile = UploadedFile::fake()->create('test_audio_unauth.mp3', 500, 'audio/mp3');

        $response = $this->postJson('/api/voice/process', [
            'audio' => $audioFile,
            'user_id' => 'some_user_id',
        ]);

        $response->assertStatus(401);
        Storage::disk('local')->assertMissing('voices/' . $audioFile->hashName());
    }

    /**
     * 測試獲取語音對話歷史 API 是否成功。
     *
     * @return void
     */
    public function test_authenticated_user_can_get_voice_history()
    {
        Voice::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'speaker' => $this->faker->randomElement(['user', 'assistant']),
            'text' => $this->faker->sentence,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
                         ->getJson('/api/voice/history/' . $this->user->id);

        $response->assertStatus(200)
                 ->assertJsonCount(3); // 斷言返回了 3 條歷史記錄
        
        // 檢查返回的數據結構
        $response->assertJsonStructure([
            '*' => [
                'speaker',
                'text',
                'created_at',
            ]
        ]);
    }

    /**
     * 測試未認證用戶無法獲取語音歷史。
     *
     * @return void
     */
    public function test_unauthenticated_user_cannot_get_voice_history()
    {
        $response = $this->getJson('/api/voice/history/' . $this->user->id);
        $response->assertStatus(401);
    }
}
