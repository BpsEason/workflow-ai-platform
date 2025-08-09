<?php

namespace App\Http\Controllers;

use Illuminate->Http\Request;
use Illuminate->Support\Facades\Storage;
use Illuminate->Support->Facades\Http;
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
     *
     * @authenticated
     * @bodyParam audio file required The audio file to process (max 10MB, allowed types: mp3, wav, ogg, webm). Example: (binary file)
     * @bodyParam user_id string required The ID of the user. This should ideally come from an authenticated user. Example: "user_uuid_123"
     * @responseFile status=200 scenarios/voice_process_success.json
     * @responseFile status=422 scenarios/voice_process_validation_error.json
     * @responseFile status=500 scenarios/voice_process_ai_error.json
     */
    public function process(Request $request)
    {
        try {
            $request->validate([
                'audio' => 'required|file|mimes:mp3,wav,ogg,webm|max:10240', // 10MB
                'user_id' => 'required|string', // 識別用戶，或從認證獲取
            ]);
        } catch (ValidationException $e) {
            Log::error('語音文件驗證失敗: ' . json_encode($e->errors()));
            return response()->json(['message' => '語音文件驗證失敗', 'errors' => $e->errors()], 422);
        }

        $audioFile = $request->file('audio');
        $filePath = $audioFile->store('voices'); // 儲存語音檔
        $userId = $request->input('user_id'); // 實際應從認證系統獲取

        Log::info("收到語音處理請求，用戶ID: {$userId}，文件路徑: {$filePath}");

        try {
            // 1. 轉錄語音
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

            // 2. 獲取對話歷史（如果需要）
            $history = Voice::where('user_id', $userId)
                             ->orderBy('created_at', 'asc')
                             ->select('speaker', 'text')
                             ->get()
                             ->map(function($item) {
                                 return ['role' => ($item->speaker == 'user' ? 'user' : 'assistant'), 'content' => $item->text];
                             })
                             ->toArray();
            Log::info("獲取到用戶 {$userId} 的歷史對話數: " . count($history));

            // 3. 發送轉錄文本給 AI Orchestrator 獲取回應
            Log::info("發送轉錄文本至 AI Orchestrator 獲取回應...");
            $aiResponse = Http::timeout(120)->post(env('AI_ORCHESTRATOR_URL') . '/voice/respond', [
                'user_id' => $userId,
                'prompt' => $transcribedText,
                'conversation_history' => $history
            ]);

            if (!$aiResponse->successful()) {
                Log::error("AI Orchestrator 語音回應失敗。錯誤: " . $aiResponse->body());
                return response()->json(['message' => 'AI 回應生成失敗', 'error' => $aiResponse->body()], $aiResponse->status());
            }
            $responseText = $aiResponse->json('response_text');
            Log::info("AI 回應生成成功: '{$responseText}'");

            // 儲存對話歷史
            Voice::create([
                'user_id' => $userId,
                'speaker' => 'user',
                'text' => $transcribedText,
                'audio_path' => $filePath,
            ]);
            Voice::create([
                'user_id' => $userId,
                'speaker' => 'assistant',
                'text' => $responseText,
                'audio_path' => null, // 機器人回應通常沒有語音檔
            ]);
            Log::info("對話歷史已儲存。");

            return response()->json([
                'message' => '語音處理成功',
                'transcribed_text' => $transcribedText,
                'response_text' => $responseText,
            ]);

        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            Log::error("連接 AI Orchestrator 失敗 (語音處理)。錯誤: " . $e->getMessage());
            return response()->json(['message' => '無法連接AI服務', 'error' => $e->getMessage()], 500);
        } catch (\Exception $e) {
            Log::error("處理語音時發生未知錯誤: " . $e->getMessage());
            return response()->json(['message' => '語音處理服務異常', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * 獲取對話歷史。
     *
     * 獲取特定用戶的語音對話歷史記錄。
     *
     * @authenticated
     * @urlParam user_id string required 用戶的 ID。Example: "user_uuid_123"
     * @responseFile status=200 scenarios/voice_history_success.json
     */
    public function history(Request $request, string $userId)
    {
        Log::info("收到獲取語音對話歷史請求，用戶ID: {$userId}");
        $history = Voice::where('user_id', $userId)
                        ->orderBy('created_at', 'asc')
                        ->get(['speaker', 'text', 'created_at']);

        return response()->json($history);
    }
}
