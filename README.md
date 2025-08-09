Workflow AI Platform歡迎來到 Workflow AI Platform！這是一個現代化的全端應用程式，旨在透過人工智慧技術簡化您的文件管理和語音互動工作流程。本專案整合了 robust 的 Laravel 後端、響應迅速的 Vue3 前端和強大的 FastAPI AI 微服務，並透過 Docker Compose 實現便捷的容器化部署。🚀 專案亮點 (Project Highlights)全端整合: 無縫協作的 Laravel (PHP) 後端、Vue3 (JavaScript) 前端和 FastAPI (Python) AI 微服務。智慧文件管理:文件上傳與處理: 支援 PDF, DOCX, TXT 文件上傳。AI 摘要: 利用 OpenAI 的大型語言模型 (LLM) 自動為文件生成精簡摘要。語意搜尋: 基於文件內容的向量化 (OpenAI Embedding) 實現高精度的語意搜尋，快速找到相關資訊。AI 語音助理:高效語音轉錄: 整合 Faster-Whisper 模型，將語音輸入快速轉換為文字。RAG (檢索增強生成): 將轉錄文字與檢索到的文件內容結合，透過 OpenAI LLM 生成連貫、上下文感知的智慧回應。對話歷史: 完整記錄用戶與 AI 助理的語音互動歷史。安全認證: 採用 Laravel Sanctum 實現安全的 API 認證和跨域 (CORS) 兼容。資料持久化: 使用 MySQL 儲存應用數據，Qdrant 作為高效能向量資料庫。自動化 API 文檔: 透過 Laravel Scribe 自動生成清晰、互動性強的 API 文檔。全面測試: 包含前端 (Vitest 單元測試、Cypress E2E 測試) 和後端 (PHPUnit) 測試，確保應用程式的穩定性和可靠性。便捷部署: 基於 Docker Compose 的容器化方案，實現快速搭建和部署開發環境。🌳 專案結構 (Project Structure)本專案採用微服務架構，清晰劃分職責：workflow-ai-platform/
├── .env.example              # 環境變數範本
├── Caddyfile                 # Caddy 反向代理配置
├── README.md                 # 專案說明文件 (您正在閱讀的檔案)
├── docker-compose.yml        # Docker Compose 服務定義
├── backend/                  # Laravel 後端應用程式
│   ├── app/
│   │   ├── Http/Controllers/   # API 控制器 (AuthController, DocumentController, VoiceController)
│   │   └── Models/             # Eloquent 模型 (User, Document, Voice)
│   ├── config/scribe.php       # Scribe API 文檔配置
│   ├── database/
│   │   ├── migrations/         # 資料庫遷移檔案
│   │   └── seeders/            # 資料庫種子檔案 (DocumentSeeder, VoiceSeeder, UserSeeder)
│   ├── nginx/default.conf      # Nginx 服務配置
│   ├── etc/supervisor/         # Supervisor 進程管理配置
│   ├── routes/api.php          # API 路由定義
│   └── tests/Feature/          # 後端 API 測試
├── frontend/                 # Vue3 前端應用程式
│   ├── public/
│   ├── src/
│   │   ├── assets/             # 靜態資源
│   │   ├── components/         # 可重用組件
│   │   ├── router/index.js     # Vue Router 配置
│   │   ├── stores/             # Pinia 狀態管理
│   │   ├── views/              # 頁面級組件
│   │   └── App.vue, main.js    # Vue 應用入口
│   ├── cypress/                # Cypress E2E 測試
│   │   ├── e2e/                # E2E 測試腳本
│   │   └── fixtures/           # 測試數據
│   ├── package.json            # 前端依賴和腳本
│   └── vite.config.js          # Vite 配置
├── ai-orchestrator/          # FastAPI AI 微服務
│   ├── app/
│   │   ├── services/           # AI 核心服務 (document_service, rag_pipeline)
│   │   ├── models/             # 數據模型
│   │   └── main.py             # FastAPI 應用入口
│   ├── data/                   # Faster-Whisper 模型下載目錄
│   ├── requirements.txt        # Python 依賴
│   └── tests/                  # AI 微服務測試
└── db_data/                  # MySQL 資料持久化目錄
└── qdrant_data/              # Qdrant 資料持久化目錄
💻 關鍵程式碼解析 (Key Code Analysis with Comments)本節將深入探討專案中的關鍵程式碼片段，展示其核心邏輯和技術實現。A. 後端服務 (Laravel)Laravel 後端負責用戶認證、API 路由管理、文件儲存以及與 AI 微服務的協調溝通。1. 用戶認證 - backend/app/Http/Controllers/AuthController.php此控制器處理用戶的註冊和登入邏輯，並利用 Laravel Sanctum 生成 API Token。<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * @group Authentication
 *
 * 管理用戶註冊、登入和登出。
 */
class AuthController extends Controller
{
    /**
     * 註冊新用戶。
     *
     * 註冊一個新用戶並返回 Sanctum API Token。
     * @bodyParam name string required 用戶名。Example: John Doe
     * @bodyParam email string required 用戶的 Email 地址，必須是唯一的。Example: john@example.com
     * @bodyParam password string required 用戶密碼，至少8個字符，且需要與 password_confirmation 匹配。Example: password123
     * @bodyParam password_confirmation string required 確認用戶密碼。Example: password123
     */
    public function register(Request $request)
    {
        try {
            // 驗證輸入數據
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users', // Email 必須唯一
                'password' => 'required|string|min:8|confirmed', // 密碼至少8位且需要確認
            ]);
        } catch (ValidationException $e) {
            Log::error('註冊驗證失敗: ' . json_encode($e->errors()));
            return response()->json(['message' => '註冊驗證失敗', 'errors' => $e->errors()], 422);
        }

        // 創建新用戶
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password), // 密碼加密儲存
        ]);

        // 為新用戶創建 Sanctum API Token
        $token = $user->createToken('auth_token')->plainTextToken;

        Log::info("用戶 {$user->email} 註冊成功。");
        return response()->json([
            'message' => 'User registered successfully',
            'token' => $token,
            'user' => $user
        ], 201); // 返回 201 Created 狀態碼
    }

    /**
     * 用戶登入。
     *
     * 驗證用戶憑證並返回 Sanctum API Token。
     * @bodyParam email string required 用戶的 Email 地址。Example: john@example.com
     * @bodyParam password string required 用戶密碼。Example: password123
     */
    public function login(Request $request)
    {
        try {
            // 驗證輸入數據
            $request->validate([
                'email' => 'required|string|email',
                'password' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            Log::error('登入驗證失敗: ' . json_encode($e->errors()));
            return response()->json(['message' => '登入驗證失敗', 'errors' => $e->errors()], 422);
        }

        // 嘗試使用 Email 和密碼進行認證
        if (!Auth::attempt($request->only('email', 'password'))) {
            Log::warning("登入嘗試失敗：無效的憑證，Email: {$request->email}");
            return response()->json(['message' => 'Invalid credentials'], 401); // 認證失敗返回 401 Unauthorized
        }

        // 獲取認證後的用戶實例
        $user = $request->user();
        // 為用戶創建新的 Sanctum API Token
        $token = $user->createToken('auth_token')->plainTextToken;

        Log::info("用戶 {$user->email} 登入成功。");
        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => $user
        ]);
    }

    // ... (logout 和 user 方法也在此控制器中，邏輯類似)
}
2. 文件處理 - backend/app/Http/Controllers/DocumentController.php此控制器負責接收文件上傳，將其儲存，然後觸發 AI 微服務進行深度處理（向量化和摘要）。<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support->Facades\Storage;
use Illuminate->Support->Facades\Http; // 用於發送 HTTP 請求到 AI Orchestrator
use App\Models\Document;
use Illuminate\Validation\ValidationException;
use Illuminate->Support->Facades\Log;

