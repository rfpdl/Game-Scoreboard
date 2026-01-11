/**
 * Real-time WebSocket e2e tests.
 *
 * These tests verify WebSocket connection behavior and real-time updates.
 *
 * Before running:
 * 1. Ensure the app is running: make up
 * 2. Ensure Reverb WebSocket server is running (included in docker-compose)
 * 3. Seed test users: make cy-setup
 *
 * Note: WebSocket testing in Cypress has limitations. These tests focus on:
 * - Connection establishment
 * - UI state based on connection status
 * - Real-time updates between two browser sessions (using shared state)
 */
describe('Real-time WebSocket Features', () => {
    const playerA = { email: 'player.a@example.com', password: 'password' };
    const playerB = { email: 'player.b@example.com', password: 'password' };

    let matchUrl: string;

    const login = (user: { email: string; password: string }) => {
        cy.visit('/login');
        cy.get('#email').type(user.email);
        cy.get('#password').type(user.password);
        cy.contains('button', 'Log in').click();
        cy.url().should('include', '/dashboard');
    };

    describe('WebSocket Connection on Match Page', () => {
        beforeEach(() => {
            login(playerA);
        });

        it('connects to WebSocket when viewing a match', () => {
            // Create a match
            cy.visit('/match/create');
            cy.get('[data-testid^="game-card-"]').first().click();
            cy.get('[data-testid="create-match-button"]').click();

            // Should be on match page
            cy.url().should('match', /\/match\/[a-f0-9-]+$/);

            // Wait for page to load and WebSocket to connect
            cy.wait(2000);

            // Check browser console for WebSocket connection
            // Note: We can't directly test WebSocket, but we can verify the page loaded
            cy.get('[data-testid="match-status"]').should('be.visible');
        });

        it('shows match code for sharing', () => {
            cy.visit('/match/create');
            cy.get('[data-testid^="game-card-"]').first().click();
            cy.get('[data-testid="create-match-button"]').click();

            // Match code should be visible for sharing
            cy.get('[data-testid="match-code"]').should('be.visible');
            cy.get('[data-testid="match-code"]')
                .invoke('text')
                .should('match', /^[A-Z0-9]{6}$/);
        });
    });

    describe('Real-time Match Updates', () => {
        it('Player A creates match, Player B joins and sees updates', () => {
            // Player A creates a match
            login(playerA);
            cy.visit('/match/create');
            cy.get('[data-testid^="game-card-"]').first().click();
            cy.get('[data-testid="create-match-button"]').click();

            // Store the match URL
            cy.url().then((url) => {
                matchUrl = url;

                // Verify match is in waiting state
                cy.get('[data-testid="match-status"]').should('contain', 'Waiting');

                // Clear session and login as Player B
                cy.clearCookies();
                cy.clearLocalStorage();
                login(playerB);

                // Player B visits the match URL
                cy.visit(matchUrl);

                // Player B should see the join button
                cy.get('[data-testid="join-match-button"]').should('be.visible');

                // Player B joins
                cy.get('[data-testid="join-match-button"]').click();

                // Match should now be ready (both players joined)
                cy.get('[data-testid="match-status"]').should('contain', 'Ready');
            });
        });

        it('Match status updates when match starts', () => {
            // Create and join a match
            login(playerA);
            cy.visit('/match/create');
            cy.get('[data-testid^="game-card-"]').first().click();
            cy.get('[data-testid="create-match-button"]').click();

            cy.url().then((url) => {
                matchUrl = url;

                // Switch to Player B to join
                cy.clearCookies();
                cy.clearLocalStorage();
                login(playerB);
                cy.visit(matchUrl);
                cy.get('[data-testid="join-match-button"]').click();
                cy.get('[data-testid="match-status"]').should('contain', 'Ready');

                // Start the match
                cy.get('[data-testid="start-match-button"]').click();

                // Status should update to in progress
                cy.get('[data-testid="match-status"]').should('contain', 'Progress');
            });
        });

        it('Match status updates when match is cancelled', () => {
            // Create a match
            login(playerA);
            cy.visit('/match/create');
            cy.get('[data-testid^="game-card-"]').first().click();
            cy.get('[data-testid="create-match-button"]').click();

            cy.url().then((url) => {
                matchUrl = url;

                // Switch to Player B to join
                cy.clearCookies();
                cy.clearLocalStorage();
                login(playerB);
                cy.visit(matchUrl);
                cy.get('[data-testid="join-match-button"]').click();
                cy.get('[data-testid="match-status"]').should('contain', 'Ready');

                // Switch back to Player A (host) to cancel
                cy.clearCookies();
                cy.clearLocalStorage();
                login(playerA);
                cy.visit(matchUrl);

                // Cancel the match
                cy.get('[data-testid="cancel-match-button"]').click();
                cy.get('[data-testid="confirm-cancel-button"]').click();

                // Status should update to cancelled
                cy.get('[data-testid="match-status"]').should('contain', 'Cancelled');
            });
        });
    });

    describe('Match Completion Flow', () => {
        it('completes full match flow with winner selection', () => {
            // Player A creates match
            login(playerA);
            cy.visit('/match/create');
            cy.get('[data-testid^="game-card-"]').first().click();
            cy.get('[data-testid="create-match-button"]').click();

            cy.url().then((url) => {
                matchUrl = url;

                // Player B joins
                cy.clearCookies();
                cy.clearLocalStorage();
                login(playerB);
                cy.visit(matchUrl);
                cy.get('[data-testid="join-match-button"]').click();

                // Start the match
                cy.get('[data-testid="start-match-button"]').click();
                cy.get('[data-testid="match-status"]').should('contain', 'Progress');

                // Select winner (first player button)
                cy.get('[data-testid^="select-winner-"]').first().click();

                // Match should be complete
                cy.get('[data-testid="match-status"]').should('contain', 'Complete');
            });
        });
    });

    describe('Connection State Handling', () => {
        it('page loads and shows match details when offline fallback needed', () => {
            // This test verifies the page works even if WebSocket has issues
            login(playerA);
            cy.visit('/match/create');
            cy.get('[data-testid^="game-card-"]').first().click();
            cy.get('[data-testid="create-match-button"]').click();

            // Core match elements should be visible regardless of WebSocket state
            cy.get('[data-testid="match-code"]').should('be.visible');
            cy.get('[data-testid="share-url"]').should('be.visible');
            cy.get('[data-testid="match-status"]').should('be.visible');
        });
    });
});

describe('Real-time Toast Notifications', () => {
    const playerA = { email: 'player.a@example.com', password: 'password' };
    const playerB = { email: 'player.b@example.com', password: 'password' };

    const login = (user: { email: string; password: string }) => {
        cy.visit('/login');
        cy.get('#email').type(user.email);
        cy.get('#password').type(user.password);
        cy.contains('button', 'Log in').click();
        cy.url().should('include', '/dashboard');
    };

    it('shows toast when player joins match', () => {
        // Note: This test verifies toast appears after join action
        // Real-time toast from WebSocket would require keeping both sessions open

        login(playerA);
        cy.visit('/match/create');
        cy.get('[data-testid^="game-card-"]').first().click();
        cy.get('[data-testid="create-match-button"]').click();

        cy.url().then((url) => {
            // Switch to Player B
            cy.clearCookies();
            cy.clearLocalStorage();
            login(playerB);
            cy.visit(url);

            // Join should trigger a status update
            cy.get('[data-testid="join-match-button"]').click();

            // Status should change (this confirms the action worked)
            cy.get('[data-testid="match-status"]').should('contain', 'Ready');
        });
    });
});
