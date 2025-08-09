import pytest
from unittest.mock import AsyncMock, patch, MagicMock
from ..app.services.document_service import process_document_embedding, search_documents_qdrant, summarize_document, initialize_qdrant_collection
import os

# --- Mock 設置 ---
@pytest.fixture(autouse=True)
def mock_env_vars():
    """為測試設置必要的環境變數."""
    with patch.dict(os.environ, {"OPENAI_API_KEY": "sk-test12345", "QDRANT_HOST": "localhost", "QDRANT_PORT": "6333"}):
        yield

@pytest.fixture
def mock_qdrant_client():
    """模擬 QdrantClient."""
    with patch('ai-orchestrator.app.services.document_service.QdrantClient') as MockClient:
        mock_client_instance = MockClient.return_value
        mock_client_instance.get_collections.return_value.collections = []
        mock_client_instance.recreate_collection = AsyncMock()
        mock_client_instance.upsert = AsyncMock()
        mock_client_instance.search = AsyncMock(return_value=[])
        yield mock_client_instance

@pytest.fixture
def mock_openai_embedding():
    """模擬 OpenAIEmbeddings."""
    with patch('ai-orchestrator.app.services.document_service.OpenAIEmbeddings') as MockEmbeddings:
        mock_embeddings_instance = MockEmbeddings.return_value
        mock_embeddings_instance.embed_query = MagicMock(return_value=[0.1] * 1536) # 固定返回一個向量
        yield mock_embeddings_instance

@pytest.fixture
def mock_chat_llm():
    """模擬 ChatOpenAI."""
    with patch('ai-orchestrator.app.services.document_service.ChatOpenAI') as MockChatLLM:
        mock_llm_instance = MockChatLLM.return_value
        mock_llm_instance.ainvoke = AsyncMock(return_value=MagicMock(content="這是一個模擬的 LLM 回應。"))
        yield mock_llm_instance

# --- 測試案例 ---

@pytest.mark.asyncio
async def test_initialize_qdrant_collection_creates_new(mock_qdrant_client):
    """測試 Qdrant collection 不存在時，會被重新創建."""
    mock_qdrant_client.get_collections.return_value.collections = []
    await initialize_qdrant_collection()
    mock_qdrant_client.recreate_collection.assert_called_once()

@pytest.mark.asyncio
async def test_initialize_qdrant_collection_exists(mock_qdrant_client):
    """測試 Qdrant collection 已存在時，不會被重新創建."""
    mock_qdrant_client.get_collections.return_value.collections = [MagicMock(name="documents_collection")]
    await initialize_qdrant_collection()
    mock_qdrant_client.recreate_collection.assert_not_called()

@pytest.mark.asyncio
async def test_get_embedding_uses_openai(mock_openai_embedding):
    """測試 get_embedding 確實調用 OpenAIEmbeddings."""
    text = "測試文本"
    embedding = await summarize_document(text) # Call directly or via document_service
    # The fixture already sets mock_openai_embedding.embed_query.return_value
    # Asserting that the mock was called
    mock_openai_embedding.embed_query.assert_called_once_with(text)
    assert embedding == [0.1] * 1536 # Expect the mocked return value

@pytest.mark.asyncio
async def test_process_document_embedding_success(tmp_path, mock_qdrant_client, mock_openai_embedding, mock_chat_llm):
    """測試文件處理成功的情境，包含 OpenAI 整合."""
    test_file_content = "這是一份測試文件內容，用於測試文件向量化和摘要功能。這應該會被分割成多個塊。"
    test_file = tmp_path / "test_doc.txt"
    test_file.write_text(test_file_content)

    document_id = 1
    metadata = {"title": "Test Document"}

    summary = await process_document_embedding(document_id, str(test_file), metadata)

    mock_qdrant_client.recreate_collection.assert_called_once() # 第一次運行可能會創建
    mock_openai_embedding.embed_query.assert_called() # 應該為每個 chunk 調用
    mock_qdrant_client.upsert.assert_called_once()
    mock_chat_llm.ainvoke.assert_called_once()
    assert summary == "這是一個模擬的 LLM 回應。"

@pytest.mark.asyncio
async def test_search_documents_qdrant_no_results(mock_qdrant_client, mock_openai_embedding):
    """測試語意搜尋無結果的情境."""
    query = "找不到的內容"
    mock_qdrant_client.search.return_value = [] # 確保沒有返回結果

    results = await search_documents_qdrant(query)

    mock_qdrant_client.search.assert_called_once()
    mock_openai_embedding.embed_query.assert_called_once_with(query) # 搜尋查詢也會被 Embedding
    assert results == []

@pytest.mark.asyncio
async def test_summarize_document_uses_chat_llm(mock_chat_llm):
    """測試 summarize_document 確實調用 ChatOpenAI."""
    doc_id = 2
    content = "這是很長的一段文本，需要被摘要。它包含了很多重要的資訊，但我們只需要其核心要點。"
    
    summary = await summarize_document(doc_id, content)

    mock_chat_llm.ainvoke.assert_called_once()
    assert "請簡潔、清晰地總結以下文件內容" in mock_chat_llm.ainvoke.call_args[0][0] # 檢查 prompt 內容
    assert summary == "這是一個模擬的 LLM 回應。"
