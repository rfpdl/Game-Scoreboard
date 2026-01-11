// Custom Cypress Commands

declare global {
    namespace Cypress {
        interface Chainable {
            /**
             * Login with email and password
             * @example cy.login('user@example.com', 'password')
             */
            login(email: string, password: string): Chainable<void>;

            /**
             * Logout the current user
             * @example cy.logout()
             */
            logout(): Chainable<void>;

            /**
             * Register a new user
             * @example cy.register('Test User', 'test@example.com', 'password')
             */
            register(name: string, email: string, password: string): Chainable<void>;
        }
    }
}

Cypress.Commands.add('login', (email: string, password: string) => {
    cy.visit('/login');
    cy.get('#email').type(email);
    cy.get('#password').type(password);
    cy.contains('button', 'Log in').click();
    cy.url().should('include', '/dashboard');
});

Cypress.Commands.add('logout', () => {
    // Click the user dropdown and logout
    cy.get('[data-testid="user-menu"]').click();
    cy.contains('Log Out').click();
    cy.url().should('include', '/login');
});

Cypress.Commands.add('register', (name: string, email: string, password: string) => {
    cy.visit('/register');
    cy.get('#name').type(name);
    cy.get('#email').type(email);
    cy.get('#password').type(password);
    cy.get('#password_confirmation').type(password);
    cy.contains('button', 'Register').click();
});

export {};
