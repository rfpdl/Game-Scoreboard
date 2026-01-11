describe('Authentication', () => {
    beforeEach(() => {
        cy.clearCookies();
        cy.clearLocalStorage();
    });

    describe('Login Page', () => {
        it('displays the login page', () => {
            cy.visit('/login');
            cy.contains('Log in').should('be.visible');
            cy.get('#email').should('be.visible');
            cy.get('#password').should('be.visible');
        });

        it('stays on login page with invalid credentials', () => {
            cy.visit('/login');
            cy.get('#email').type('invalid@example.com');
            cy.get('#password').type('wrongpassword');
            cy.contains('button', 'Log in').click();
            // Should stay on login page (not redirect to dashboard)
            cy.url().should('include', '/login');
        });
    });

    describe('Register Page', () => {
        it('displays the register page', () => {
            cy.visit('/register');
            cy.contains('button', 'Register').should('be.visible');
            cy.get('#name').should('be.visible');
            cy.get('#email').should('be.visible');
            cy.get('#password').should('be.visible');
            cy.get('#password_confirmation').should('be.visible');
        });

        it('stays on register page with mismatched passwords', () => {
            cy.visit('/register');
            cy.get('#name').type('Test User');
            cy.get('#email').type(`test${Date.now()}@example.com`);
            cy.get('#password').type('password123');
            cy.get('#password_confirmation').type('different123');
            cy.contains('button', 'Register').click();
            // Should stay on register page (validation failed)
            cy.url().should('include', '/register');
        });
    });
});
