describe('Input Field Styling', () => {
    it('displays visible borders on text inputs', () => {
        cy.visit('/login');

        // Check email input has visible border
        cy.get('#email')
            .should('have.css', 'border-width', '1px')
            .and('have.css', 'border-style', 'solid');

        // Check password input has visible border
        cy.get('#password')
            .should('have.css', 'border-width', '1px')
            .and('have.css', 'border-style', 'solid');
    });

    it('displays styled checkbox inputs', () => {
        cy.visit('/login');

        // Verify checkbox exists and is visible
        cy.get('input[type="checkbox"]')
            .should('exist')
            .and('be.visible');
    });

    it('displays visible borders on registration form inputs', () => {
        cy.visit('/register');

        cy.get('#name')
            .should('have.css', 'border-width', '1px')
            .and('have.css', 'border-style', 'solid');

        cy.get('#email')
            .should('have.css', 'border-width', '1px')
            .and('have.css', 'border-style', 'solid');

        cy.get('#password')
            .should('have.css', 'border-width', '1px')
            .and('have.css', 'border-style', 'solid');

        cy.get('#password_confirmation')
            .should('have.css', 'border-width', '1px')
            .and('have.css', 'border-style', 'solid');
    });
});
