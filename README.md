# Workflow AI Platform

**Workflow AI Platform** 是一個現代化的全端應用程式，旨在透過人工智慧技術簡化文件管理和語音互動工作流程。該平台整合了 Laravel 後端、Vue 3 前端和 FastAPI AI 微服務，並通過 Docker Compose 實現便捷的容器化部署。🚀

本項目提供智能文件處理（上傳、摘要、語意搜尋）和 AI 語音助理功能（語音轉錄、檢索增強生成），適合需要高效管理和分析大量數據的企業或個人。

---

## 功能亮點

- **全端技術棧**  
  - 後端：Laravel (PHP) 提供穩健的 API 和認證系統  
  - 前端：Vue 3 (JavaScript) 打造響應式用戶界面  
  - AI 微服務：FastAPI (Python) 實現高效的 AI 處理  

- **智能文件管理**  
  - 支持多格式文件上傳（PDF、DOCX、TXT）  
  - 使用 OpenAI LLM 自動生成文件摘要  
  - 基於 OpenAI Embedding 和 Qdrant 的語意搜尋  

- **AI 語音助理**  
  - 高效語音轉錄（Faster-Whisper）  
  - 檢索增強生成（RAG）提供上下文相關的回答  
  - 完整記錄用戶與 AI 的對話歷史  

- **安全與認證**  
  - Laravel Sanctum 實現安全的 API Token 認證  
  - 支持跨域資源共享（CORS）  

- **數據持久化**  
  - MySQL 用於應用數據存儲  
  - Qdrant 作為向量數據庫支持語意搜尋  

- **自動化 API 文件**  
  - Laravel Scribe 生成交互式 API 文檔  
  - FastAPI 提供 Swagger UI 文檔  

- **全面測試**  
  - 前端：Vitest（單元測試）、Cypress（E2E 測試）  
  - 後端：PHPUnit（單元測試）  

- **容器化部署**  
  - 使用 Docker Compose 一鍵部署所有服務  

---

## 系統要求

- **操作系統**：Linux、macOS 或 Windows（推薦使用 WSL2 在 Windows 上）  
- **Docker**：Docker Desktop 或 Docker Engine（版本 >= 20.10）  
- **Docker Compose**：版本 >= 2.0  
- **Node.js**：版本 >= 18.x（前端開發和測試）  
- **PHP**：版本 >= 8.1（後端本地開發，僅在不使用 Docker 時需要）  
- **Python**：版本 >= 3.9（AI 微服務本地開發，僅在不使用 Docker 時需要）  
- **硬體**：最低 4GB RAM，推薦 8GB+（用於運行多容器）  
- **其他**：  
  - OpenAI API Key（用於文件摘要和語意搜尋）  
  - 穩定的網絡連接（用於下載 Docker 鏡像和 Whisper 模型）  

---

## 專案結構

```text
workflow-ai-platform/
├── .env.example            # 環境變數範本
├── Caddyfile               # Caddy 反向代理配置
├── README.md               # 專案說明（本文件）
├── docker-compose.yml      # Docker Compose 服務定義
├── backend/                # Laravel 後端
│   ├── app/
│   │   ├── Http/Controllers/  # 控制器：AuthController, DocumentController, VoiceController
│   │   └── Models/            # 模型：User, Document, Voice
│   ├── config/scribe.php      # Laravel Scribe API 文檔配置
│   ├── database/
│   │   ├── migrations/        # 資料庫遷移文件
│   │   └── seeders/           # 假資料填充
│   ├── nginx/                 # Nginx 配置
│   ├── etc/supervisor/        # Supervisor 配置
│   └── routes/api.php         # API 路由定義
├── frontend/               # Vue 3 前端
│   ├── public/                # 靜態資源
│   ├── src/
│   │   ├── assets/            # 圖片、CSS 等資源
│   │   ├── components/        # Vue 組件
│   │   ├── router/            # Vue Router 配置
│   │   ├── stores/            # Pinia 狀態管理
│   │   ├── views/             # 視圖頁面
│   │   └── App.vue, main.js   # 主應用文件
│   ├── cypress/               # Cypress E2E 測試
│   ├── tests/unit/            # Vitest 單元測試
│   ├── package.json           # 前端依賴與腳本
│   └── vite.config.js         # Vite 配置
├── ai-orchestrator/       # FastAPI AI 微服務
│   ├── app/
│   │   ├── services/          # 服務邏輯：document_service, rag_pipeline
│   │   ├── models/            # Pydantic 模型
│   │   └── main.py            # FastAPI 主應用
│   ├── data/                  # Whisper 模型與臨時文件存儲
│   ├── requirements.txt       # Python 依賴
│   └── tests/                 # 測試文件
└── data-volumes/          # MySQL 和 Qdrant 數據持久化目錄
```

---

## 快速開始

### 1. 克隆專案

```bash
git clone <repository-url>
cd workflow-ai-platform
```

### 2. 配置環境變數

```bash
cp .env.example .env
```

編輯 `.env` 文件，設置以下關鍵變數：
- `OPENAI_API_KEY`：您的 OpenAI API Key（必須）
- `WHISPER_MODEL`：Faster-Whisper 模型（預設 `tiny`，可選 `base`, `small`, `medium`, `large-v2`, `large-v3`）
- `AI_ORCHESTRATOR_URL`：AI 微服務地址（預設 `http://ai-orchestrator:8001`）

