from fastapi import FastAPI, UploadFile, File, HTTPException, Depends
from pydantic import BaseModel
from typing import List, Dict, Any, Optional
import logging
import io # For handling audio file in memory
import os # For environment variables

# Faster-Whisper 導入
from faster_whisper import WhisperModel

from .services.document_service import process_document_embedding, search_documents_qdrant, summarize_document
from .services.rag_pipeline import generate_response_from_rag
from .models.document_models import DocumentUploadRequest, DocumentSearchResponse, DocumentSummaryResponse
from .models.voice_models import VoiceTranscriptionResponse, VoiceResponseRequest, VoiceResponse

# 配置日誌
logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(name)s - %(levelname)s - %(message)s')
logger = logging.getLogger(__name__)

app = FastAPI(title="AI Orchestrator Microservice")

# 全局載入 Whisper 模型，只載入一次
# 從環境變數讀取模型配置
WHISPER_MODEL_NAME = os.getenv("WHISPER_MODEL", "tiny")
WHISPER_DEVICE = os.getenv("WHISPER_DEVICE", "cpu") # 'cpu' or 'cuda'
WHISPER_COMPUTE_TYPE = os.getenv("WHISPER_COMPUTE_TYPE", "int8") # 'int8' for CPU, 'float16' for GPU
WHISPER_DOWNLOAD_ROOT = "./data/whisper_models" # 模型下載路徑

whisper_model = None
try:
    logger.info(f"嘗試載入 Faster-Whisper 模型: {WHISPER_MODEL_NAME} (Device: {WHISPER_DEVICE}, Compute Type: {WHISPER_COMPUTE_TYPE})")
    whisper_model = WhisperModel(
        WHISPER_MODEL_NAME,
        device=WHISPER_DEVICE,
        compute_type=WHISPER_COMPUTE_TYPE,
        download_root=WHISPER_DOWNLOAD_ROOT
    )
    logger.info(f"Faster-Whisper model '{WHISPER_MODEL_NAME}' loaded successfully.")
except Exception as e:
    logger.error(f"載入 Faster-Whisper 模型失敗: {e}", exc_info=True)

@app.get("/")
async def root():
    """AI Orchestrator 服務的根路由."""
    logger.info("收到根路由請求.")
    return {"message": "AI Orchestrator is running and ready for duty!"}

@app.post("/documents/upload", response_model=DocumentSummaryResponse)
async def upload_document(request: DocumentUploadRequest):
    """
    文件上傳後，通知 AI Orchestrator 進行向量化與摘要。
    實際文件內容由後端提供路徑或透過其他方式傳輸。
    """
    logger.info(f"收到文件上傳請求: document_id={request.document_id}, file_path={request.file_path}")
    try:
        # 這裡會模擬處理文件內容（例如從後端提供的路徑讀取）
        # 實際應用中，文件可能需要從共享儲存或API獲取
        summary_text = await process_document_embedding(request.document_id, request.file_path, request.metadata)
        logger.info(f"文件 {request.document_id} 處理成功，摘要: {summary_text[:50]}...")
        return DocumentSummaryResponse(document_id=request.document_id, summary=summary_text, status="processed")
    except FileNotFoundError:
        logger.error(f"文件路徑不存在: {request.file_path}")
        raise HTTPException(status_code=404, detail=f"文件路徑不存在: {request.file_path}")
    except Exception as e:
        logger.error(f"文件 {request.document_id} 處理失敗: {e}", exc_info=True)
        raise HTTPException(status_code=500, detail=f"文件處理失敗: {e}")

@app.get("/documents/search", response_model=DocumentSearchResponse)
async def search_documents(query: str):
    """
    執行語意搜尋，從向量資料庫中檢索相關文件。
    """
    logger.info(f"收到文件搜尋請求: query='{query}'")
    try:
        results = await search_documents_qdrant(query)
        logger.info(f"文件搜尋完成，找到 {len(results)} 個結果。")
        return DocumentSearchResponse(query=query, results=results)
    except Exception as e:
        logger.error(f"文件搜尋失敗: {e}", exc_info=True)
        raise HTTPException(status_code=500, detail=f"文件搜尋失敗: {e}")

