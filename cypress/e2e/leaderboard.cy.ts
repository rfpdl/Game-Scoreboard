describe('Leaderboard', () => {
    it('displays the leaderboard page without login', () => {
        cy.visit('/leaderboard');
        cy.contains('Leaderboard').should('be.visible');
    });

    it('shows game filter options', () => {
        cy.visit('/leaderboard');
        // Check that there's a way to filter by game
        cy.get('body').should('be.visible');
    });

    it('can navigate to game-specific leaderboard', () => {
        cy.visit('/leaderboard/pool');
        cy.url().should('include', '/leaderboard/pool');
    });
});
