/**
 * Install Wizard e2e tests.
 *
 * IMPORTANT: These tests require a fresh database with NO admin users.
 * Run: make fresh (without seeding admin users)
 *
 * The install wizard redirects to login if an admin already exists,
 * so these tests need special setup.
 */
describe('Install Wizard', () => {
    // Skip these tests by default since they require special database state
    // To run: Remove the .skip and ensure no admin users exist
    describe.skip('Fresh Installation Flow', () => {
        beforeEach(() => {
            // Clear any existing session
            cy.clearCookies();
            cy.clearLocalStorage();
        });

        it('shows install wizard when no admin exists', () => {
            cy.visit('/install');

            // Should see the install wizard page
            cy.contains('Welcome').should('be.visible');
            cy.contains('Set up your').should('be.visible');
        });

        it('allows setting app name and color', () => {
            cy.visit('/install');

            // Fill in app name
            cy.get('input[name="appName"]').clear().type('My Game Hub');

            // Select a color (click color input or use default)
            cy.get('input[type="color"]').invoke('val', '#ff6600').trigger('input');

            // Submit settings
            cy.contains('button', 'Next').click();

            // Should progress to next step
            cy.contains('Logo').should('be.visible');
        });

        it('allows skipping logo upload', () => {
            cy.visit('/install');

            // Go through app name step
            cy.get('input[name="appName"]').clear().type('Test App');
            cy.contains('button', 'Next').click();

            // Skip logo step
            cy.contains('button', 'Skip').click();

            // Should be on admin creation step
            cy.contains('Create Admin').should('be.visible');
        });

        it('creates first admin user', () => {
            cy.visit('/install');

            // Step 1: App name
            cy.get('input[name="appName"]').clear().type('Test App');
            cy.contains('button', 'Next').click();

            // Step 2: Skip logo
            cy.contains('button', 'Skip').click();

            // Step 3: Create admin
            cy.get('input[name="name"]').type('Admin User');
            cy.get('input[name="email"]').type('admin@example.com');
            cy.get('input[name="password"]').type('password123');
            cy.get('input[name="password_confirmation"]').type('password123');

            cy.contains('button', 'Create Admin').click();

            // Should redirect to login
            cy.url().should('include', '/login');
        });

        it('validates admin creation form', () => {
            cy.visit('/install');

            // Go to admin step
            cy.get('input[name="appName"]').clear().type('Test App');
            cy.contains('button', 'Next').click();
            cy.contains('button', 'Skip').click();

            // Try to submit empty form
            cy.contains('button', 'Create Admin').click();

            // Should show validation errors
            cy.contains('required').should('be.visible');
        });

        it('validates password confirmation', () => {
            cy.visit('/install');

            // Go to admin step
            cy.get('input[name="appName"]').clear().type('Test App');
            cy.contains('button', 'Next').click();
            cy.contains('button', 'Skip').click();

            // Fill form with mismatched passwords
            cy.get('input[name="name"]').type('Admin User');
            cy.get('input[name="email"]').type('admin@example.com');
            cy.get('input[name="password"]').type('password123');
            cy.get('input[name="password_confirmation"]').type('different123');

            cy.contains('button', 'Create Admin').click();

            // Should show password mismatch error
            cy.contains('confirmation').should('be.visible');
        });
    });

    describe('Install Wizard Redirect', () => {
        it('redirects to login when admin already exists', () => {
            // This test works with seeded data (admin exists)
            cy.visit('/install');

            // Should redirect to login since admin exists
            cy.url().should('include', '/login');
        });
    });
});