/**
 * @group Document Management
 *
 * 管理文件的上傳、搜尋和處理。
 */
class DocumentController extends Controller
{
    /**
     * 上傳文件。
     *
     * 允許用戶上傳文件 (PDF, DOCX, TXT)，文件將被儲存，並觸發 AI 微服務進行向量化和摘要。
     * @authenticated
     * @bodyParam file file required The file to upload (max 10MB, allowed types: pdf, doc, docx, txt).
     * @bodyParam category string The category of the document. Example: "Policy"
     */
    public function upload(Request $request)
    {
        try {
            // 驗證文件和分類輸入
            $request->validate([
                'file' => 'required|file|mimes:pdf,doc,docx,txt|max:10240', // 限制文件類型和大小 (10MB)
                'category' => 'nullable|string|max:255'
            ]);
        } catch (ValidationException $e) {
            Log::error('文件上傳驗證失敗: ' . json_encode($e->errors()));
            return response()->json(['message' => '文件驗證失敗', 'errors' => $e->errors()], 422);
        }

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        // 將文件儲存到 Laravel 的 storage/app/documents 目錄
        $path = $file->store('documents'); 

        // 創建文件記錄到資料庫，狀態設為 'pending_ai'
        $document = Document::create([
            'user_id' => $request->user()->id ?? null, // 關聯上傳用戶
            'name' => $originalName,
            'file_path' => $path,
            'summary' => null, // 摘要等待 AI 服務填充
            'status' => 'pending_ai', 
            'category' => $request->input('category')
        ]);

        Log::info("文件 {$document->id} 已上傳，準備發送至 AI Orchestrator。");

        try {
            // 向 AI Orchestrator 微服務發送 HTTP POST 請求，觸發文件處理
            // 傳遞文件在後端的絕對路徑和相關元數據
            $aiResponse = Http::timeout(120)->post(env('AI_ORCHESTRATOR_URL') . '/documents/upload', [
                'document_id' => $document->id,
                'file_path' => storage_path('app/' . $path), // 提供文件的絕對路徑
                'metadata' => [
                    'original_name' => $originalName,
                    'category' => $document->category,
                    'uploaded_by' => $document->user_id,
                ]
            ]);

            if ($aiResponse->successful()) {
                $ai_result = $aiResponse->json();
                // 如果 AI 處理成功，更新文件記錄的摘要和狀態
                $document->update([
                    'summary' => $ai_result['summary'] ?? null,
                    'status' => $ai_result['status'] ?? 'processed_ai',
                ]);
                Log::info("文件 {$document->id} AI 處理成功。");
            } else {
                // AI 處理失敗，更新狀態並返回錯誤
                $document->update(['status' => 'ai_failed']);
                Log::error("AI Orchestrator 文件處理失敗，文件 ID: {$document->id}。錯誤: " . $aiResponse->body());
                return response()->json(['message' => '文件上傳成功，但AI處理失敗', 'error' => $aiResponse->body()], $aiResponse->status());
            }
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            // 無法連接到 AI Orchestrator
            $document->update(['status' => 'ai_connection_error']);
            Log::error("連接 AI Orchestrator 失敗，文件 ID: {$document->id}。錯誤: " . $e->getMessage());
            return response()->json(['message' => '文件上傳成功，但無法連接AI服務', 'error' => $e->getMessage()], 500);
        } catch (\Exception $e) {
            // 其他未知錯誤
            $document->update(['status' => 'ai_process_error']);
            Log::error("AI Orchestrator 處理文件時發生未知錯誤，文件 ID: {$document->id}。錯誤: " . $e->getMessage());
            return response()->json(['message' => '文件上傳成功，但AI處理發生未知錯誤', 'error' => $e->getMessage()], 500);
        }

        return response()->json([
            'message' => '文件已上傳並發送至AI處理',
            'document' => $document,
            'ai_response' => $ai_result ?? null
        ], 201);
    }
    
    // ... (search 方法也在此控制器中，邏輯類似)
}
3. 語音助理 - backend/app/Http/Controllers/VoiceController.php此控制器處理語音輸入，將其發送給 AI 微服務進行轉錄和回應生成，並管理對話歷史。<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support->Facades->Storage;
use Illuminate->Support->Facades->Http;
use App\Models\Voice;
use Illuminate->Validation\ValidationException;
use Illuminate->Support->Facades\Log;

/**
 * @group Voice Assistant
 *
 * 提供語音輸入處理和對話歷史記錄功能。
 */
