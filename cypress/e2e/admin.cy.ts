/**
 * Admin Panel e2e tests.
 *
 * Before running:
 * 1. Ensure the app is running: make up
 * 2. Seed test users: make cy-setup
 */
describe('Admin Panel', () => {
    const admin = { email: 'admin@example.com', password: 'password' };
    const regularUser = { email: 'player.a@example.com', password: 'password' };

    const loginAsAdmin = () => {
        cy.visit('/login');
        cy.get('#email').type(admin.email);
        cy.get('#password').type(admin.password);
        cy.contains('button', 'Log in').click();
        cy.url().should('include', '/dashboard');
    };

    const loginAsUser = () => {
        cy.visit('/login');
        cy.get('#email').type(regularUser.email);
        cy.get('#password').type(regularUser.password);
        cy.contains('button', 'Log in').click();
        cy.url().should('include', '/dashboard');
    };

    describe('Admin Access Control', () => {
        it('admin can access admin dashboard', () => {
            loginAsAdmin();

            cy.visit('/admin');

            // Should see admin dashboard
            cy.url().should('include', '/admin');
            cy.contains('Dashboard').should('be.visible');
        });

        it('regular user cannot access admin panel', () => {
            loginAsUser();

            cy.visit('/admin', { failOnStatusCode: false });

            // Should be forbidden or redirected
            cy.url().should('not.include', '/admin');
        });

        it('guest cannot access admin panel', () => {
            cy.clearCookies();
            cy.clearLocalStorage();

            cy.visit('/admin', { failOnStatusCode: false });

            // Should redirect to login
            cy.url().should('include', '/login');
        });
    });

    describe('Admin Dashboard', () => {
        beforeEach(() => {
            loginAsAdmin();
            cy.visit('/admin');
        });

        it('displays stats cards', () => {
            // Should show statistics
            cy.contains('Users').should('be.visible');
            cy.contains('Games').should('be.visible');
            cy.contains('Matches').should('be.visible');
        });

        it('has navigation links', () => {
            cy.contains('a', 'Settings').should('be.visible');
            cy.contains('a', 'Games').should('be.visible');
            cy.contains('a', 'Users').should('be.visible');
        });
    });

    describe('Admin Settings', () => {
        beforeEach(() => {
            loginAsAdmin();
            cy.visit('/admin/settings');
        });

        it('displays settings form', () => {
            cy.contains('Settings').should('be.visible');
            cy.get('#appName').should('be.visible');
            cy.get('#primaryColor').should('be.visible');
        });

        it('can update app name', () => {
            const newName = 'Test App ' + Date.now();

            cy.get('#appName').clear().type(newName);
            cy.contains('button', 'Save').click();

            // Should show success message or stay on page
            cy.get('#appName').should('have.value', newName);
        });

        it('validates app name is required', () => {
            cy.get('#appName').clear();
            cy.contains('button', 'Save').click();

            // Should show validation error
            cy.contains('required').should('be.visible');
        });
    });

    describe('Admin Games Management', () => {
        beforeEach(() => {
            loginAsAdmin();
            cy.visit('/admin/games');
        });

        it('displays games list', () => {
            cy.contains('Games').should('be.visible');

            // Should show at least one game (from seeder)
            cy.contains('Pool').should('be.visible');
        });

        it('can navigate to create game form', () => {
            cy.contains('Add Game').click();

            cy.url().should('include', '/admin/games/create');
            cy.get('#name').should('be.visible');
            cy.get('#slug').should('be.visible');
        });

        it('can create a new game', () => {
            cy.contains('Add Game').click();

            const timestamp = Date.now();
            const gameName = 'Test Game ' + timestamp;
            const gameSlug = 'test-game-' + timestamp;

            cy.get('#name').type(gameName);
            cy.get('#slug').clear().type(gameSlug);
            cy.get('#description').type('A test game for e2e testing');
            cy.get('#icon').type('🎮');

            cy.contains('button', 'Create Game').click();

            // Should redirect to games list
            cy.url().should('include', '/admin/games');
            cy.url().should('not.include', '/create');
            cy.contains(gameName).should('be.visible');
        });

        it('validates game name is required', () => {
            cy.contains('Add Game').click();

            cy.get('#slug').type('test-slug');
            cy.contains('button', 'Create Game').click();

            // Should show validation error
            cy.contains('name').should('be.visible');
        });

        it('validates slug is unique', () => {
            cy.contains('Add Game').click();

            // Try to use existing slug
            cy.get('#name').type('Duplicate Game');
            cy.get('#slug').clear().type('pool'); // Already exists

            cy.contains('button', 'Create Game').click();

            // Should show validation error about unique slug
            cy.contains('taken').should('be.visible');
        });

        it('can edit an existing game', () => {
            // Click edit on Pool game
            cy.contains('tr', 'Pool').within(() => {
                cy.contains('Edit').click();
            });

            cy.url().should('include', '/edit');
            cy.get('#name').should('have.value', 'Pool');
        });

    });

    describe('Admin Games - Edit Flow', () => {
        beforeEach(() => {
            loginAsAdmin();
        });

        it('can update a game description', () => {
            cy.visit('/admin/games');

            // Click edit on Pool game
            cy.contains('tr', 'Pool').within(() => {
                cy.contains('Edit').click();
            });

            // Update description
            cy.get('#description')
                .clear()
                .type('Updated description for testing');

            cy.contains('button', 'Save Changes').click();

            // Should redirect back to list
            cy.url().should('include', '/admin/games');
            cy.url().should('not.include', '/edit');
        });
    });

    describe('Admin Users Management', () => {
        beforeEach(() => {
            loginAsAdmin();
            cy.visit('/admin/users');
        });

        it('displays users list', () => {
            cy.contains('Users').should('be.visible');

            // Should show test users
            cy.contains('admin@example.com').should('be.visible');
        });

        it('shows admin badge for admin users', () => {
            cy.contains('tr', 'admin@example.com').within(() => {
                cy.contains('Admin').should('be.visible');
            });
        });

        it('can toggle admin status of a user', () => {
            // Find a non-admin user and make them admin
            cy.contains('tr', 'player.a@example.com').within(() => {
                cy.contains('button', 'Make Admin').click();
            });

            // Confirm in the modal
            cy.contains('button', 'Confirm').click();

            // User should now show admin badge
            cy.contains('tr', 'player.a@example.com').within(() => {
                cy.contains('Admin').should('be.visible');
            });

            // Toggle back to restore state
            cy.contains('tr', 'player.a@example.com').within(() => {
                cy.contains('button', 'Remove Admin').click();
            });

            // Confirm in the modal
            cy.contains('button', 'Confirm').click();
        });

        it('shows You label for current user row', () => {
            // The current admin user should show "You" instead of action buttons
            cy.contains('tr', 'admin@example.com').within(() => {
                cy.contains('You').should('be.visible');
            });
        });
    });

    describe('Admin Navigation', () => {
        beforeEach(() => {
            loginAsAdmin();
        });

        it('can navigate between admin sections', () => {
            cy.visit('/admin');

            // Navigate to Settings
            cy.contains('a', 'Settings').click();
            cy.url().should('include', '/admin/settings');

            // Navigate to Games
            cy.contains('a', 'Games').click();
            cy.url().should('include', '/admin/games');

            // Navigate to Users
            cy.contains('a', 'Users').click();
            cy.url().should('include', '/admin/users');

            // Navigate back to Dashboard
            cy.contains('a', 'Dashboard').click();
            cy.url().should('eq', Cypress.config().baseUrl + '/admin');
        });

        it('can return to main app from admin', () => {
            cy.visit('/admin');

            // Find and click "Back to App" or similar link
            cy.contains('a', 'Back').click();

            // Should be on main app
            cy.url().should('not.include', '/admin');
        });
    });
});
