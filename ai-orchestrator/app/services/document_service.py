from qdrant_client import QdrantClient, models
from typing import List, Dict, Any
import os
import logging
from dotenv import load_dotenv

# 確保載入環境變數
load_dotenv()

# LangChain OpenAI 導入
from langchain_community.embeddings import OpenAIEmbeddings
from langchain_openai import ChatOpenAI
from langchain.text_splitter import RecursiveCharacterTextSplitter

logger = logging.getLogger(__name__)

QDRANT_HOST = os.getenv("QDRANT_HOST", "qdrant")
QDRANT_PORT = int(os.getenv("QDRANT_PORT", 6333))
COLLECTION_NAME = "documents_collection"
EMBEDDING_DIM = 1536 # OpenAI text-embedding-ada-002 dimension

# 初始化 Qdrant 客戶端
client = QdrantClient(host=QDRANT_HOST, port=QDRANT_PORT)

# 初始化 OpenAI Embedding 和 ChatOpenAI 模型
# 確保 OPENAI_API_KEY 已在環境變數中設定
try:
    embeddings_model = OpenAIEmbeddings(openai_api_key=os.getenv("OPENAI_API_KEY"))
    chat_llm = ChatOpenAI(temperature=0.7, openai_api_key=os.getenv("OPENAI_API_KEY"))
    logger.info("OpenAI Embedding and ChatOpenAI models initialized.")
except Exception as e:
    logger.error(f"初始化 OpenAI 模型失敗: {e}. 請檢查 OPENAI_API_KEY。", exc_info=True)
    embeddings_model = None
    chat_llm = None

async def initialize_qdrant_collection():
    """確保 Qdrant Collection 存在."""
    try:
        collections = client.get_collections().collections
        if COLLECTION_NAME not in [c.name for c in collections]:
            client.recreate_collection(
                collection_name=COLLECTION_NAME,
                vectors_config=models.VectorParams(size=EMBEDDING_DIM, distance=models.Distance.COSINE),
            )
            logger.info(f"Collection '{COLLECTION_NAME}' created in Qdrant.")
        else:
            logger.info(f"Collection '{COLLECTION_NAME}' already exists.")
    except Exception as e:
        logger.error(f"初始化 Qdrant collection 失敗: {e}", exc_info=True)
        raise

async def get_embedding(text: str) -> List[float]:
    """
    使用 OpenAIEmbeddings 獲取文本 Embedding。
    """
    if embeddings_model is None:
        raise RuntimeError("OpenAI Embedding 模型未載入，請檢查配置。")
    try:
        # LangChain 的 embed_query 可能是同步的，在非同步環境中需要用 run_in_executor
        # 為了簡潔，這裡直接呼叫，假設它足夠快或在 async context 中被包裝
        embedding = embeddings_model.embed_query(text)
        return embedding
    except Exception as e:
        logger.error(f"獲取 Embedding 失敗: {e}", exc_info=True)
        raise RuntimeError(f"Embedding 服務錯誤: {e}")

async def process_document_embedding(document_id: int, file_path: str, metadata: Dict[str, Any]) -> str:
    """
    處理文件：讀取內容，分割，向量化，儲存到 Qdrant。
    並生成文件摘要。
    """
    await initialize_qdrant_collection()
    logger.info(f"開始處理文件 {document_id}，路徑: {file_path}")

    content = ""
    try:
        # 在實際應用中，需要考慮文件類型（PDF, DOCX, TXT）並使用對應的解析器
        # 這裡為了範例，假設 file_path 指向一個純文本文件
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
        logger.info(f"文件 {document_id} 內容讀取成功，長度: {len(content)}.")
    except FileNotFoundError:
        logger.error(f"處理文件 {document_id} 失敗: 文件路徑不存在: {file_path}")
        raise FileNotFoundError(f"文件未找到: {file_path}")
    except Exception as e:
        logger.error(f"讀取文件 {document_id} 時發生錯誤: {e}", exc_info=True)
        raise RuntimeError(f"讀取文件失敗: {e}")

    # 使用 LangChain 的文本分割器
    text_splitter = RecursiveCharacterTextSplitter(chunk_size=1000, chunk_overlap=200)
    chunks = text_splitter.split_text(content)
    
    if not chunks:
        chunks = [""] # 處理空文件情況

    points = []
    for i, chunk in enumerate(chunks):
        embedding = await get_embedding(chunk)
        points.append(
            models.PointStruct(
                id=f"{document_id}_{i}", # 結合 document_id 和 chunk 索引作為 point ID
                vector=embedding,
                payload={"document_id": document_id, "chunk_index": i, "text": chunk, **metadata}
            )
        )
    
    if points:
        try:
            client.upsert(
                collection_name=COLLECTION_NAME,
                points=points,
                wait=True # 等待操作完成
            )
            logger.info(f"文件 {document_id} 的 {len(points)} 個塊已儲存到 Qdrant。")
        except Exception as e:
            logger.error(f"儲存文件 {document_id} 到 Qdrant 失敗: {e}", exc_info=True)
            raise RuntimeError(f"儲存到向量資料庫失敗: {e}")

    # 生成摘要
    summary = await summarize_document(document_id, content)
    return summary

async def search_documents_qdrant(query: str, limit: int = 5) -> List[Dict[str, Any]]:
    """
    在 Qdrant 中進行語意搜尋。
    """
    await initialize_qdrant_collection() # 確保 collection 存在
    logger.info(f"在 Qdrant 中搜尋: query='{query}'")

    query_embedding = await get_embedding(query)
    try:
        search_result = client.search(
            collection_name=COLLECTION_NAME,
            query_vector=query_embedding,
            limit=limit,
            with_payload=True
        )
        results = []
        for hit in search_result:
            results.append({
                "score": hit.score,
                "document_id": hit.payload.get("document_id"),
                "text_chunk": hit.payload.get("text"),
                "metadata": {k: v for k, v in hit.payload.items() if k not in ["document_id", "chunk_index", "text"]}
            })
        logger.info(f"Qdrant 搜尋完成，找到 {len(results)} 個結果。")
        return results
    except Exception as e:
        logger.error(f"Qdrant 搜尋失敗: {e}", exc_info=True)
        raise RuntimeError(f"向量資料庫搜尋錯誤: {e}")

async def summarize_document(document_id: int, text_content: str) -> str:
    """
    對文本內容進行摘要，實際會呼叫 LLM。
    """
    logger.info(f"開始為文件 {document_id} 生成摘要...")
    if chat_llm is None:
        raise RuntimeError("OpenAI Chat LLM 模型未載入，無法執行摘要。")
    try:
        # 限制輸入長度，避免超出 LLM token 限制
        # 注意：實際應基於 tokenizer 估計長度
        max_input_length = 4000 
        truncated_content = text_content[:max_input_length] + ("..." if len(text_content) > max_input_length else "")

        prompt = f"請簡潔、清晰地總結以下文件內容：\n\n{truncated_content}"
        # chat_llm.ainvoke 是非同步呼叫
        summary_response = await chat_llm.ainvoke(prompt)
        summary = summary_response.content
        logger.info(f"文件 {document_id} 摘要生成完成。")
        return summary
    except Exception as e:
        logger.error(f"文件 {document_id} 摘要失敗 (LLM 錯誤): {e}", exc_info=True)
        return "無法生成摘要。"