class VoiceController extends Controller
{
    /**
     * 處理語音輸入。
     *
     * 接收語音文件，將其發送至 AI 微服務進行轉錄，然後使用轉錄文本獲取 AI 回應，並儲存對話歷史。
     * @authenticated
     * @bodyParam audio file required The audio file to process (max 10MB, allowed types: mp3, wav, ogg, webm).
     * @bodyParam user_id string required The ID of the user. This should ideally come from an authenticated user. Example: "user_uuid_123"
     */
    public function process(Request $request)
    {
        try {
            // 驗證音頻文件和用戶 ID
            $request->validate([
                'audio' => 'required|file|mimes:mp3,wav,ogg,webm|max:10240', // 支援常見音頻格式，最大10MB
                'user_id' => 'required|string', 
            ]);
        } catch (ValidationException $e) {
            Log::error('語音文件驗證失敗: ' . json_encode($e->errors()));
            return response()->json(['message' => '語音文件驗證失敗', 'errors' => $e->errors()], 422);
        }

        $audioFile = $request->file('audio');
        // 儲存用戶上傳的音頻文件
        $filePath = $audioFile->store('voices'); 
        $userId = $request->input('user_id');

        Log::info("收到語音處理請求，用戶ID: {$userId}，文件路徑: {$filePath}");

        try {
            // 1. 轉錄語音：將音頻文件發送給 AI Orchestrator 進行轉錄
            Log::info("發送語音檔至 AI Orchestrator 進行轉錄...");
            $transcribeResponse = Http::timeout(60)->attach(
                'audio_file', file_get_contents(storage_path('app/' . $filePath)), $audioFile->getClientOriginalName()
            )->post(env('AI_ORCHESTRATOR_URL') . '/voice/transcribe');

            if (!$transcribeResponse->successful()) {
                Log::error("AI Orchestrator 語音轉錄失敗。錯誤: " . $transcribeResponse->body());
                return response()->json(['message' => '語音轉錄失敗', 'error' => $transcribeResponse->body()], $transcribeResponse->status());
            }
            $transcribedText = $transcribeResponse->json('transcribed_text');
            Log::info("語音轉錄成功: '{$transcribedText}'");

            // 2. 獲取對話歷史：從資料庫中檢索當前用戶的對話歷史，用於 RAG 的上下文
            $history = Voice::where('user_id', $userId)
                             ->orderBy('created_at', 'asc')
                             ->select('speaker', 'text')
                             ->get()
                             ->map(function($item) {
                                 // 格式化歷史訊息為 AI Orchestrator 預期的格式
                                 return ['role' => ($item->speaker == 'user' ? 'user' : 'assistant'), 'content' => $item->text];
                             })
                             ->toArray();
            Log::info("獲取到用戶 {$userId} 的歷史對話數: " . count($history));

            // 3. 生成回應：將轉錄文本和歷史對話發送給 AI Orchestrator 獲取回應
            Log::info("發送轉錄文本至 AI Orchestrator 獲取回應...");
            $aiResponse = Http::timeout(120)->post(env('AI_ORCHESTRATOR_URL') . '/voice/respond', [
                'user_id' => $userId,
                'prompt' => $transcribedText,
                'conversation_history' => $history // 傳遞對話歷史
            ]);

            if (!$aiResponse->successful()) {
                Log::error("AI Orchestrator 語音回應失敗。錯誤: " . $aiResponse->body());
                return response()->json(['message' => 'AI 回應生成失敗', 'error' => $aiResponse->body()], $aiResponse->status());
            }
            $responseText = $aiResponse->json('response_text');
            Log::info("AI 回應生成成功: '{$responseText}'");

            // 儲存用戶的語音輸入和 AI 的文字回應到資料庫，作為對話歷史的一部分
            Voice::create([
                'user_id' => $userId,
                'speaker' => 'user', // 用戶發言
                'text' => $transcribedText,
                'audio_path' => $filePath, // 記錄原始語音文件路徑
            ]);
            Voice::create([
                'user_id' => $userId,
                'speaker' => 'assistant', // 助理回應
                'text' => $responseText,
                'audio_path' => null, // AI 回應通常沒有語音檔
            ]);
            Log::info("對話歷史已儲存。");

            return response()->json([
                'message' => '語音處理成功',
                'transcribed_text' => $transcribedText,
                'response_text' => $responseText,
            ]);

        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            // 連接錯誤處理
            Log::error("連接 AI Orchestrator 失敗 (語音處理)。錯誤: " . $e->getMessage());
            return response()->json(['message' => '無法連接AI服務', 'error' => $e->getMessage()], 500);
        } catch (\Exception $e) {
            // 其他未知錯誤處理
            Log::error("處理語音時發生未知錯誤: " . $e->getMessage());
            return response()->json(['message' => '語音處理服務異常', 'error' => $e->getMessage()], 500);
        }
    }

    // ... (history 方法也在此控制器中，邏輯類似)
}
4. 資料庫遷移 - backend/database/migrations/遷移文件定義了資料庫表的結構。*_create_documents_table.php<?php

use Illuminate->Database->Migrations->Migration;
use Illuminate->Database->Schema->Blueprint;
use Illuminate->Support->Facades->Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id(); // 主鍵 ID
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // 用戶 ID 外鍵，允許為空 (如果文件不與特定用戶關聯)
            $table->string('name'); // 文件原始名稱
            $table->string('file_path'); // 文件在伺服器儲存的路徑
            $table->text('summary')->nullable(); // AI 生成的文件摘要，允許為空
            $table->string('status')->default('uploaded'); // 文件處理狀態 (uploaded, pending_ai, processed_ai, ai_failed, etc.)
            $table->string('category')->nullable(); // 文件分類，例如 "Contract", "Report"
            $table->timestamps(); // created_at 和 updated_at 時間戳
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
*_create_voices_table.php<?php

use Illuminate->Database->Migrations->Migration;
use Illuminate->Database->Schema->Blueprint;
use Illuminate->Support->Facades->Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('voices', function (Blueprint $table) {
            $table->id(); // 主鍵 ID
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // 用戶 ID 外鍵，關聯用戶，用戶刪除時對話記錄也刪除
            $table->enum('speaker', ['user', 'assistant']); // 發言者角色：'user' 或 'assistant'
            $table->text('text'); // 轉錄後的文字 (用戶) 或 AI 回應的文字 (助理)
            $table->string('audio_path')->nullable(); // 原始語音文件路徑 (僅限用戶發言)，允許為空
            $table->timestamps(); // created_at 和 updated_at 時間戳
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voices');
    }
};
B. AI 微服務 (FastAPI)AI 微服務是專案的核心，負責所有的 AI 相關處理，包括 Embedding、摘要、RAG 和語音轉錄。1. OpenAI Embedding 與摘要 - ai-orchestrator/app/services/document_service.py此服務處理文件的 Embedding 生成和內容摘要，並與 Qdrant 向量資料庫交互。import os
import logging
from dotenv import load_dotenv
from typing import List, Dict, Any

from qdrant_client import QdrantClient, models
from langchain_community.embeddings import OpenAIEmbeddings # 從 LangChain 導入 OpenAI Embedding
from langchain_openai import ChatOpenAI # 從 LangChain 導入 OpenAI Chat 模型
from langchain.text_splitter import RecursiveCharacterTextSplitter # 用於文本分割

logger = logging.getLogger(__name__)

# 從環境變數載入配置
load_dotenv()
QDRANT_HOST = os.getenv("QDRANT_HOST", "qdrant")
QDRANT_PORT = int(os.getenv("QDRANT_PORT", 6333))
COLLECTION_NAME = "documents_collection"
EMBEDDING_DIM = 1536 # OpenAI text-embedding-ada-002 的向量維度

# 初始化 Qdrant 客戶端
client = QdrantClient(host=QDRANT_HOST, port=QDRANT_PORT)

# 初始化 OpenAI Embedding 和 ChatOpenAI 模型
embeddings_model = None
chat_llm = None
try:
    openai_api_key = os.getenv("OPENAI_API_KEY")
    if openai_api_key:
        embeddings_model = OpenAIEmbeddings(openai_api_key=openai_api_key) # 初始化 Embedding 模型
        chat_llm = ChatOpenAI(temperature=0.7, openai_api_key=openai_api_key) # 初始化 Chat 模型
        logger.info("OpenAI Embedding and ChatOpenAI models initialized.")
    else:
        logger.warning("OPENAI_API_KEY 未設定，OpenAI 服務將無法工作。")
except Exception as e:
    logger.error(f"初始化 OpenAI 模型失敗: {e}. 請檢查 OPENAI_API_KEY。", exc_info=True)


