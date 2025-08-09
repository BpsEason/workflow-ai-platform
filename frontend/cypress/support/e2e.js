// ***********************************************************
// This example support/e2e.js is processed and
// loaded automatically before your test files.
//
// This is a great place to put global configuration and
// behavior that modifies Cypress.
//
// You can change the location of this file or turn off
// automatically serving support files with the
// 'supportFile' configuration option.
//
// You can read more here:
// https://on.cypress.io/configuration
// ***********************************************************

// Import commands.js using ES2015 syntax:
import './commands'

// Alternatively, you can use CommonJS syntax:
// require('./commands')

// For testing API calls that involve file uploads, you might need to mock them
// Cypress provides `cy.intercept` for this.
// For example, if you want to mock the backend's document upload:
/*
beforeEach(() => {
  cy.intercept('POST', 'http://localhost:8000/api/documents/upload', {
    statusCode: 201,
    body: {
      message: '文件已上傳並發送至AI處理 (Mocked)',
      document: {
        id: 99,
        name: 'mock_document.txt',
        status: 'processed_ai',
        summary: '這是一個模擬的摘要。',
      },
      ai_response: {
        document_id: 99,
        summary: '這是一個模擬的摘要。',
        status: 'processed',
      }
    }
  }).as('uploadDocument');

  cy.intercept('POST', 'http://localhost:8000/api/voice/process', {
    statusCode: 200,
    body: {
      message: '語音處理成功 (Mocked)',
      transcribed_text: '這是模擬的轉錄文本。',
      response_text: '這是模擬的 AI 回應。',
    }
  }).as('processVoice');

  cy.intercept('GET', 'http://localhost:8000/api/documents/search*', {
    statusCode: 200,
    body: {
      query: 'mock query',
      results: [
        { score: 0.99, document_id: 1, text_chunk: 'Mocked search result 1.' },
        { score: 0.95, document_id: 2, text_chunk: 'Mocked search result 2.' }
      ]
    }
  }).as('searchDocuments');

  cy.intercept('POST', 'http://localhost:8000/api/register', {
    statusCode: 201,
    body: {
      message: 'User registered successfully',
      token: 'mock-auth-token-register',
      user: { id: 1, name: 'Test User', email: 'test@example.com' }
    }
  }).as('registerUser');

  cy.intercept('POST', 'http://localhost:8000/api/login', {
    statusCode: 200,
    body: {
      message: 'Login successful',
      token: 'mock-auth-token-login',
      user: { id: 1, name: 'Test User', email: 'test@example.com' }
    }
  }).as('loginUser');

  cy.intercept('POST', 'http://localhost:8000/api/logout', {
    statusCode: 200,
    body: { message: 'Successfully logged out' }
  }).as('logoutUser');

  cy.intercept('GET', 'http://localhost:8000/api/user', {
    statusCode: 200,
    body: { id: 1, name: 'Test User', email: 'test@example.com' }
  }).as('fetchUser');
});
*/
