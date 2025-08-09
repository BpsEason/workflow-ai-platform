<?php

use Illuminate->Http\Request;
use Illuminate->Support->Facades->Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\VoiceController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Auth 認證路由 (不需要認證即可訪問)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// 受保護的路由 (需要 Sanctum 認證)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']); // 獲取當前認證用戶資訊

    // 文件模組路由 (Document Module)
    Route::prefix('documents')->group(function () {
        // POST /api/documents/upload - 上傳文件並觸發 AI 處理
        Route::post('/upload', [DocumentController::class, 'upload']);
        // GET /api/documents/search?q=... - 語意搜尋文件
        Route::get('/search', [DocumentController::class, 'search']);
    });

    // 語音模組路由 (Voice Module)
    Route::prefix('voice')->group(function () {
        // POST /api/voice/process - 處理語音輸入 (轉錄 -> AI 回應)
        Route::post('/process', [VoiceController::class, 'process']);
        // GET /api/voice/history/{user_id} - 獲取特定使用者的語音對話歷史
        Route::get('/history/{user_id}', [VoiceController::class, 'history']);
    });
});