async def get_embedding(text: str) -> List[float]:
    """
    使用 OpenAIEmbeddings 獲取文本 Embedding (向量表示)。
    """
    if embeddings_model is None:
        raise RuntimeError("OpenAI Embedding 模型未載入，請檢查配置。")
    try:
        embedding = embeddings_model.embed_query(text) # 調用 OpenAI API 生成 Embedding
        return embedding
    except Exception as e:
        logger.error(f"獲取 Embedding 失敗: {e}", exc_info=True)
        raise RuntimeError(f"Embedding 服務錯誤: {e}")

async def process_document_embedding(document_id: int, file_path: str, metadata: Dict[str, Any]) -> str:
    """
    處理文件：讀取內容，分割為塊，向量化，並儲存到 Qdrant。
    同時生成文件摘要。
    """
    await initialize_qdrant_collection() # 確保 Qdrant Collection 存在

    content = ""
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read() # 讀取文件內容 (目前假設為純文本)

    # 使用 LangChain 的遞歸字符文本分割器，將長文本分割成較小的塊
    text_splitter = RecursiveCharacterTextSplitter(chunk_size=1000, chunk_overlap=200)
    chunks = text_splitter.split_text(content)
    
    points = []
    for i, chunk in enumerate(chunks):
        embedding = await get_embedding(chunk) # 為每個文本塊生成 Embedding
        points.append(
            models.PointStruct(
                id=f"{document_id}_{i}", # 創建唯一的點 ID
                vector=embedding,
                payload={"document_id": document_id, "chunk_index": i, "text": chunk, **metadata} # 儲存元數據和原始文本塊
            )
        )
    
    if points:
        client.upsert(
            collection_name=COLLECTION_NAME,
            points=points,
            wait=True 
        ) # 將所有點插入或更新到 Qdrant

    summary = await summarize_document(document_id, content) # 生成文件摘要
    return summary

async def summarize_document(document_id: int, text_content: str) -> str:
    """
    使用 ChatOpenAI 對文本內容進行摘要。
    """
    if chat_llm is None:
        raise RuntimeError("OpenAI Chat LLM 模型未載入，無法執行摘要。")
    try:
        # 截斷輸入文本以符合 LLM 的 token 限制
        max_input_length = 4000 
        truncated_content = text_content[:max_input_length] + ("..." if len(text_content) > max_input_length else "")

        prompt = f"請簡潔、清晰地總結以下文件內容：\n\n{truncated_content}"
        summary_response = await chat_llm.ainvoke(prompt) # 調用 ChatOpenAI 生成摘要
        summary = summary_response.content
        return summary
    except Exception as e:
        logger.error(f"文件 {document_id} 摘要失敗 (LLM 錯誤): {e}", exc_info=True)
        return "無法生成摘要。"
2. RAG 回應生成 - ai-orchestrator/app/services/rag_pipeline.py此服務實現了 RAG 流程，結合檢索到的文件內容和對話歷史來生成更準確的回應。import os
import logging
from dotenv import load_dotenv
from typing import List, Dict, Any

from langchain_openai import ChatOpenAI
from langchain.prompts import ChatPromptTemplate, MessagesPlaceholder
from langchain.chains import create_history_aware_retriever, create_retrieval_chain
from langchain.chains.combine_documents import create_stuff_documents_chain
from langchain_core.messages import HumanMessage, AIMessage
from langchain_community.vectorstores import Qdrant # LangChain 與 Qdrant 的整合
from langchain_community.embeddings import OpenAIEmbeddings # LangChain 與 OpenAI Embedding 整合

# 導入 document_service 以訪問其已初始化的 Qdrant 客戶端和 Embedding 模型
from . import document_service 

logger = logging.getLogger(__name__)

# 初始化 OpenAI Chat LLM 和 Embedding 模型 (確保 OPENAI_API_KEY 已設定)
chat_llm = None
embeddings_model_for_retriever = None
try:
    openai_api_key = os.getenv("OPENAI_API_KEY")
    if openai_api_key:
        chat_llm = ChatOpenAI(temperature=0.7, openai_api_key=openai_api_key)
        embeddings_model_for_retriever = OpenAIEmbeddings(openai_api_key=openai_api_key)
        logger.info("OpenAI Chat LLM and Embedding models for RAG initialized.")
    else:
        logger.warning("OPENAI_API_KEY 未設定，OpenAI 服務將無法工作。")
except Exception as e:
    logger.error(f"初始化 OpenAI 模型失敗: {e}. 請檢查 OPENAI_API_KEY。", exc_info=True)


async def get_qdrant_retriever():
    """獲取 Qdrant 向量儲存的 LangChain Retriever。"""
    if embeddings_model_for_retriever is None:
        raise RuntimeError("OpenAI Embedding 模型未載入，無法初始化 Retriever。")
    
    vector_store = Qdrant(
        client=document_service.client, # 重用 document_service 中已初始化的 Qdrant 客戶端
        collection_name=document_service.COLLECTION_NAME, # 使用 document_service 中定義的集合名稱
        embeddings=embeddings_model_for_retriever, # 使用 OpenAI Embedding 模型
    )
    return vector_store.as_retriever()


async def generate_response_from_rag(user_id: str, prompt: str, conversation_history: List[Dict[str, str]]) -> str:
    """
    基於 RAG (Retrieval-Augmented Generation) 流程生成回應。
    1. 根據用戶問題和歷史對話檢索相關文件塊。
    2. 將檢索到的內容與用戶問題結合，送入 LLM 生成回答。
    """
    logger.info(f"用戶 {user_id} 請求 RAG 回應，問題: '{prompt}'")
    
    if chat_llm is None:
        raise RuntimeError("OpenAI Chat LLM 模型未載入，無法生成 RAG 回應。")

    # 將對話歷史轉換為 LangChain 訊息格式 (HumanMessage/AIMessage)
    chat_history_messages = []
    for msg in conversation_history:
        if msg['role'] == 'user':
            chat_history_messages.append(HumanMessage(content=msg['content']))
        elif msg['role'] == 'assistant':
            chat_history_messages.append(AIMessage(content=msg['content']))

    # 步驟 1: 歷史感知檢索器
    # 創建一個檢索器，它能夠根據當前對話歷史和用戶輸入生成更精確的檢索查詢。
    retriever = await get_qdrant_retriever()
    history_aware_retriever = create_history_aware_retriever(
        chat_llm, 
        retriever, 
        ChatPromptTemplate.from_messages([
            MessagesPlaceholder("chat_history"), # 包含對話歷史
            ("user", "{input}"), # 當前用戶輸入
            ("user", "根據以上對話和我的問題，請總結出一個獨立的問題，以便從文件中檢索相關信息。") # 提示 LLM 生成新的檢索問題
        ])
    )

    # 步驟 2: 文件組合鏈
    # 將檢索到的文件內容作為上下文，結合用戶問題，送入 LLM 生成回答。
    document_chain = create_stuff_documents_chain(
        chat_llm, 
        ChatPromptTemplate.from_messages([
            ("system", "您是一個智慧助手，請根據提供的上下文和對話歷史，簡潔、專業地回答用戶的問題。\n\n上下文:\n{context}"), # 系統提示，提供上下文
            MessagesPlaceholder("chat_history"),
            ("user", "{input}"),
        ])
    )

    # 步驟 3: RAG 鏈
    # 結合歷史感知檢索器和文件組合鏈，形成完整的 RAG 流程。
    retrieval_chain = create_retrieval_chain(history_aware_retriever, document_chain)
    
    try:
        # 調用 RAG 鏈，傳入對話歷史和當前用戶輸入
        response = await retrieval_chain.ainvoke({
            "chat_history": chat_history_messages,
            "input": prompt
        })
        response_text = response["answer"] # 獲取 LLM 生成的回答
        logger.info(f"RAG 回應生成完成，回應: '{response_text[:50]}...'")
        return response_text
    except Exception as e:
        logger.error(f"RAG 回應生成失敗 (LLM 或檢索錯誤): {e}", exc_info=True)
        return "很抱歉，我無法生成基於內部資料的回應。請嘗試換個問題或稍後再試。"
