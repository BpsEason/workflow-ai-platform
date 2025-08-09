from typing import List, Dict, Any
from .document_service import search_documents_qdrant
import os
import logging
from dotenv import load_dotenv

# 確保載入環境變數
load_dotenv()

# LangChain OpenAI 導入
from langchain_openai import ChatOpenAI
from langchain.prompts import ChatPromptTemplate, MessagesPlaceholder
from langchain.chains import create_history_aware_retriever, create_retrieval_chain
from langchain.chains.combine_documents import create_stuff_documents_chain
from langchain_core.messages import HumanMessage, AIMessage
from langchain_community.vectorstores import Qdrant # 用於 LangChain 與 Qdrant 的整合
from langchain_community.embeddings import OpenAIEmbeddings # 用於 LangChain 與 Qdrant 的整合

logger = logging.getLogger(__name__)

# 初始化 OpenAI Chat LLM 和 Embedding 模型 (如果未在 document_service 中初始化)
# 確保 OPENAI_API_KEY 已在環境變數中設定
try:
    chat_llm = ChatOpenAI(temperature=0.7, openai_api_key=os.getenv("OPENAI_API_KEY"))
    embeddings_model_for_retriever = OpenAIEmbeddings(openai_api_key=os.getenv("OPENAI_API_KEY"))
    logger.info("OpenAI Chat LLM and Embedding models for RAG initialized.")
except Exception as e:
    logger.error(f"初始化 OpenAI 模型失敗: {e}. 請檢查 OPENAI_API_KEY。", exc_info=True)
    chat_llm = None
    embeddings_model_for_retriever = None

# Qdrant 配置
QDRANT_HOST = os.getenv("QDRANT_HOST", "qdrant")
QDRANT_PORT = int(os.getenv("QDRANT_PORT", 6333))
COLLECTION_NAME = "documents_collection" # 需與 document_service 中的一致

async def get_qdrant_retriever():
    """獲取 Qdrant 向量儲存的 LangChain Retriever."""
    if embeddings_model_for_retriever is None:
        raise RuntimeError("OpenAI Embedding 模型未載入，無法初始化 Retriever。")
    # 確保 Qdrant 集合已存在 (由 document_service.py 負責)
    vector_store = Qdrant(
        client=document_service.client, # 使用 document_service 中已初始化的 Qdrant 客戶端
        collection_name=COLLECTION_NAME,
        embeddings=embeddings_model_for_retriever,
    )
    return vector_store.as_retriever()


async def generate_response_from_rag(user_id: str, prompt: str, conversation_history: List[Dict[str, str]]) -> str:
    """
    基於 RAG (Retrieval-Augmented Generation) 流程生成回應。
    1. 檢索相關文件塊。
    2. 將檢索到的內容與用戶問題結合，送入 LLM 生成回答。
    """
    logger.info(f"用戶 {user_id} 請求 RAG 回應，問題: '{prompt}'")
    
    if chat_llm is None:
        raise RuntimeError("OpenAI Chat LLM 模型未載入，無法生成 RAG 回應。")

    # 將歷史轉換為 LangChain 訊息格式
    chat_history_messages = []
    for msg in conversation_history:
        if msg['role'] == 'user':
            chat_history_messages.append(HumanMessage(content=msg['content']))
        elif msg['role'] == 'assistant':
            chat_history_messages.append(AIMessage(content=msg['content']))

    # 步驟 1: 歷史感知檢索器 (用於處理多輪對話中的上下文)
    try:
        retriever = await get_qdrant_retriever()
        history_aware_retriever = create_history_aware_retriever(chat_llm, retriever, ChatPromptTemplate.from_messages([
            MessagesPlaceholder("chat_history"),
            ("user", "{input}"),
            ("user", "根據以上對話和我的問題，請總結出一個獨立的問題，以便從文件中檢索相關信息。")
        ]))
    except Exception as e:
        logger.error(f"創建歷史感知檢索器失敗: {e}", exc_info=True)
        return "很抱歉，初始化檢索服務時發生問題。"


    # 步驟 2: 文件組合鏈 (將檢索到的文檔與用戶問題結合)
    document_chain = create_stuff_documents_chain(chat_llm, ChatPromptTemplate.from_messages([
        ("system", "您是一個智慧助手，請根據提供的上下文和對話歷史，簡潔、專業地回答用戶的問題。\n\n上下文:\n{context}"),
        MessagesPlaceholder("chat_history"),
        ("user", "{input}"),
    ]))

    # 步驟 3: RAG 鏈
    retrieval_chain = create_retrieval_chain(history_aware_retriever, document_chain)
    
    try:
        response = await retrieval_chain.ainvoke({
            "chat_history": chat_history_messages,
            "input": prompt
        })
        response_text = response["answer"]
        logger.info(f"RAG 回應生成完成，回應: '{response_text[:50]}...'")
        return response_text
    except Exception as e:
        logger.error(f"RAG 回應生成失敗 (LLM 或檢索錯誤): {e}", exc_info=True)
        return "很抱歉，我無法生成基於內部資料的回應。請嘗試換個問題或稍後再試。"
