describe('Authentication Flow', () => {
  beforeEach(() => {
    // Clear localStorage before each test to ensure a clean state
    cy.clearLocalStorage();
    // Visit the login page before each test
    cy.visit('/login');
  });

  it('allows a user to register successfully', () => {
    const randomEmail = `testuser${Cypress._.random(0, 1e6)}@example.com`;
    const password = 'password123';

    cy.get('a[href="/register"]').click(); // Navigate to register page
    cy.url().should('include', '/register');

    cy.get('#name').type('Test User');
    cy.get('#email-address').type(randomEmail);
    cy.get('#password').type(password);
    cy.get('#password-confirm').type(password);
    cy.get('button[type="submit"]').click();

    // Assert redirection to documents page after successful registration
    cy.url().should('include', '/documents');
    cy.contains('h1', '文件智慧管理系統').should('be.visible');
    // Verify that the user name is displayed in the navbar
    cy.contains('nav', 'Test User').should('be.visible');
  });

  it('shows error on registration with existing email', () => {
    // First, register a user
    const existingEmail = `existing${Cypress._.random(0, 1e6)}@example.com`;
    const password = 'password123';
    cy.request('POST', 'http://localhost:8000/api/register', {
      name: 'Existing User',
      email: existingEmail,
      password: password,
      password_confirmation: password
    });

    cy.visit('/register'); // Go to register page

    // Try to register with the same email
    cy.get('#name').type('Another User');
    cy.get('#email-address').type(existingEmail);
    cy.get('#password').type(password);
    cy.get('#password-confirm').type(password);
    cy.get('button[type="submit"]').click();

    // Assert that an error message is displayed
    cy.contains('.text-red-600', 'The email has already been taken.').should('be.visible');
    cy.url().should('include', '/register'); // Should stay on register page
  });

  it('allows a user to log in successfully and displays user name', () => {
    const email = `login${Cypress._.random(0, 1e6)}@example.com`;
    const password = 'password123';

    // Register a user first for login
    cy.request('POST', 'http://localhost:8000/api/register', {
      name: 'Login Test',
      email: email,
      password: password,
      password_confirmation: password
    });

    cy.visit('/login'); // Go to login page

    cy.get('#email-address').type(email);
    cy.get('#password').type(password);
    cy.get('button[type="submit"]').click();

    // Assert redirection to documents page after successful login
    cy.url().should('include', '/documents');
    cy.contains('h1', '文件智慧管理系統').should('be.visible');
    // Verify that the user name is displayed in the navbar
    cy.contains('nav', 'Login Test').should('be.visible');
  });

  it('shows error on login with invalid credentials', () => {
    cy.get('#email-address').type('nonexistent@example.com');
    cy.get('#password').type('wrongpassword');
    cy.get('button[type="submit"]').click();

    // Assert that an error message is displayed
    cy.contains('.text-red-600', 'Invalid credentials').should('be.visible');
    cy.url().should('include', '/login'); // Should stay on login page
  });

  it('redirects to login if trying to access protected route when unauthenticated', () => {
    cy.visit('/documents'); // Directly visit a protected route
    cy.url().should('include', '/login');
    cy.contains('h2', '登入您的帳號').should('be.visible');
  });

  it('allows a logged-in user to log out', () => {
    const email = `logout${Cypress._.random(0, 1e6)}@example.com`;
    const password = 'password123';

    // Register and login a user
    cy.request('POST', 'http://localhost:8000/api/register', {
      name: 'Logout User',
      email: email,
      password: password,
      password_confirmation: password
    }).then((response) => {
      const token = response.body.token;
      // Manually set token to localStorage to simulate logged-in state without going through UI login
      localStorage.setItem('authToken', token);
    });

    cy.visit('/documents'); // Go to a protected page, should be logged in

    // Click logout button
    cy.contains('button', '登出').click();

    // Assert redirection to login page after logout
    cy.url().should('include', '/login');
    cy.contains('h2', '登入您的帳號').should('be.visible');
    // Verify that the logout button is no longer visible and login/register buttons are
    cy.contains('button', '登出').should('not.exist');
    cy.contains('button', '登入').should('be.visible');
  });
});