3. 語音轉錄 - ai-orchestrator/app/main.py這是 FastAPI 應用程序的入口文件，包含語音轉錄 API。import io
import os
import logging
from fastapi import FastAPI, UploadFile, File, HTTPException
from pydantic import BaseModel
from typing import List, Dict, Any

from faster_whisper import WhisperModel # 導入 Faster-Whisper 模型

# 導入其他服務和模型
from .services.document_service import process_document_embedding, search_documents_qdrant, summarize_document
from .services.rag_pipeline import generate_response_from_rag
from .models.document_models import DocumentUploadRequest, DocumentSearchResponse, DocumentSummaryResponse
from .models.voice_models import VoiceTranscriptionResponse, VoiceResponseRequest, VoiceResponse

logger = logging.getLogger(__name__)

app = FastAPI(title="AI Orchestrator Microservice")

# 全局載入 Whisper 模型，確保只載入一次
WHISPER_MODEL_NAME = os.getenv("WHISPER_MODEL", "tiny")
WHISPER_DEVICE = os.getenv("WHISPER_DEVICE", "cpu") # 運行設備：'cpu' 或 'cuda'
WHISPER_COMPUTE_TYPE = os.getenv("WHISPER_COMPUTE_TYPE", "int8") # 計算類型：'int8' (CPU 優化) 或 'float16' (GPU 優化)
WHISPER_DOWNLOAD_ROOT = "./data/whisper_models" # 模型下載和儲存路徑

whisper_model = None
try:
    logger.info(f"嘗試載入 Faster-Whisper 模型: {WHISPER_MODEL_NAME} (Device: {WHISPER_DEVICE}, Compute Type: {WHISPER_COMPUTE_TYPE})")
    whisper_model = WhisperModel(
        WHISPER_MODEL_NAME,
        device=WHISPER_DEVICE,
        compute_type=WHISPER_COMPUTE_TYPE,
        download_root=WHISPER_DOWNLOAD_ROOT # 指定模型儲存路徑
    )
    logger.info(f"Faster-Whisper model '{WHISPER_MODEL_NAME}' loaded successfully.")
except Exception as e:
    logger.error(f"載入 Faster-Whisper 模型失敗: {e}", exc_info=True)


@app.post("/voice/transcribe", response_model=VoiceTranscriptionResponse)
async def transcribe_voice(audio_file: UploadFile = File(...)):
    """
    接收語音檔 (例如 webm 格式)，使用 Faster-Whisper 進行轉錄。
    """
    logger.info(f"收到語音轉錄請求: filename='{audio_file.filename}', content_type='{audio_file.content_type}'")

    if whisper_model is None:
        raise HTTPException(status_code=503, detail="語音轉錄服務暫時不可用，模型載入失敗。")

    # 檢查音頻格式是否支援
    if audio_file.content_type not in ["audio/webm", "audio/mp3", "audio/wav", "audio/ogg"]:
         logger.warning(f"不支援的音頻格式: {audio_file.content_type}")
         raise HTTPException(status_code=400, detail=f"音頻格式 {audio_file.content_type} 不支援。")

    try:
        # 將上傳的音頻文件讀入內存中的 BytesIO 流
        audio_bytes = await audio_file.read()
        audio_stream = io.BytesIO(audio_bytes)

        # 使用 Faster-Whisper 進行語音轉錄
        segments, info = whisper_model.transcribe(audio_stream, beam_size=5) # beam_size 是一個轉錄參數
        
        transcribed_text = ""
        for segment in segments:
            transcribed_text += segment.text + " " # 拼接所有轉錄片段
        
        transcribed_text = transcribed_text.strip() # 移除首尾空格
        
        logger.info(f"語音轉錄完成: '{transcribed_text[:50]}...'")
        return VoiceTranscriptionResponse(transcribed_text=transcribed_text)
    except Exception as e:
        logger.error(f"語音轉錄失敗: {e}", exc_info=True)
        raise HTTPException(status_code=500, detail=f"語音轉錄失敗: {e}")

# ... (其他文件和語音相關的 API 路由也在此文件中定義)
C. 前端服務 (Vue3)前端應用程式使用 Vue3 和 Pinia 構建，提供直觀的用戶界面。1. 認證狀態管理 - frontend/src/stores/auth.js使用 Pinia 管理用戶認證狀態和 API Token。import { defineStore } from 'pinia';
import axios from 'axios'; // 使用 axios 進行 HTTP 請求

// 從 Vite 環境變數獲取後端 API URL
const API_URL = import.meta.env.VITE_API_URL;

