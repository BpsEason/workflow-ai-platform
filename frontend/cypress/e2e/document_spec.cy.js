describe('Document Module', () => {
  let authToken; // To store the token for authenticated requests

  beforeEach(() => {
    cy.clearLocalStorage();
    // Register and login a user programmatically
    const email = `docuser${Cypress._.random(0, 1e6)}@example.com`;
    const password = 'password123';
    cy.request('POST', 'http://localhost:8000/api/register', {
      name: 'Document Test User',
      email: email,
      password: password,
      password_confirmation: password
    }).then((response) => {
      authToken = response.body.token;
      localStorage.setItem('authToken', authToken); // Set token for frontend to use
    });
    cy.visit('/documents'); // Go to the documents page
    cy.contains('h1', '文件智慧管理系統').should('be.visible');
  });

  it('allows a user to upload a document and displays success message', () => {
    // Intercept the AI Orchestrator call
    cy.intercept('POST', 'http://localhost:8001/documents/upload', {
      statusCode: 200,
      body: {
        document_id: 1,
        summary: '這是一份模擬的年度報告摘要。',
        status: 'processed'
      }
    }).as('aiProcessDocument');

    // Intercept the backend upload call (if needed, but frontend talks to backend first)
    cy.intercept('POST', 'http://localhost:8000/api/documents/upload', {
      statusCode: 201,
      body: {
        message: '文件已上傳並發送至AI處理',
        document: {
          id: 1,
          name: 'annual_report.pdf',
          file_path: 'path/to/file',
          summary: null,
          status: 'pending_ai',
          category: 'Report'
        },
        ai_response: {
          document_id: 1,
          summary: '這是一份模擬的年度報告摘要。',
          status: 'processed'
        }
      }
    }).as('backendUploadDocument');

    const fileName = 'annual_report.pdf';
    cy.fixture('test_pdf.pdf', 'binary').then(fileContent => {
      cy.get('input[type="file"]').selectFile({
        contents: Cypress.Buffer.from(fileContent),
        fileName: fileName,
        mimeType: 'application/pdf',
        lastModified: Date.now(),
      });
    });

    cy.get('#category').type('年度報告');
    cy.contains('button', '開始上傳').click();

    cy.contains('.text-green-600', '文件上傳成功！AI 正在處理中。').should('be.visible');
    cy.wait('@backendUploadDocument'); // Wait for backend call
    cy.wait('@aiProcessDocument'); // Wait for AI call
  });

  it('allows a user to search for documents and displays results', () => {
    const searchQuery = '最新的市場趨勢';
    const mockResults = [
      { score: 0.95, document_id: 101, text_chunk: '市場趨勢分析顯示，雲計算需求持續增長。', metadata: { source: '報告A' } },
      { score: 0.88, document_id: 102, text_chunk: '關於AI技術在市場中的應用前景展望。', metadata: { source: '論文B' } }
    ];

    cy.intercept('GET', `http://localhost:8000/api/documents/search?q=${encodeURIComponent(searchQuery)}`, {
      statusCode: 200,
      body: {
        query: searchQuery,
        results: mockResults
      }
    }).as('searchDocuments');

    cy.get('input[placeholder="輸入您想搜尋的內容..."]').type(searchQuery);
    cy.contains('button', '搜尋').click();

    cy.wait('@searchDocuments');

    cy.contains('h4', '搜尋結果:').should('be.visible');
    cy.contains('文件 ID: 101').should('be.visible');
    cy.contains('市場趨勢分析顯示，雲計算需求持續增長。').should('be.visible');
    cy.contains('文件 ID: 102').should('be.visible');
    cy.contains('關於AI技術在市場中的應用前景展望。').should('be.visible');
  });

  it('displays "沒有找到符合條件的結果" when search yields no results', () => {
    const searchQuery = '不存在的文檔';
    cy.intercept('GET', `http://localhost:8000/api/documents/search?q=${encodeURIComponent(searchQuery)}`, {
      statusCode: 200,
      body: {
        query: searchQuery,
        results: []
      }
    }).as('searchNoResults');

    cy.get('input[placeholder="輸入您想搜尋的內容..."]').type(searchQuery);
    cy.contains('button', '搜尋').click();

    cy.wait('@searchNoResults');
    cy.contains('沒有找到符合條件的結果。').should('be.visible');
    cy.contains('搜尋結果:').should('not.exist');
  });
});
