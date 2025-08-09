<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate->Support\Facades\Storage;
use Illuminate->Support\Facades->Http;
use App\Models\Document;
use Illuminate->Validation->ValidationException;
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
     *
     * @authenticated
     * @bodyParam file file required The file to upload (max 10MB, allowed types: pdf, doc, docx, txt). Example: (binary file)
     * @bodyParam category string The category of the document. Example: "Policy"
     * @responseFile status=201 scenarios/document_upload_success.json
     * @responseFile status=422 scenarios/document_upload_validation_error.json
     * @responseFile status=500 scenarios/document_upload_ai_error.json
     */
    public function upload(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:pdf,doc,docx,txt|max:10240', // 10MB
                'category' => 'nullable|string|max:255'
            ]);
        } catch (ValidationException $e) {
            Log::error('文件上傳驗證失敗: ' . json_encode($e->errors()));
            return response()->json(['message' => '文件驗證失敗', 'errors' => $e->errors()], 422);
        }

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $path = $file->store('documents'); // 儲存到 storage/app/documents

        // 創建文件記錄
        $document = Document::create([
            'user_id' => $request->user()->id ?? null, // 假設有認證用戶，否則為 null
            'name' => $originalName,
            'file_path' => $path,
            'summary' => null, // 待 AI 服務填充
            'status' => 'pending_ai', // 待 AI 處理
            'category' => $request->input('category') // 可以從前端傳入分類
        ]);

        Log::info("文件 {$document->id} 已上傳，路徑: {$path}，準備發送至 AI Orchestrator。");

        // 通知 AI Orchestrator 進行處理 (非同步，或使用佇列)
        try {
            // 使用 absolute path for AI service to read
            $aiResponse = Http::timeout(120)->post(env('AI_ORCHESTRATOR_URL') . '/documents/upload', [
                'document_id' => $document->id,
                'file_path' => storage_path('app/' . $path),
                'metadata' => [
                    'original_name' => $originalName,
                    'category' => $document->category,
                    'uploaded_by' => $document->user_id,
                ]
            ]);

            if ($aiResponse->successful()) {
                $ai_result = $aiResponse->json();
                $document->update([
                    'summary' => $ai_result['summary'] ?? null,
                    'status' => $ai_result['status'] ?? 'processed_ai',
                ]);
                Log::info("文件 {$document->id} AI 處理成功。");
            } else {
                $document->update(['status' => 'ai_failed']);
                Log::error("AI Orchestrator 文件處理失敗，文件 ID: {$document->id}。錯誤: " . $aiResponse->body());
                return response()->json(['message' => '文件上傳成功，但AI處理失敗', 'error' => $aiResponse->body()], $aiResponse->status());
            }
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            $document->update(['status' => 'ai_connection_error']);
            Log::error("連接 AI Orchestrator 失敗，文件 ID: {$document->id}。錯誤: " . $e->getMessage());
            return response()->json(['message' => '文件上傳成功，但無法連接AI服務', 'error' => $e->getMessage()], 500);
        } catch (\Exception $e) {
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

    /**
     * 語意搜尋文件。
     *
     * 根據用戶提供的自然語言查詢，透過 AI 微服務進行語意搜尋，返回相關文件片段。
     *
     * @authenticated
     * @queryParam q string required 搜尋查詢內容。Example: "蘋果公司的最新財報"
     * @responseFile status=200 scenarios/document_search_success.json
     * @responseFile status=422 scenarios/document_search_validation_error.json
     * @responseFile status=500 scenarios/document_search_ai_error.json
     */
    public function search(Request $request)
    {
        try {
            $request->validate([
                'q' => 'required|string|min:2|max:500'
            ]);
        } catch (ValidationException $e) {
            Log::error('文件搜尋驗證失敗: ' . json_encode($e->errors()));
            return response()->json(['message' => '搜尋查詢驗證失敗', 'errors' => $e->errors()], 422);
        }

        $query = $request->get('q');
        Log::info("收到文件搜尋請求: query='{$query}'");

        try {
            $response = Http::get(env('AI_ORCHESTRATOR_URL') . '/documents/search', ['query' => $query]);

            if ($response->successful()) {
                Log::info("AI Orchestrator 搜尋成功。");
                return response()->json($response->json());
            } else {
                Log::error("AI Orchestrator 搜尋失敗。錯誤: " . $response->body());
                return response()->json(['message' => '文件搜尋失敗', 'error' => $response->body()], $response->status());
            }
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            Log::error("連接 AI Orchestrator 失敗 (搜尋請求)。錯誤: " . $e->getMessage());
            return response()->json(['message' => '無法連接AI服務進行搜尋', 'error' => $e->getMessage()], 500);
        } catch (\Exception $e) {
            Log::error("AI Orchestrator 搜尋時發生未知錯誤。錯誤: " . $e->getMessage());
            return response()->json(['message' => 'AI搜尋服務異常', 'error' => $e->getMessage()], 500);
        }
    }
}