export const useAuthStore = defineStore('auth', {
  state: () => ({
    authToken: localStorage.getItem('authToken') || null, // 從 localStorage 獲取 token
    user: JSON.parse(localStorage.getItem('user')) || null, // 從 localStorage 獲取用戶信息
    loading: false, // 標示加載狀態
    error: null,    // 標示錯誤信息
  }),
  getters: {
    // 判斷用戶是否已登入
    isLoggedIn: (state) => !!state.authToken, 
    // 獲取當前用戶
    currentUser: (state) => state.user,
  },
  actions: {
    // 設置 HTTP 請求頭中的認證 Token
    setAuthHeader(token) {
      if (token) {
        axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
      } else {
        delete axios.defaults.headers.common['Authorization'];
      }
    },

    // 用戶登入方法
    async login(credentials) {
      this.loading = true;
      this.error = null;
      try {
        const response = await axios.post(`${API_URL}/login`, credentials, {
            // 確保跨域請求攜帶憑證（cookie/token）
            withCredentials: true 
        });
        const { token, user } = response.data;
        this.authToken = token;
        this.user = user;
        localStorage.setItem('authToken', token); // 將 token 儲存到 localStorage
        localStorage.setItem('user', JSON.stringify(user)); // 將用戶信息儲存到 localStorage
        this.setAuthHeader(token); // 設置全局 axios 請求頭
        this.loading = false;
        return true;
      } catch (error) {
        this.error = error.response?.data?.message || '登入失敗，請檢查憑證。';
        this.loading = false;
        return false;
      }
    },

    // 用戶註冊方法
    async register(userData) {
      this.loading = true;
      this.error = null;
      try {
        const response = await axios.post(`${API_URL}/register`, userData, {
             withCredentials: true
        });
        const { token, user } = response.data;
        this.authToken = token;
        this.user = user;
        localStorage.setItem('authToken', token);
        localStorage.setItem('user', JSON.stringify(user));
        this.setAuthHeader(token);
        this.loading = false;
        return true;
      } catch (error) {
        this.error = error.response?.data?.errors ? Object.values(error.response.data.errors).flat().join(', ') : '註冊失敗。';
        this.loading = false;
        return false;
      }
    },

    // 用戶登出方法
    async logout() {
      this.loading = true;
      try {
        await axios.post(`${API_URL}/logout`, {}, {
             withCredentials: true
        });
        this.authToken = null; // 清除 token
        this.user = null;     // 清除用戶信息
        localStorage.removeItem('authToken'); // 從 localStorage 移除 token
        localStorage.removeItem('user');     // 從 localStorage 移除用戶信息
        this.setAuthHeader(null); // 移除 axios 請求頭
        this.loading = false;
        return true;
      } catch (error) {
        this.error = error.response?.data?.message || '登出失敗。';
        this.loading = false;
        return false;
      }
    },

    // 初始化認證狀態 (在應用程式啟動時調用)
    initialize() {
      if (this.authToken) {
        this.setAuthHeader(this.authToken);
        // 可選：驗證 token 是否有效，例如調用 /api/user 獲取用戶信息
        // this.fetchUser(); 
      }
    },

    // 獲取認證用戶信息
    async fetchUser() {
      if (!this.isLoggedIn) return null;
      try {
        const response = await axios.get(`${API_URL}/user`, {
            withCredentials: true
        });
        this.user = response.data;
        localStorage.setItem('user', JSON.stringify(this.user));
        return this.user;
      } catch (error) {
        console.error('獲取用戶信息失敗:', error);
        this.logout(); // 如果 token 無效，則登出
        return null;
      }
    }
  },
});

2. 文件上傳組件核心邏輯 - frontend/src/views/DocumentsView.vue此組件允許用戶上傳文件並顯示處理狀態和搜尋結果。<template>
  <div class="max-w-4xl mx-auto p-4 bg-white shadow-lg rounded-lg">
    <h1 class="text-3xl font-bold text-center text-indigo-700 mb-6">文件智慧管理系統</h1>

    <!-- 文件上傳區塊 -->
    <div class="mb-8 p-6 border border-gray-200 rounded-lg shadow-sm">
      <h2 class="text-2xl font-semibold text-gray-800 mb-4">上傳新文件</h2>
      <form @submit.prevent="uploadDocument" class="space-y-4">
        <div>
          <label for="file-upload" class="block text-sm font-medium text-gray-700">選擇文件 (PDF, DOCX, TXT)</label>
          <input 
            type="file" 
            id="file-upload" 
            @change="handleFileUpload" 
            class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
            accept=".pdf,.doc,.docx,.txt"
          />
          <p v-if="selectedFile" class="mt-2 text-sm text-gray-600">已選擇文件: {{ selectedFile.name }}</p>
        </div>
        <div>
          <label for="category" class="block text-sm font-medium text-gray-700">文件分類 (可選)</label>
          <input 
            type="text" 
            id="category" 
            v-model="documentCategory" 
            placeholder="例如：報告, 合同, 政策"
            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
          />
        </div>
        <button 
          type="submit" 
          :disabled="!selectedFile || uploadLoading"
          class="w-full inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-base font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ uploadLoading ? '上傳中...' : '開始上傳' }}
        </button>
      </form>
      <p v-if="uploadMessage" :class="uploadError ? 'text-red-600' : 'text-green-600'" class="mt-4 text-center">
        {{ uploadMessage }}
      </p>
    </div>

    <!-- 文件搜尋區塊 -->
    <div class="mb-8 p-6 border border-gray-200 rounded-lg shadow-sm">
      <h2 class="text-2xl font-semibold text-gray-800 mb-4">搜尋文件</h2>
      <form @submit.prevent="searchDocuments" class="flex space-x-2">
        <input 
          type="text" 
          v-model="searchQuery" 
          placeholder="輸入您想搜尋的內容..."
          class="flex-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
        />
        <button 
          type="submit" 
          :disabled="!searchQuery.trim() || searchLoading"
          class="inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-base font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ searchLoading ? '搜尋中...' : '搜尋' }}
        </button>
      </form>
      <div v-if="searchResults.length > 0" class="mt-6">
        <h4 class="text-xl font-semibold text-gray-700 mb-3">搜尋結果:</h4>
        <ul class="space-y-3">
          <li v-for="result in searchResults" :key="result.document_id + '-' + result.score" class="p-4 border border-gray-200 rounded-md bg-gray-50">
            <p class="text-sm text-gray-500">相關性分數: {{ (result.score * 100).toFixed(2) }}%</p>
            <p class="font-medium text-indigo-700">文件 ID: {{ result.document_id }}</p>
            <p class="text-gray-800 break-words">{{ result.text_chunk }}</p>
            <p v-if="result.metadata && Object.keys(result.metadata).length > 0" class="text-xs text-gray-500 mt-1">
              元數據: {{ JSON.stringify(result.metadata) }}
            </p>
          </li>
        </ul>
      </div>
      <p v-else-if="searchLoading === false && searchedOnce" class="mt-4 text-center text-gray-600">沒有找到符合條件的結果。</p>
      <p v-if="searchError" class="mt-4 text-center text-red-600">
        搜尋失敗: {{ searchError }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { useAuthStore } from '../stores/auth'; // 導入認證狀態管理

const authStore = useAuthStore(); // 獲取認證 Store 實例

const API_URL = import.meta.env.VITE_API_URL; // 從環境變數獲取 API URL

// 文件上傳相關狀態
const selectedFile = ref(null);
const documentCategory = ref('');
const uploadLoading = ref(false);
const uploadMessage = ref('');
const uploadError = ref(false);

// 文件搜尋相關狀態
const searchQuery = ref('');
const searchLoading = ref(false);
const searchResults = ref([]);
const searchError = ref(null);
const searchedOnce = ref(false); // 標記是否已進行過搜尋

// 處理文件選擇事件
const handleFileUpload = (event) => {
  selectedFile.value = event.target.files[0];
  uploadMessage.value = ''; // 清除之前的上傳訊息
  uploadError.value = false;
};

// 上傳文件邏輯
const uploadDocument = async () => {
  if (!selectedFile.value) {
    uploadError.value = true;
    uploadMessage.value = '請選擇一個文件。';
    return;
  }

  uploadLoading.value = true;
  uploadMessage.value = '';
  uploadError.value = false;

  const formData = new FormData();
  formData.append('file', selectedFile.value); // 添加文件
  formData.append('category', documentCategory.value); // 添加分類

  try {
    const response = await axios.post(`${API_URL}/documents/upload`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data', // 設置正確的 Content-Type
      },
      withCredentials: true, // 確保攜帶跨域憑證
    });
    uploadMessage.value = '文件上傳成功！AI 正在處理中。';
    selectedFile.value = null; // 清空選擇
    documentCategory.value = ''; // 清空分類
    if (document.getElementById('file-upload')) {
        document.getElementById('file-upload').value = ''; // 清空文件輸入框
    }
  } catch (error) {
    console.error('文件上傳失敗:', error);
    uploadError.value = true;
    uploadMessage.value = error.response?.data?.message || '文件上傳失敗，請稍後再試。';
    if (error.response && error.response.data && error.response.data.errors) {
        // 顯示後端驗證錯誤
        uploadMessage.value += ' ' + Object.values(error.response.data.errors).flat().join(' ');
    }
  } finally {
    uploadLoading.value = false;
  }
};

