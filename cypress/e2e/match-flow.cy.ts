/**
 * Multi-user match flow e2e tests.
 *
 * Before running:
 * 1. Ensure the app is running: make up
 * 2. Seed test users: make shell then php artisan db:seed --class=TestUserSeeder
 */
describe('Match Flow', () => {
    // Test users - must be seeded first
    const playerA = { email: 'player.a@example.com', password: 'password' };
    const playerB = { email: 'player.b@example.com', password: 'password' };

    // Shared state between tests
    let matchUrl: string;

    // Helper to login
    const login = (user: { email: string; password: string }) => {
        cy.visit('/login');
        cy.get('#email').type(user.email);
        cy.get('#password').type(user.password);
        cy.contains('button', 'Log in').click();
        cy.url().should('include', '/dashboard');
    };

    describe('Complete match flow - Player wins', () => {
        it('Player A creates a match', () => {
            // Login as Player A
            login(playerA);

            // Navigate to create match
            cy.visit('/match/create');

            // Select a game (first available game card)
            cy.get('[data-testid^="game-card-"]').first().click();

            // Create match
            cy.get('[data-testid="create-match-button"]').click();

            // Should be on match page with share URL
            cy.url().should('match', /\/match\/[a-f0-9-]+$/);

            // Get the current URL for Player B
            cy.url().then((url) => {
                matchUrl = url;
                cy.log('Match URL:', matchUrl);
            });

            // Verify match code is displayed
            cy.get('[data-testid="match-code"]').should('be.visible');

            // Verify share URL is displayed
            cy.get('[data-testid="share-url"]').should('be.visible');

            // Verify status shows waiting
            cy.get('[data-testid="match-status"]').should('contain', 'Waiting');
        });

        it('Player B joins the match', () => {
            // Clear session
            cy.clearCookies();
            cy.clearLocalStorage();

            // Login as Player B
            login(playerB);

            // Visit the match URL
            cy.visit(matchUrl);

            // Should see join button and click it
            cy.get('[data-testid="join-match-button"]').click();

            // Wait for join to complete
            cy.get('[data-testid="match-status"]').should('contain', 'Ready');
        });

        it('Either player can start the match', () => {
            // Start the match (we're still logged in as Player B)
            cy.get('[data-testid="start-match-button"]').click();

            // Status should show in progress
            cy.get('[data-testid="match-status"]').should('contain', 'Progress');
        });

        it('Player B selects a winner', () => {
            // Select first player as winner (Player A)
            cy.get('[data-testid^="select-winner-"]').first().click();

            // Match should show completed
            cy.get('[data-testid="match-status"]').should('contain', 'Complete');
        });
    });

    describe('Match cancellation flow', () => {
        it('Player A creates a match', () => {
            // Login as Player A
            login(playerA);

            // Create a match
            cy.visit('/match/create');
            cy.get('[data-testid^="game-card-"]').first().click();
            cy.get('[data-testid="create-match-button"]').click();

            // Store match URL
            cy.url().then((url) => {
                matchUrl = url;
            });

            // Verify on match page
            cy.get('[data-testid="match-code"]').should('be.visible');
        });

        it('Player A can cancel before opponent joins', () => {
            // Click cancel match
            cy.get('[data-testid="cancel-match-button"]').click();

            // Confirm cancellation
            cy.get('[data-testid="confirm-cancel-button"]').click();

            // Match should show cancelled
            cy.get('[data-testid="match-status"]').should('contain', 'Cancelled');
        });
    });

    describe('Match cancellation with opponent', () => {
        it('Player A creates a match and Player B joins', () => {
            // Login as Player A and create match
            login(playerA);
            cy.visit('/match/create');
            cy.get('[data-testid^="game-card-"]').first().click();
            cy.get('[data-testid="create-match-button"]').click();

            cy.url().then((url) => {
                matchUrl = url;
            });

            // Switch to Player B
            cy.clearCookies();
            cy.clearLocalStorage();
            login(playerB);

            // Join the match
            cy.visit(matchUrl);
            cy.get('[data-testid="join-match-button"]').click();
            cy.get('[data-testid="match-status"]').should('contain', 'Ready');
        });

        it('Host (Player A) can still cancel after opponent joins', () => {
            // Switch back to Player A (the host)
            cy.clearCookies();
            cy.clearLocalStorage();
            login(playerA);

            // Visit the match
            cy.visit(matchUrl);

            // Cancel the match
            cy.get('[data-testid="cancel-match-button"]').click();
            cy.get('[data-testid="confirm-cancel-button"]').click();

            // Match should show cancelled
            cy.get('[data-testid="match-status"]').should('contain', 'Cancelled');
        });
    });
});
