describe('Navigation', () => {
    it('can visit the login page directly', () => {
        cy.visit('/login');
        cy.url().should('include', '/login');
        cy.get('#email').should('be.visible');
    });

    it('can visit the register page directly', () => {
        cy.visit('/register');
        cy.url().should('include', '/register');
        cy.get('#name').should('be.visible');
    });

    it('can visit the leaderboard page', () => {
        cy.visit('/leaderboard');
        cy.contains('Leaderboard').should('be.visible');
    });

    it('has link from login to register', () => {
        cy.visit('/login');
        cy.contains('a', 'Register here').click();
        cy.url().should('include', '/register');
    });

    it('has link from register to login', () => {
        cy.visit('/register');
        cy.contains('a', 'Log in here').click();
        cy.url().should('include', '/login');
    });
});
