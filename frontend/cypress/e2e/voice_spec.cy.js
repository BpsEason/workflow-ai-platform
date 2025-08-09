describe('Voice Module', () => {
  let authToken; // To store the token for authenticated requests

  beforeEach(() => {
    cy.clearLocalStorage();
    // Register and login a user programmatically
    const email = `voiceuser${Cypress._.random(0, 1e6)}@example.com`;
    const password = 'password123';
    cy.request('POST', 'http://localhost:8000/api/register', {
      name: 'Voice Test User',
      email: email,
      password: password,
      password_confirmation: password
    }).then((response) => {
      authToken = response.body.token;
      localStorage.setItem('authToken', authToken); // Set token for frontend to use
      localStorage.setItem('userId', response.body.user.id); // Also store user ID for voice store mock
    });
    cy.visit('/voice'); // Go to the voice page
    cy.contains('h1', 'AI 語音助理').should('be.visible');

    // Mock the initial fetchConversationHistory call
    cy.intercept('GET', 'http://localhost:8000/api/voice/history/*', {
      statusCode: 200,
      body: [] // Start with empty history
    }).as('fetchHistory');
    cy.wait('@fetchHistory');
  });

  it('simulates voice recording and displays AI response', () => {
    // Mock the backend API calls for voice processing
    cy.intercept('POST', 'http://localhost:8000/api/voice/process', {
      statusCode: 200,
      body: {
        message: '語音處理成功',
        transcribed_text: '這是一個模擬的語音輸入。',
        response_text: '這是來自AI的模擬回應。',
      }
    }).as('processVoice');

    // Stub MediaRecorder to avoid actual microphone access in Cypress
    cy.window().then((win) => {
      win.MediaRecorder = class {
        constructor(stream) {
          this.stream = stream;
          this.ondataavailable = null;
          this.onstop = null;
          this.state = 'inactive';
          this.chunks = [];
        }
        start() {
          this.state = 'recording';
          // Simulate some data
          setTimeout(() => {
            if (this.ondataavailable) {
              const fakeBlob = new Blob(['fake audio data'], { type: 'audio/webm' });
              this.ondataavailable({ data: fakeBlob });
            }
          }, 100);
          cy.stub(this, 'stop').callsFake(() => {
            this.state = 'inactive';
            // Simulate the onstop event with the collected chunks
            if (this.onstop) {
                const finalBlob = new Blob(this.chunks, { type: 'audio/webm' });
                this.onstop(); // Call onstop to trigger the processing in Vue component
            }
            this.stream.getTracks().forEach(track => track.stop());
          }).as('mediaRecorderStop');
        }
        stop() {
          // This will be stubbed by the constructor to control timing
        }
        // Additional stubbing for the stream and tracks to prevent real browser interaction
        get internalStream() {
          return { getTracks: () => [{ stop: cy.stub().as('trackStop') }] };
        }
      };
      // Ensure getUserMedia is also stubbed
      cy.stub(win.navigator.mediaDevices, 'getUserMedia').resolves({
        getTracks: () => [{ stop: cy.stub().as('getUserMediaTrackStop') }]
      });
    });

    // Click the start recording button
    cy.contains('button[aria-label="開始錄音"]').click();
    cy.contains('錄音中...').should('be.visible');

    // Click the stop recording button
    cy.contains('button[aria-label="停止錄音"]').click();

    cy.contains('AI 思考中...').should('be.visible');

    // Wait for the mocked API call to complete
    cy.wait('@processVoice');

    // Assert that the transcribed text and AI response are displayed in history
    cy.contains('對話歷史:').should('be.visible');
    cy.contains('你: 這是一個模擬的語音輸入。').should('be.visible');
    cy.contains('AI 助手: 這是來自AI的模擬回應。').should('be.visible');
    cy.contains('AI 思考中...').should('not.exist');
  });

  it('displays error if microphone access is denied', () => {
    cy.window().then((win) => {
      // Stub getUserMedia to reject with an error
      cy.stub(win.navigator.mediaDevices, 'getUserMedia').rejects(new Error('Permission denied'));
    });

    cy.contains('button[aria-label="開始錄音"]').click();
    cy.contains('.text-red-500', '無法獲取麥克風權限或開始錄音。請檢查瀏覽器設定。').should('be.visible');
  });

  it('displays error if voice processing fails on backend', () => {
    cy.intercept('POST', 'http://localhost:8000/api/voice/process', {
      statusCode: 500,
      body: {
        message: '語音處理服務異常',
        error: 'Backend processing error'
      }
    }).as('processVoiceFailed');

    cy.window().then((win) => {
      // Stub MediaRecorder to simulate successful recording
      win.MediaRecorder = class {
        constructor(stream) {
          this.stream = stream;
          this.ondataavailable = null;
          this.onstop = null;
          this.state = 'inactive';
        }
        start() { this.state = 'recording'; }
        stop() {
          this.state = 'inactive';
          if (this.onstop) {
            this.onstop({ data: new Blob(['fake audio data'], { type: 'audio/webm' }) });
          }
        }
        get stream() { return { getTracks: () => [{ stop: cy.stub() }] }; }
      };
      cy.stub(win.navigator.mediaDevices, 'getUserMedia').resolves({
        getTracks: () => [{ stop: cy.stub() }]
      });
    });

    cy.contains('button[aria-label="開始錄音"]').click();
    cy.contains('button[aria-label="停止錄音"]').click();

    cy.contains('AI 思考中...').should('be.visible');
    cy.wait('@processVoiceFailed');
    cy.contains('.text-red-500', '處理語音輸入失敗: 語音處理服務異常').should('be.visible');
    cy.contains('AI 思考中...').should('not.exist');
  });
});
