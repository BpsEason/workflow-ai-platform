<?php

namespace Tests\Feature;

use Illuminate->Foundation->Testing->RefreshDatabase;
use Illuminate->Foundation->Testing->WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate->Http\UploadedFile;
use Illuminate->Support->Facades\Storage;
use Illuminate->Support->Facades\Http;

class DocumentTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // 創建一個測試用戶
        $this->user = User::factory()->create();

        // 模擬 AI Orchestrator 的回應
        Http::fake([
            // 模擬文件上傳後的處理
            env('AI_ORCHESTRATOR_URL') . '/documents/upload' => Http::response([
                'document_id' => 1,
                'summary' => '這是一份測試文件的模擬摘要。',
                'status' => 'processed'
            ], 200),
            // 模擬文件搜尋
            env('AI_ORCHESTRATOR_URL') . '/documents/search*' => Http::response([
                'query' => '測試搜尋',
                'results' => [
                    ['score' => 0.95, 'document_id' => 1, 'text_chunk' => '這是文件的第一個相關片段。'],
                    ['score' => 0.88, 'document_id' => 2, 'text_chunk' => '這是文件的第二個相關片段。']
                ]
            ], 200),
        ]);
    }

    /**
     * 測試文件上傳 API 是否成功。
     *
     * @return void
     */
    public function test_authenticated_user_can_upload_document()
    {
        Storage::fake('local'); // 模擬文件存儲

        $file = UploadedFile::fake()->create('test_document.txt', 100, 'text/plain');

        $response = $this->actingAs($this->user, 'sanctum') // 假設使用 Sanctum 認證
                         ->postJson('/api/documents/upload', [
                             'file' => $file,
                             'category' => '測試分類',
                         ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'message',
                     'document' => ['id', 'name', 'file_path', 'summary', 'status', 'category'],
                     'ai_response'
                 ])
                 ->assertJsonFragment([
                     'message' => '文件已上傳並發送至AI處理',
                     'name' => 'test_document.txt',
                     'status' => 'processed_ai',
                     'summary' => '這是一份測試文件的模擬摘要。'
                 ]);

        Storage::disk('local')->assertExists('documents/' . $file->hashName());
        $this->assertDatabaseHas('documents', [
            'name' => 'test_document.txt',
            'user_id' => $this->user->id,
            'status' => 'processed_ai',
            'summary' => '這是一份測試文件的模擬摘要。'
        ]);
    }

    /**
     * 測試未認證用戶無法上傳文件。
     *
     * @return void
     */
    public function test_unauthenticated_user_cannot_upload_document()
    {
        Storage::fake('local');
        $file = UploadedFile::fake()->create('test_document_unauth.txt', 100, 'text/plain');

        $response = $this->postJson('/api/documents/upload', [
            'file' => $file,
            'category' => '測試分類',
        ]);

        $response->assertStatus(401); // 未認證應返回 401
        Storage::disk('local')->assertMissing('documents/' . $file->hashName());
    }

    /**
     * 測試文件上傳無效文件類型時的錯誤。
     *
     * @return void
     */
    public function test_document_upload_fails_with_invalid_file_type()
    {
        Storage::fake('local');
        $file = UploadedFile::fake()->image('photo.jpg'); // 不允許的圖片類型

        $response = $this->actingAs($this->user, 'sanctum')
                         ->postJson('/api/documents/upload', [
                             'file' => $file,
                         ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['file']);
    }

    /**
     * 測試文件語意搜尋 API 是否成功。
     *
     * @return void
     */
    public function test_authenticated_user_can_search_documents()
    {
        $query = '蘋果公司的歷史';

        $response = $this->actingAs($this->user, 'sanctum')
                         ->getJson('/api/documents/search?q=' . urlencode($query));

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'query',
                     'results' => [
                         '*' => ['score', 'document_id', 'text_chunk']
                     ]
                 ])
                 ->assertJsonFragment([
                     'query' => '測試搜尋', // 這是模擬的查詢結果
                     'text_chunk' => '這是文件的第一個相關片段。'
                 ]);
    }

    /**
     * 測試未認證用戶無法搜尋文件。
     *
     * @return void
     */
    public function test_unauthenticated_user_cannot_search_documents()
    {
        $response = $this->getJson('/api/documents/search?q=anyquery');
        $response->assertStatus(401);
    }

    /**
     * 測試文件語意搜尋無效查詢時的錯誤。
     *
     * @return void
     */
    public function test_document_search_fails_with_invalid_query()
    {
        $response = $this->actingAs($this->user, 'sanctum')
                         ->getJson('/api/documents/search?q=a'); // 查詢太短

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['q']);
    }
}