### 3. 啟動服務

```bash
# 構建並啟動所有 Docker 容器
docker compose build
docker compose up -d
```

### 4. 初始化 Laravel 後端

```bash
# 進入後端容器
docker exec -it workflow-ai-backend bash

# 生成應用密鑰
php artisan key:generate

# 執行資料庫遷移
php artisan migrate

# （可選）填充假數據
php artisan db:seed

# 生成 API 文檔
php artisan scribe:generate

exit
```

### 5. （可選）設置 Caddy 反向代理

若需要統一入口點，可以使用 Caddy：

```bash
# 安裝 Caddy（請參考 https://caddyserver.com/docs/install）
caddy run --config Caddyfile
```

### 6. 訪問應用

- **前端應用**：`http://localhost:5173`
- **Laravel API**：`http://localhost:8000/api`
- **FastAPI Swagger 文檔**：`http://localhost:8001/docs`
- **Laravel Scribe 文檔**：`http://localhost:8000/docs`
- **Caddy 代理（若啟用）**：
  - 前端：`http://localhost:8081`
  - 後端：`http://localhost:8080`
  - AI 微服務：`http://localhost:8082`

---

## API 文檔

### Laravel API（後端）

- **訪問地址**：`http://localhost:8000/docs`
- **生成方式**：運行 `php artisan scribe:generate`
- **功能**：提供認證、文件管理和語音處理的 API 端點，包含範例請求和響應。

### FastAPI（AI 微服務）

- **訪問地址**：`http://localhost:8001/docs`
- **功能**：提供文件向量化、語意搜尋、語音轉錄和 RAG 回應的 API 端點。

---

## 運行測試

### 前端測試

1. **單元測試（Vitest）**

```bash
cd frontend
npm install
npm test
# 或監聽文件變化
npm run test:watch
```

2. **E2E 測試（Cypress）**

為文件上傳測試準備一個假 PDF 文件（例如 `test_pdf.pdf`）並放置在 `frontend/cypress/fixtures/` 目錄下。

```bash
cd frontend
npm install
# 打開 Cypress UI
npm run cypress:open
# 或運行無頭模式
npm run cypress:run
```

### 後端測試

```bash
docker exec -it workflow-ai-backend bash
vendor/bin/phpunit
exit
```

---

## 開發筆記

- **OpenAI API Key**：必須在 `.env` 中設置 `OPENAI_API_KEY`，否則 AI 功能（摘要、語意搜尋）將無法運行。
- **Faster-Whisper 模型**：首次運行時會自動下載模型，存儲於 `ai-orchestrator/data/whisper_models/`。可通過 `.env` 中的 `WHISPER_MODEL` 參數選擇不同模型（更大的模型精度更高，但需要更多資源）。
- **CORS 配置**：`.env` 中的 `SANCTUM_STATEFUL_DOMAINS` 和 `SESSION_DOMAIN` 已預設為 `localhost:5173` 和 `localhost`，確保前端與後端的跨域請求正常。
- **路由保護**：前端的 `/documents` 和 `/voice` 路由受保護，未登錄用戶將被重定向至登錄頁面。
- **日誌**：後端使用 Laravel 的日誌系統（`storage/logs`），AI 微服務使用 Python 的日誌（控制台輸出）。

---

## 故障排除

- **問題**：`OPENAI_API_KEY` 未設置導致 AI 功能失敗  
  **解決方案**：檢查 `.env` 文件，確保已設置有效的 OpenAI API Key。

- **問題**：Docker 容器啟動失敗  
  **解決方案**：
  - 檢查 Docker 是否運行：`docker info`
  - 確保端口未被占用：`8000`, `8001`, `5173`, `3306`, `6333`, `6334`
  - 查看容器日誌：`docker logs <container_name>`

- **問題**：Cypress 測試無法上傳文件  
  **解決方案**：確保 `frontend/cypress/fixtures/test_pdf.pdf` 存在，可使用任意空白 PDF 文件。

- **問題**：語音轉錄失敗  
  **解決方案**：
  - 檢查 `WHISPER_MODEL` 是否設置為支持的模型。
  - 確保 `ai-orchestrator` 容器正常運行：`docker ps`
  - 檢查日誌：`docker logs workflow-ai-ai-orchestrator`

---

## 貢獻指南

我們歡迎任何形式的貢獻！請按照以下步驟參與：

1. Fork 本倉庫並克隆到本地。
2. 創建一個新分支：`git checkout -b feature/your-feature-name`
3. 提交更改：`git commit -m "Add your feature description"`
4. 推送到遠端：`git push origin feature/your-feature-name`
5. 在 GitHub 上提交 Pull Request，並詳細描述您的更改。

請確保：
- 代碼遵循項目現有的編碼規範（PSR-12 for PHP, ESLint for JavaScript）。
- 所有測試通過（Vitest, Cypress, PHPUnit）。
- 更新相關文檔（例如本 README 或 API 文檔）。

---

## 授權協議

本項目採用 [MIT 許可證](LICENSE)。詳情請見 `LICENSE` 文件。

---

## 聯繫我們

有問題或建議？請通過以下方式聯繫：
- **GitHub Issues**：提交問題或功能請求
- **Email**：support@example.com（請替換為實際聯繫方式）

感謝您使用 **Workflow AI Platform**！我們期待您的探索與反饋！ 🚀