@app.post("/documents/summarize", response_model=DocumentSummaryResponse)
async def get_document_summary(document_id: int, text_content: str):
    """
    對給定的文件內容進行摘要。
    """
    logger.info(f"收到文件摘要請求: document_id={document_id}")
    try:
        summary = await summarize_document(document_id, text_content)
        logger.info(f"文件 {document_id} 摘要完成: {summary[:50]}...")
        return DocumentSummaryResponse(document_id=document_id, summary=summary, status="completed")
    except Exception as e:
        logger.error(f"文件 {document_id} 摘要失敗: {e}", exc_info=True)
        raise HTTPException(status_code=500, detail=f"文件摘要失敗: {e}")

@app.post("/voice/transcribe", response_model=VoiceTranscriptionResponse)
async def transcribe_voice(audio_file: UploadFile = File(...)):
    """
    接收語音檔 (webm 格式)，使用 Faster-Whisper 進行轉錄。
    """
    logger.info(f"收到語音轉錄請求: filename='{audio_file.filename}', content_type='{audio_file.content_type}'")

    if whisper_model is None:
        logger.error("Whisper 模型未載入，無法執行轉錄。")
        raise HTTPException(status_code=503, detail="語音轉錄服務暫時不可用，模型載入失敗。")

    # 確保音頻格式為 Faster-Whisper 支援的類型，前端通常會發送 webm
    # Faster-Whisper 內部會使用 ffmpeg 處理多種格式，但為避免不必要的轉換複雜度，建議前端保持一致
    if audio_file.content_type not in ["audio/webm", "audio/mp3", "audio/wav", "audio/ogg"]:
         logger.warning(f"不支援的音頻格式: {audio_file.content_type}")
         raise HTTPException(status_code=400, detail=f"音頻格式 {audio_file.content_type} 不支援，請上傳 mp3, wav, ogg, webm。")

    try:
        # 將 UploadFile 讀入 BytesIO，然後傳遞給 Faster-Whisper
        audio_bytes = await audio_file.read()
        audio_stream = io.BytesIO(audio_bytes)

        # Faster-Whisper 轉錄
        # options 可以根據需求調整，例如語言、VAD 閾值等
        # transcribe_options = dict(beam_size=5, vad_filter=True, vad_parameters=dict(min_silence_duration_ms=500))
        segments, info = whisper_model.transcribe(audio_stream, beam_size=5) # 移除 options 參數
        
        transcribed_text = ""
        for segment in segments:
            transcribed_text += segment.text + " "
        
        transcribed_text = transcribed_text.strip()
        
        logger.info(f"語音轉錄完成: '{transcribed_text[:50]}...'")
        return VoiceTranscriptionResponse(transcribed_text=transcribed_text)
    except Exception as e:
        logger.error(f"語音轉錄失敗: {e}", exc_info=True)
        raise HTTPException(status_code=500, detail=f"語音轉錄失敗: {e}")

@app.post("/voice/respond", response_model=VoiceResponse)
async def get_voice_response(request: VoiceResponseRequest):
    """
    接收轉錄後的文字，透過 RAG Pipeline 進行語意解析並生成回應。
    """
    logger.info(f"收到語音回應請求: user_id={request.user_id}, prompt='{request.prompt}'")
    try:
        response_text = await generate_response_from_rag(request.user_id, request.prompt, request.conversation_history)
        logger.info(f"語音回應生成完成: '{response_text[:50]}...'")
        return VoiceResponse(user_id=request.user_id, response_text=response_text)
    except Exception as e:
        logger.error(f"語音回應生成失敗: {e}", exc_info=True)
        raise HTTPException(status_code=500, detail=f"語音回應生成失敗: {e}")
