import pytest
from unittest.mock import AsyncMock, patch, MagicMock
from ..app.services.rag_pipeline import generate_response_from_rag
from ..app.services.document_service import search_documents_qdrant
import os

# --- Mock 設置 ---
@pytest.fixture(autouse=True)
def mock_env_vars():
    """為測試設置必要的環境變數."""
    with patch.dict(os.environ, {"OPENAI_API_KEY": "sk-test12345", "QDRANT_HOST": "localhost", "QDRANT_PORT": "6333"}):
        yield

@pytest.fixture
def mock_openai_embedding_for_retriever():
    """模擬 OpenAIEmbeddings for retriever."""
    with patch('ai-orchestrator.app.services.rag_pipeline.OpenAIEmbeddings') as MockEmbeddings:
        mock_embeddings_instance = MockEmbeddings.return_value
        mock_embeddings_instance.embed_query = MagicMock(return_value=[0.1] * 1536)
        yield mock_embeddings_instance

@pytest.fixture
def mock_chat_llm_for_rag():
    """模擬 ChatOpenAI for RAG."""
    with patch('ai-orchestrator.app.services.rag_pipeline.ChatOpenAI') as MockChatLLM:
        mock_llm_instance = MockChatLLM.return_value
        mock_llm_instance.ainvoke = AsyncMock(return_value=MagicMock(content="這是一個來自 RAG 的模擬 LLM 回應。"))
        yield mock_llm_instance

@pytest.fixture
def mock_qdrant_retriever():
    """模擬 LangChain Qdrant Retriever."""
    with patch('ai-orchestrator.app.services.rag_pipeline.Qdrant') as MockQdrant:
        mock_retriever = MagicMock()
        mock_retriever.as_retriever.return_value = MagicMock(aget_relevant_documents=AsyncMock(return_value=[]))
        MockQdrant.return_value = mock_retriever
        yield mock_retriever.as_retriever.return_value

@pytest.mark.asyncio
async def test_generate_response_from_rag_with_context(mock_search_documents_qdrant, mock_openai_embedding_for_retriever, mock_chat_llm_for_rag):
    """測試 RAG 流程，當檢索到相關上下文時."""
    # 模擬 search_documents_qdrant 返回檢索結果
    mock_search_documents_qdrant.return_value = [
        {"document_id": 1, "score": 0.85, "text_chunk": "蘋果公司成立於1976年，由史蒂夫·賈伯斯、史蒂夫·沃茲尼亞克和羅納德·韋恩創立。"},
        {"document_id": 2, "score": 0.78, "text_chunk": "史蒂夫·賈伯斯也是皮克斯動畫工作室的創辦人之一。"}
    ]

    user_id = "test_user_1"
    prompt = "蘋果公司的創辦人是誰？"
    conversation_history = [
        {"role": "user", "content": "你好"},
        {"role": "assistant", "content": "您好！"}
    ]

    # 因為 create_history_aware_retriever 和 create_retrieval_chain 內部邏輯複雜，
    # 這裡我們主要測試它們的組合行為，並確保 LLM 被調用。
    # 更深層的 LangChain 內部測試通常由 LangChain 庫本身保證。
    with patch('ai-orchestrator.app.services.rag_pipeline.create_retrieval_chain', new=MagicMock()) as mock_retrieval_chain:
        mock_retrieval_chain.return_value.ainvoke = AsyncMock(return_value={"answer": "這是一個來自 RAG 鏈的最終回答。"})

        response = await generate_response_from_rag(user_id, prompt, conversation_history)

        mock_search_documents_qdrant.assert_called_once_with(prompt, limit=3)
        mock_retrieval_chain.return_value.ainvoke.assert_called_once()
        assert response == "這是一個來自 RAG 鏈的最終回答。"

@pytest.mark.asyncio
async def test_generate_response_from_rag_no_context(mock_search_documents_qdrant, mock_openai_embedding_for_retriever, mock_chat_llm_for_rag):
    """測試 RAG 流程，當未能檢索到相關上下文時."""
    mock_search_documents_qdrant.return_value = [] # 模擬沒有檢索到結果

    user_id = "test_user_2"
    prompt = "我能從太空站看到我的房子嗎？"
    conversation_history = []

    response = await generate_response_from_rag(user_id, prompt, conversation_history)

    mock_search_documents_qdrant.assert_called_once_with(prompt, limit=3)
    # 在沒有上下文時，應直接返回預設訊息，不觸發 RAG 鏈的 LLM 調用
    mock_chat_llm_for_rag.ainvoke.assert_not_called() 
    assert "很抱歉，我目前沒有找到相關的內部資料來回答您的問題" in response
