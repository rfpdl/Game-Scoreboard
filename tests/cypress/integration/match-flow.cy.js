describe('Match Flow', () => {
    // Tests use seeded data from the main database
    // The database already has games and an admin user seeded

    describe('Authentication', () => {
        it('redirects unauthenticated users to login', () => {
            cy.visit('/dashboard');
            cy.url().should('include', '/login');
        });

        it('allows authenticated users to access dashboard', () => {
            cy.login();
            cy.visit('/dashboard');
            cy.contains('Dashboard');
        });
    });

    describe('Match Creation', () => {
        beforeEach(() => {
            cy.login();
        });

        it('can access create match page', () => {
            cy.visit('/dashboard');
            cy.contains('Create Match').click();
            cy.contains('Create New Match');
        });

        it('shows match type toggle', () => {
            cy.visit('/matches/create');
            cy.contains('Match Type');
            cy.contains('Quick Match');
            cy.contains('Book a Match');
        });

        it('can create a quick match', () => {
            // Uses seeded 'Pool' game
            cy.visit('/matches/create');
            cy.contains('Pool').click();
            cy.wait(500);
            cy.contains('button', 'Create Match').click();
            cy.wait(2000);
            cy.contains('Share this code with your opponent');
        });

        it('can create a quick match with a name', () => {
            cy.visit('/matches/create');
            cy.contains('Pool').click();
            cy.wait(500);
            cy.get('input[placeholder*="Friday Pool"]').type('Cypress Test Match');
            cy.contains('button', 'Create Match').click();
            cy.wait(2000);
            cy.contains('Cypress Test Match');
        });

        it('shows shareable link on match page', () => {
            cy.visit('/matches/create');
            cy.contains('Pool').click();
            cy.wait(500);
            cy.contains('button', 'Create Match').click();
            cy.wait(2000);
            cy.contains('Or share this link');
        });

        it('shows scheduling fields when Book a Match is selected', () => {
            cy.visit('/matches/create');
            cy.contains('Pool').click();
            cy.wait(500);
            cy.contains('button', 'Book a Match').click();
            cy.wait(500);
            cy.contains('Schedule');
            cy.contains('Date');
            cy.contains('Time');
        });
    });

    describe('Join Match', () => {
        it('can access join match page when logged in', () => {
            cy.login();
            cy.visit('/dashboard');
            cy.contains('Join Match').click();
            cy.contains('Join');
        });
    });

    describe('Leaderboard', () => {
        it('can view leaderboard without login', () => {
            cy.visit('/leaderboard');
            cy.contains('Leaderboard');
        });

        it('can filter leaderboard by game', () => {
            // Uses seeded 'pool' game
            cy.visit('/leaderboard/pool');
            cy.contains('Leaderboard');
        });
    });

    describe('Match Cancellation', () => {
        it('can cancel match when no opponent has joined', () => {
            cy.login();
            cy.visit('/matches/create');
            cy.contains('Pool').click();
            cy.wait(500);
            cy.contains('button', 'Create Match').click();
            cy.wait(2000);
            // Use specific button selector to avoid matching the heading
            cy.get('button').contains('Cancel Match').should('be.visible').click();
            // Wait for redirect to complete
            cy.url({ timeout: 10000 }).should('include', '/dashboard');
        });
    });
});