// 搜尋文件邏輯
const searchDocuments = async () => {
  if (!searchQuery.value.trim()) {
    searchError.value = '請輸入搜尋內容。';
    return;
  }

  searchLoading.value = true;
  searchError.value = null;
  searchResults.value = [];
  searchedOnce.value = true;

  try {
    const response = await axios.get(`${API_URL}/documents/search`, {
      params: { q: searchQuery.value }, // 搜尋參數
      withCredentials: true,
    });
    searchResults.value = response.data.results;
  } catch (error) {
    console.error('文件搜尋失敗:', error);
    searchError.value = error.response?.data?.message || '文件搜尋失敗，請稍後再試。';
  } finally {
    searchLoading.value = false;
  }
};
</script>

<style scoped>
/* Tailwind CSS 會自動處理大部分樣式，這裡只保留了少量的 scoped 樣式 */
/* 可以根據需要添加更多自定義樣式 */
</style>
3. 語音錄製與互動組件核心邏輯 - frontend/src/views/VoiceAssistantView.vue此組件負責處理麥克風輸入、語音錄製，並將音頻數據發送到後端進行轉錄和 AI 回應。<template>
  <div class="max-w-xl mx-auto p-4 bg-white shadow-lg rounded-lg">
    <h1 class="text-3xl font-bold text-center text-green-700 mb-6">AI 語音助理</h1>

    <div class="flex flex-col items-center space-y-4">
      <!-- 錄音按鈕 -->
      <button
        @click="toggleRecording"
        :class="{
          'bg-red-600 hover:bg-red-700': isRecording,
          'bg-green-600 hover:bg-green-700': !isRecording,
        }"
        :disabled="processing"
        class="w-32 h-32 rounded-full text-white flex items-center justify-center shadow-lg transform transition-all duration-200 ease-in-out"
        :aria-label="isRecording ? '停止錄音' : '開始錄音'"
      >
        <svg v-if="!isRecording" class="w-16 h-16" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
          <path fill-rule="evenodd" d="M7 4a3 3 0 00-3 3v6a3 3 0 003 3h6a3 3 0 003-3V7a3 3 0 00-3-3H7zM4 7a4 4 0 014-4h4a4 4 0 014 4v6a4 4 0 01-4 4H8a4 4 0 01-4-4V7zM7 9a1 1 0 000 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
          <path fill-rule="evenodd" d="M7 4a3 3 0 00-3 3v6a3 3 0 003 3h6a3 3 0 003-3V7a3 3 0 00-3-3H7z" clip-rule="evenodd" />
          <circle cx="10" cy="10" r="3" fill="white"/>
        </svg>
        <svg v-else class="w-16 h-16 animate-pulse" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
          <path fill-rule="evenodd" d="M7 4a3 3 0 00-3 3v6a3 3 0 003 3h6a3 3 0 003-3V7a3 3 0 00-3-3H7z" clip-rule="evenodd" />
        </svg>
      </button>

      <!-- 狀態訊息 -->
      <p v-if="processing" class="text-indigo-600 font-semibold text-lg">AI 思考中...</p>
      <p v-else-if="isRecording" class="text-red-500 font-semibold text-lg">錄音中...</p>
      <p v-else class="text-gray-600 text-lg">點擊麥克風開始錄音</p>

      <!-- 錯誤訊息 -->
      <p v-if="error" class="text-red-500 text-center mt-2">{{ error }}</p>
    </div>

    <!-- 對話歷史 -->
    <div class="mt-8 p-6 border border-gray-200 rounded-lg shadow-sm max-h-96 overflow-y-auto">
      <h2 class="text-2xl font-semibold text-gray-800 mb-4">對話歷史:</h2>
      <div v-if="conversationHistory.length === 0" class="text-center text-gray-500">
        目前沒有對話。
      </div>
      <div v-for="(message, index) in conversationHistory" :key="index" class="mb-3">
        <div v-if="message.speaker === 'user'" class="text-right">
          <span class="inline-block bg-blue-100 text-blue-800 rounded-lg px-4 py-2 max-w-xs break-words">
            你: {{ message.text }}
          </span>
        </div>
        <div v-else class="text-left">
          <span class="inline-block bg-green-100 text-green-800 rounded-lg px-4 py-2 max-w-xs break-words">
            AI 助手: {{ message.text }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { useAuthStore } from '../stores/auth'; // 導入認證狀態管理

const authStore = useAuthStore();
const API_URL = import.meta.env.VITE_API_URL;

const isRecording = ref(false); // 是否正在錄音
const processing = ref(false);  // 是否正在處理語音 (AI 思考中)
const mediaRecorder = ref(null); // MediaRecorder 實例
const audioChunks = ref([]);     // 儲存錄音數據塊
const error = ref(null);         // 錯誤信息

// 對話歷史，包含發言者和內容
const conversationHistory = ref([]); 

// 獲取當前用戶 ID，用於標識對話
const currentUserId = ref(localStorage.getItem('userId') || 'guest_user'); // 這裡簡單從 localStorage 取，實際應從認證 Store 獲取

// 頁面加載時獲取歷史對話
onMounted(async () => {
  await fetchConversationHistory();
});

// 頁面卸載時停止錄音（如果正在錄音）
onUnmounted(() => {
  if (isRecording.value && mediaRecorder.value) {
    mediaRecorder.value.stop();
    isRecording.value = false;
  }
});

// 獲取對話歷史
const fetchConversationHistory = async () => {
    try {
        const response = await axios.get(`${API_URL}/voice/history/${currentUserId.value}`, {
            withCredentials: true,
        });
        // 將後端返回的 {speaker, text, created_at} 格式轉換為 {speaker, text}
        conversationHistory.value = response.data.map(msg => ({
            speaker: msg.speaker,
            text: msg.text
        }));
    } catch (err) {
        console.error('獲取對話歷史失敗:', err);
        error.value = '無法獲取對話歷史。';
    }
};

// 切換錄音狀態 (開始/停止)
const toggleRecording = async () => {
  if (processing.value) return; // 正在處理時不允許操作

  if (!isRecording.value) {
    // 開始錄音
    error.value = null; // 清除之前的錯誤
    audioChunks.value = []; // 清空之前的音頻數據

    try {
      // 請求麥克風權限
      const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
      // 創建 MediaRecorder 實例，指定輸出格式為 audio/webm
      mediaRecorder.value = new MediaRecorder(stream, { mimeType: 'audio/webm' });

      // 監聽數據可用事件
      mediaRecorder.value.ondataavailable = (event) => {
        audioChunks.value.push(event.data);
      };

      // 監聽停止錄音事件
      mediaRecorder.value.onstop = async () => {
        processing.value = true; // 進入處理中狀態
        isRecording.value = false; // 錄音狀態結束
        // 將所有音頻數據塊組合成一個 Blob
        const audioBlob = new Blob(audioChunks.value, { type: 'audio/webm' });
        
        // 將 Blob 發送到後端進行處理
        await sendAudioToBackend(audioBlob);

        // 停止麥克風流
        stream.getTracks().forEach(track => track.stop());
        processing.value = false; // 處理結束
      };

      mediaRecorder.value.start(); // 開始錄音
      isRecording.value = true;
      console.log('錄音開始...');

    } catch (err) {
      console.error('無法訪問麥克風:', err);
      error.value = '無法獲取麥克風權限或開始錄音。請檢查瀏覽器設定。';
    }
  } else {
    // 停止錄音
    if (mediaRecorder.value && mediaRecorder.value.state === 'recording') {
      mediaRecorder.value.stop();
      console.log('錄音停止...');
    }
  }
};

// 將音頻 Blob 發送到後端 API
const sendAudioToBackend = async (audioBlob) => {
  const formData = new FormData();
  // 將音頻 Blob 命名為 'audio'，後端將以此名稱接收文件
  formData.append('audio', audioBlob, 'audio.webm'); 
  formData.append('user_id', currentUserId.value); // 傳遞用戶 ID

  try {
    const response = await axios.post(`${API_URL}/voice/process`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data', // 設置正確的 Content-Type
      },
      withCredentials: true,
    });

    const { transcribed_text, response_text } = response.data;
    // 將轉錄文本和 AI 回應添加到對話歷史中
    conversationHistory.value.push({ speaker: 'user', text: transcribed_text });
    conversationHistory.value.push({ speaker: 'assistant', text: response_text });
    error.value = null; // 清除任何錯誤

  } catch (err) {
    console.error('發送音頻到後端失敗:', err);
    error.value = err.response?.data?.message || '處理語音輸入失敗。';
  }
};
</script>

