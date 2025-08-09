# Workflow AI Platform

![GitHub](https://img.shields.io/github/license/BpsEason/workflow-ai-platform)  
![GitHub stars](https://img.shields.io/github/stars/BpsEason/workflow-ai-platform)  
![Docker](https://img.shields.io/badge/Docker-Enabled-blue)

**Workflow AI Platform** 是一個現代化的全端應用程式，旨在通過人工智慧技術簡化文件管理和語音互動工作流程。本項目整合了 Laravel (PHP) 後端、Vue 3 (JavaScript) 前端以及 FastAPI (Python) AI 微服務，並使用 Docker Compose 實現一鍵容器化部署。🚀

本平台提供智能文件處理（上傳、摘要、語意搜尋）和 AI 語音助理功能（語音轉錄、檢索增強生成，RAG），適合需要高效管理和分析大量數據的企業或個人。請注意，倉庫僅包含核心代碼，相關依賴需自行安裝。

---

## 功能亮點

- **全端技術棧**  
  - 後端：Laravel 10 提供穩健的 API 和認證系統（使用 Sanctum）  
  - 前端：Vue 3 + Pinia 打造響應式單頁應用（SPA）  
  - AI 微服務：FastAPI 實現高效的文件向量化、語音轉錄和 RAG  

- **智能文件管理**  
  - 支持多格式文件上傳（PDF、DOCX、TXT）  
  - 使用 OpenAI LLM 自動生成文件摘要  
  - 基於 OpenAI Embedding 和 Qdrant 的語意搜尋  

- **AI 語音助理**  
  - 高效語音轉錄（Faster-Whisper）  
  - 檢索增強生成（RAG）提供上下文相關的回答  
  - 完整記錄用戶與 AI 的對話歷史  

- **安全與認證**  
  - Laravel Sanctum 提供 API Token 認證  
  - 支持跨域資源共享（CORS）  

- **數據持久化**  
  - MySQL 用於應用數據存儲  
  - Qdrant 作為向量數據庫支持語意搜尋  

- **自動化 API 文檔**  
  - Laravel Scribe 生成交互式後端 API 文檔  
  - FastAPI 提供 Swagger UI 文檔  

- **全面測試**  
  - 前端：Vitest（單元測試）、Cypress（端到端測試）  
  - 後端：PHPUnit（單元測試）  

- **容器化部署**  
  - 使用 Docker Compose 簡化多服務部署  

---

## 系統要求

- **操作系統**：Linux、macOS 或 Windows（Windows 推薦使用 WSL2）  
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
├── README.md               # 本文件
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

## 安裝與設置

### 1. 克隆倉庫

```bash
git clone https://github.com/BpsEason/workflow-ai-platform.git
cd workflow-ai-platform
```

### 2. 安裝依賴

由於倉庫僅包含核心代碼，您需要自行安裝以下依賴：

#### 後端（Laravel）

進入 `backend/` 目錄並安裝 PHP 依賴：

```bash
cd backend
composer install
```

**必要依賴**（在 `composer.json` 中添加）：
```json
{
    "require": {
        "php": "^8.1",
        "laravel/framework": "^10.0",
        "laravel/sanctum": "^3.2",
        "knuckleswtf/scribe": "^4.0",
        "guzzlehttp/guzzle": "^7.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^10.0"
    }
}
```

運行 `composer update` 以確保依賴正確安裝。

#### 前端（Vue 3）

進入 `frontend/` 目錄並安裝 Node.js 依賴：

```bash
cd frontend
npm install
```

**必要依賴**（在 `package.json` 中添加）：
```json
{
    "dependencies": {
        "vue": "^3.2.0",
        "vue-router": "^4.0.0",
        "pinia": "^2.0.0",
        "axios": "^1.0.0"
    },
    "devDependencies": {
        "vite": "^4.0.0",
        "vitest": "^0.25.0",
        "cypress": "^12.0.0"
    }
}
```

運行 `npm install` 以安裝依賴。

#### AI 微服務（FastAPI）

進入 `ai-orchestrator/` 目錄並安裝 Python 依賴：

```bash
cd ai-orchestrator
pip install -r requirements.txt
```

**必要依賴**（在 `requirements.txt` 中添加）：
```text
fastapi==0.95.0
uvicorn==0.20.0
pydantic==1.10.0
faster-whisper==0.9.0
langchain==0.0.300
openai==0.27.0
qdrant-client==1.3.0
pytest==7.2.0
```

運行 `pip install -r requirements.txt` 以安裝依賴。

### 3. 配置環境變數

```bash
cp .env.example .env
```

編輯 `.env` 文件，設置以下關鍵變數：
- `OPENAI_API_KEY`：您的 OpenAI API Key（必須，可從 [OpenAI 平台](https://platform.openai.com/) 獲取）
- `WHISPER_MODEL`：Faster-Whisper 模型（預設 `tiny`，可選 `base`, `small`, `medium`, `large-v2`, `large-v3`）
- `AI_ORCHESTRATOR_URL`：AI 微服務地址（預設 `http://ai-orchestrator:8001`）
- `SANCTUM_STATEFUL_DOMAINS`：確保設置為 `localhost:5173` 以支持前端跨域請求

### 4. 啟動服務

```bash
# 構建並啟動所有 Docker 容器
docker compose build
docker compose up -d
```

### 5. 初始化 Laravel 後端

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

### 6. （可選）設置 Caddy 反向代理

若需要統一入口點，可以使用 Caddy：

```bash
# 安裝 Caddy（參考 https://caddyserver.com/docs/install）
caddy run --config Caddyfile
```

### 7. 訪問應用

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
npm test
# 或監聽文件變化
npm run test:watch
```

2. **端到端測試（Cypress）**

為文件上傳測試準備一個假 PDF 文件（例如 `test_pdf.pdf`）並放置在 `frontend/cypress/fixtures/` 目錄下。可以使用任意空白 PDF 文件。

```bash
cd frontend
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

- **依賴管理**：由於倉庫僅包含核心代碼，請確保按照上述步驟安裝所有必要依賴。缺少依賴可能導致服務無法正常運行。
- **OpenAI API Key**：必須在 `.env` 中設置 `OPENAI_API_KEY`，否則文件摘要和語意搜尋功能將失敗。
- **Faster-Whisper 模型**：首次運行時，`ai-orchestrator` 會自動下載模型，存儲於 `ai-orchestrator/data/whisper_models/`。更大的模型（如 `large-v3`）提供更高精度，但需要更多計算資源（推薦 GPU 支持）。
- **CORS 配置**：`.env` 中的 `SANCTUM_STATEFUL_DOMAINS` 和 `SESSION_DOMAIN` 已預設為 `localhost:5173` 和 `localhost`，確保前端與後端的跨域請求正常。
- **路由保護**：前端的 `/documents` 和 `/voice` 路由受保護，未登錄用戶將被重定向至登錄頁面。
- **日誌**：
  - 後端：Laravel 日誌存儲於 `backend/storage/logs`
  - AI 微服務：Python 日誌輸出至控制台（可通過 `docker logs workflow-ai-ai-orchestrator` 查看）

---

## 故障排除

- **問題**：`OPENAI_API_KEY` 未設置導致 AI 功能失敗  
  **解決方案**：檢查 `.env` 文件，確保已設置有效的 OpenAI API Key。

- **問題**：依賴安裝失敗  
  **解決方案**：
  - 確保使用正確的 PHP、Node.js 和 Python 版本。
  - 檢查 `composer.json`、`package.json` 和 `requirements.txt` 是否包含所有必要依賴。
  - 運行 `composer install`、`npm install` 或 `pip install -r requirements.txt` 時，確保網絡暢通。

- **問題**：Docker 容器啟動失敗  
  **解決方案**：
  - 確認 Docker 正在運行：`docker info`
  - 檢查端口是否被占用：`8000`, `8001`, `5173`, `3306`, `6333`, `6334`
  - 查看容器日誌：`docker logs <container_name>`

- **問題**：Cypress 測試無法上傳文件  
  **解決方案**：確保 `frontend/cypress/fixtures/test_pdf.pdf` 存在。可以使用以下命令創建空白 PDF：
  ```bash
  touch frontend/cypress/fixtures/test_pdf.pdf
  ```

- **問題**：語音轉錄失敗  
  **解決方案**：
  - 檢查 `.env` 中的 `WHISPER_MODEL` 是否設置為支持的模型（`tiny`, `base`, `small`, `medium`, `large-v2`, `large-v3`）。
  - 確保 `ai-orchestrator` 容器正常運行：`docker ps`
  - 查看日誌：`docker logs workflow-ai-ai-orchestrator`

---

## 貢獻指南

我們歡迎任何形式的貢獻！請按照以下步驟參與：

1. Fork 本倉庫並克隆到本地：
   ```bash
   git clone https://github.com/BpsEason/workflow-ai-platform.git
   ```
2. 創建一個新分支：
   ```bash
   git checkout -b feature/your-feature-name
   ```
3. 提交更改：
   ```bash
   git commit -m "Add your feature description"
   ```
4. 推送到遠端：
   ```bash
   git push origin feature/your-feature-name
   ```
5. 在 GitHub 上提交 Pull Request，詳細描述您的更改。

**貢獻要求**：
- 遵循代碼規範：PHP 使用 PSR-12，JavaScript 使用 ESLint。
- 確保所有測試（Vitest、Cypress、PHPUnit）通過。
- 更新相關文檔（例如本 README 或 API 文檔）。

---


