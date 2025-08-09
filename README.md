Workflow AI Platform歡迎來到 Workflow AI Platform！這是一個現代化的全端應用程式，旨在透過人工智慧技術簡化您的文件管理和語音互動工作流程。本專案整合了 robust 的 Laravel 後端、響應迅速的 Vue 3 前端和強大的 FastAPI AI 微服務，並透過 Docker Compose 實現便捷的容器化部署。🚀Project Highlights全端整合Laravel (PHP) 後端 + Vue 3 (JavaScript) 前端 + FastAPI (Python) AI 微服務智慧文件管理文件上傳與處理：支援 PDF、DOCX、TXTAI 摘要：利用 OpenAI LLM 自動生成文件摘要語意搜尋：OpenAI Embedding + Qdrant 向量搜尋AI 語音助理高效語音轉錄：整合 Faster-WhisperRAG（檢索增強生成）：結合檢索結果與問答引擎對話歷史：完整記錄用戶與 AI 互動安全認證Laravel Sanctum 實現 API Token 認證與 CORS 配置資料持久化MySQL 儲存應用資料；Qdrant 作為向量資料庫自動化 API 文件Laravel Scribe 生成互動式文件全面測試Vitest、Cypress、PHPUnit 等單元／E2E 測試便捷部署一鍵 Docker Compose 啟動所有服務Project Structure專案採用微服務架構，主要分為以下幾個部分：backend/ - Laravel 後端app/Http/Controllers/ - AuthController, DocumentController, VoiceControllerModels/ - User, Document, Voiceconfig/scribe.php - API 文件設定database/migrations/ - 資料表遷移seeders/ - 假資料填充nginx/ - Nginx 設定etc/supervisor/ - Supervisor 設定routes/api.php - API 路由frontend/ - Vue 3 前端public/src/assets/components/router/stores/views/App.vue, main.jscypress/ - E2E 測試package.jsonvite.config.jsai-orchestrator/ - FastAPI AI 服務app/services/ - document_service, rag_pipelinemodels/main.pydata/ - Whisper 模型儲存requirements.txttests/data-volumes/ - MySQL & Qdrant 資料持久化.env.example - 環境變數範本Caddyfile - Caddy 反向代理配置README.md - 專案說明（本檔）docker-compose.yml - 服務定義Key Code Snippets1. 用戶認證 (AuthController)<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user  = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'token'   => $token,
            'user'    => $user,
        ], 201);
    }
}
2. 文件處理 (DocumentController)<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Document;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class DocumentController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file'     => 'required|file|mimes:pdf,docx,txt|max:10240',
            'category' => 'nullable|string|max:255',
        ]);

        $path     = $request->file('file')->store('documents');
        $document = Document::create([
            'user_id'   => $request->user()->id,
            'name'      => $request->file('file')->getClientOriginalName(),
            'file_path' => $path,
            'status'    => 'pending_ai',
            'category'  => $request->input('category'),
        ]);

        // 觸發 AI 處理
        $aiResp = Http::post(
            env('AI_ORCHESTRATOR_URL') . '/documents/upload',
            ['document_id' => $document->id, 'file_path' => storage_path('app/'.$path)]
        );

        if ($aiResp->successful()) {
            $data = $aiResp->json();
            $document->update([
                'summary' => $data['summary'],
                'status'  => $data['status'],
            ]);
        }

        return response()->json(['document' => $document], 201);
    }
}
3. 語音助理 (VoiceController)<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Voice;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class VoiceController extends Controller
{
    public function process(Request $request)
    {
        $request->validate([
            'audio'   => 'required|file|mimes:mp3,wav,webm|max:10240',
            'user_id' => 'required|string',
        ]);

        $filePath = $request->file('audio')->store('voices');
        // 推送給 AI Orchestrator 轉錄
        $transResp = Http::attach(
            'audio_file',
            file_get_contents(storage_path("app/{$filePath}")),
            basename($filePath)
        )->post(env('AI_ORCHESTRATOR_URL') . '/voice/transcribe');

        $text = $transResp->json('transcribed_text');
        // 取得 AI 回應
        $aiResp = Http::post(env('AI_ORCHESTRATOR_URL') . '/voice/respond', [
            'user_id'             => $request->user_id,
            'prompt'              => $text,
            'conversation_history' => Voice::where('user_id', $request->user_id)
                                            ->pluck('text','speaker')
                                            ->toArray(),
        ]);

        return response()->json([
            'transcribed_text' => $text,
            'response_text'    => $aiResp->json('response_text'),
        ]);
    }
}
Quick Start複製範本cp .env.example .env
# 編輯 .env，設定 OPENAI_API_KEY
啟動服務docker compose build
docker compose up -d
後端初始化docker exec -it workflow-ai-backend bash
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan scribe:generate
exit
（可選）Caddy 反向代理caddy run --config Caddyfile
訪問地址前端應用：http://localhost:5173Laravel API：http://localhost:8000/apiFastAPI Swagger：http://localhost:8001/docsLaravel Scribe Docs：http://localhost:8000/docsRunning Tests前端單元測試 (Vitest)cd frontend
npm test
前端 E2E 測試 (Cypress)cd frontend
npm run cypress:open
# or
npm run cypress:run
後端 PHPUnitdocker exec -it workflow-ai-backend bash
vendor/bin/phpunit
exit
Development NotesOpenAI API Key：必須在 .env 中設定 OPENAI_API_KEYFaster-Whisper：首次運行會自動下載（預設 tiny），修改 WHISPER_MODEL 可選用更大模型Sanctum & CORS：.env.example 已配置 SANCTUM_STATEFUL_DOMAINS、SESSION_DOMAIN路由守衛：Vue 前端對 /documents、/voice 路由進行保護，未登入自動重定向Enjoy exploring your new Workflow AI Platform! 🚀