<style scoped>
/* Tailwind CSS 處理大部分樣式。這裡可以添加自定義過渡效果或動畫。 */
button {
  transition: all 0.2s ease-in-out;
}
button:hover {
  transform: scale(1.05);
}
button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}
</style>
⚡ 快速啟動 (Quick Start)請確保您的系統已安裝 Docker 和 Docker Compose。進入專案目錄：cd workflow-ai-platform
配置環境變數：複製 .env.example 為 .env，然後打開 .env 檔案，務必填寫您的 OPENAI_API_KEY。cp .env.example .env
# 打開 .env 檔案並填寫 OPENAI_API_KEY
構建並啟動所有服務：這會下載所有必要的 Docker 映像並啟動 Laravel 後端、Vue 前端、FastAPI AI 微服務、MySQL 資料庫和 Qdrant 向量資料庫。docker compose build
docker compose up -d
Laravel 後端初始化 (重要)：進入 backend 容器並執行以下命令來初始化 Laravel 應用程式：docker exec -it workflow-ai-backend bash
php artisan key:generate           # 生成應用程式加密金鑰
php artisan migrate                # 執行資料庫遷移，創建 documents 和 voices 表
php artisan db:seed                # 可選：填充假數據，包括用戶、文件和語音記錄
php artisan scribe:generate        # 生成 API 文檔
exit                               # 退出容器
Caddy 反向代理 (可選)：如果您希望使用 Caddy 作為統一入口，請先在您的主機上安裝 Caddy (參考 Caddy 官方文檔)，然後在專案根目錄執行：caddy run --config Caddyfile
之後可透過以下地址訪問：Frontend: http://localhost:8081Backend: http://localhost:8080AI Orchestrator: http://localhost:8082🌐 訪問應用程式與 API 文檔前端應用: http://localhost:5173 (或通過 Caddy 的 http://localhost:8081)後端 Laravel API: http://localhost:8000/api (或通過 Caddy 的 http://localhost:8080/api)AI 微服務 API (FastAPI) 文檔 (Swagger UI): http://localhost:8001/docs (或通過 Caddy 的 http://localhost:8082/docs)後端 Laravel API 文檔 (Scribe): http://localhost:8000/docs (或通過 Caddy 的 http://localhost:8080/docs)✅ 運行測試 (Running Tests)前端單元測試 (Vitest)進入前端目錄：cd frontend
運行測試：npm test
# 或 npm run test:watch 監聽文件變化
完成後返回專案根目錄：cd ..
前端端到端測試 (Cypress E2E)確保所有 Docker 服務都在運行中。為文件上傳測試準備假文件：Cypress 的文件上傳測試需要一個實際的 PDF 文件。請在 frontend/cypress/fixtures/ 目錄下手動創建一個名為 test_pdf.pdf 的任意 PDF 文件（可以是一個空白 PDF，用於模擬上傳）。進入前端目錄：cd frontend
打開 Cypress UI，選擇 E2E 測試並運行：npm run cypress:open
或在無頭模式下運行所有測試：npm run cypress:run
完成後返回專案根目錄：cd ..
後端測試 (PHPUnit)進入 Laravel 後端容器：docker exec -it workflow-ai-backend bash
運行 PHPUnit 測試：./vendor/bin/phpunit
退出容器：exit
📝 開發筆記 (Development Notes)OpenAI API Key: AI Orchestrator 依賴於 OpenAI API 進行 Embedding 和 LLM 功能。請確保您的 OPENAI_API_KEY 在根目錄的 .env 文件中已正確設定，否則 AI 服務將無法正常工作。Faster-Whisper 模型: AI Orchestrator 中的 faster-whisper 模型會在首次運行時自動下載（預設為 tiny 模型）。模型文件將存儲在 ai-orchestrator/data/whisper_models/ 目錄中。如果您需要使用更大或更精確的模型（例如 base, small, medium, large-v2, large-v3），請修改根目錄的 .env 文件中的 WHISPER_MODEL 參數。Laravel Sanctum 與 CORS: 為了確保前端與後端的認證正常工作，SANCTUM_STATEFUL_DOMAINS 和 SESSION_DOMAIN 環境變數已在 .env.example 中配置。它們允許跨域請求在特定域（例如您的前端地址）上安全地發送憑證。路由保護: 前端應用中的敏感路由（如 /documents, /voice）已配置了路由守衛，確保只有已認證的用戶才能訪問。未登入的用戶將被重定向到登入頁面。希望這份詳細的 README.md 文件能幫助您更好地理解和使用這個 Workflow AI Platform 專案！