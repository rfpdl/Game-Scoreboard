describe('Admin Panel', () => {
    describe('Access Control', () => {
        it('redirects non-admin users away from admin panel', () => {
            cy.login({ is_admin: false });
            cy.visit('/admin');
            cy.url().should('not.include', '/admin');
        });

        it('allows admin users to access admin panel', () => {
            cy.login({ is_admin: true });
            cy.visit('/admin');
            cy.url().should('include', '/admin');
            cy.contains('Dashboard');
        });

        it('redirects unauthenticated users to login', () => {
            cy.visit('/admin');
            cy.url().should('include', '/login');
        });
    });

    describe('Admin Dashboard', () => {
        beforeEach(() => {
            cy.login({ is_admin: true });
            cy.visit('/admin');
        });

        it('displays dashboard with stats cards', () => {
            cy.contains('Dashboard');
            cy.contains('Total Users');
            cy.contains('Total Games');
            cy.contains('Active Games');
            cy.contains('Total Matches');
        });

        it('displays quick actions section', () => {
            cy.contains('Quick Actions');
            cy.contains('Manage Settings');
            cy.contains('Add New Game');
            cy.contains('Manage Users');
        });

        it('navigates to users page when clicking Total Users card', () => {
            cy.contains('Total Users').parent().parent().click();
            cy.url().should('include', '/admin/users');
        });

        it('navigates to games page when clicking Total Games card', () => {
            cy.contains('Total Games').parent().parent().click();
            cy.url().should('include', '/admin/games');
        });

        it('navigates to matches page when clicking Total Matches card', () => {
            cy.contains('Total Matches').parent().parent().click();
            cy.url().should('include', '/admin/matches');
        });

        it('navigates to settings via quick action', () => {
            cy.contains('Manage Settings').click();
            cy.url().should('include', '/admin/settings');
        });

        it('navigates to create game via quick action', () => {
            cy.contains('Add New Game').click();
            cy.url().should('include', '/admin/games/create');
        });
    });

    describe('Admin Navigation', () => {
        beforeEach(() => {
            cy.login({ is_admin: true });
            cy.visit('/admin');
        });

        it('shows all navigation items in header', () => {
            cy.get('nav').within(() => {
                cy.contains('Dashboard');
                cy.contains('Settings');
                cy.contains('Games');
                cy.contains('Matches');
                cy.contains('Users');
                cy.contains('Backups');
            });
        });

        it('highlights active navigation item', () => {
            cy.get('nav').contains('Dashboard').should('have.class', 'bg-gray-900');
        });

        it('can navigate to settings page', () => {
            cy.get('nav').contains('Settings').click();
            cy.url().should('include', '/admin/settings');
            cy.contains('h1', 'Settings');
        });

        it('can navigate to games page', () => {
            cy.get('nav').contains('Games').click();
            cy.url().should('include', '/admin/games');
            cy.contains('h1', 'Games');
        });

        it('can navigate to matches page', () => {
            cy.get('nav').contains('Matches').click();
            cy.url().should('include', '/admin/matches');
            cy.contains('h1', 'Matches');
        });

        it('can navigate to users page', () => {
            cy.get('nav').contains('Users').click();
            cy.url().should('include', '/admin/users');
            cy.contains('h1', 'Users');
        });

        it('can navigate back to main app', () => {
            cy.contains('Back to App').click();
            cy.url().should('include', '/dashboard');
        });
    });

    describe('Admin Settings', () => {
        beforeEach(() => {
            cy.login({ is_admin: true });
            cy.visit('/admin/settings');
        });

        it('displays settings page with all sections', () => {
            cy.contains('h1', 'Settings');
            cy.contains('App Name');
            cy.contains('Primary Color');
            cy.contains('Logo');
            cy.contains('Registration');
            cy.contains('Default Color Mode');
        });

        it('shows current app name in input', () => {
            cy.get('input#appName').should('have.value').and('not.be.empty');
        });

        it('shows color picker for primary color', () => {
            cy.get('input#primaryColor').should('exist');
            cy.get('input[type="color"]').should('exist');
        });

        it('can update app name', () => {
            const newName = 'Cypress Test App ' + Date.now();
            cy.get('input#appName').clear().type(newName);
            cy.contains('button', 'Save Settings').click();
            cy.contains('Settings saved').should('be.visible');
        });

        it('shows registration toggle', () => {
            cy.contains('Registration').parent().parent().within(() => {
                cy.get('button[role="switch"]').should('exist');
            });
        });

        it('shows color mode dropdown', () => {
            cy.get('select#colorMode').should('exist');
            cy.get('select#colorMode').within(() => {
                cy.get('option').should('have.length', 3);
            });
        });
    });

    describe('Admin Users', () => {
        beforeEach(() => {
            cy.login({ is_admin: true });
            cy.visit('/admin/users');
        });

        it('displays users page with table', () => {
            cy.contains('h1', 'Users');
            cy.get('table').should('exist');
        });

        it('shows user table headers', () => {
            cy.get('table thead').within(() => {
                cy.contains('User');
                cy.contains('Email');
                cy.contains('Stats');
                cy.contains('Status');
                cy.contains('Actions');
            });
        });

        it('displays Add User button', () => {
            cy.contains('button', 'Add User').should('be.visible');
        });

        it('opens create user modal', () => {
            cy.contains('button', 'Add User').click();
            cy.contains('Create New User').should('be.visible');
            cy.get('input#create-name').should('exist');
            cy.get('input#create-email').should('exist');
            cy.get('input#create-password').should('exist');
        });

        it('can close create user modal', () => {
            cy.contains('button', 'Add User').click();
            cy.contains('Create New User').should('be.visible');
            cy.contains('button', 'Cancel').click();
            cy.contains('Create New User').should('not.exist');
        });

        it('validates create user form', () => {
            cy.contains('button', 'Add User').click();
            cy.contains('button', 'Create User').click();
            cy.contains('name field is required');
        });

        it('can create a new user', () => {
            const email = `test${Date.now()}@example.com`;
            cy.contains('button', 'Add User').click();
            cy.get('input#create-name').type('Test User');
            cy.get('input#create-email').type(email);
            cy.get('input#create-password').type('password123');
            cy.get('input#create-password_confirmation').type('password123');
            cy.contains('button', 'Create User').click();
            cy.contains('User created successfully').should('be.visible');
        });

        it('shows admin badge for admin users', () => {
            cy.get('table tbody tr').first().within(() => {
                cy.contains('Admin').should('exist');
            });
        });
    });

    describe('Admin Games', () => {
        beforeEach(() => {
            cy.login({ is_admin: true });
            cy.visit('/admin/games');
        });

        it('displays games page with table', () => {
            cy.contains('h1', 'Games');
            cy.get('table').should('exist');
        });

        it('shows game table headers', () => {
            cy.get('table thead').within(() => {
                cy.contains('Game');
                cy.contains('Players');
                cy.contains('Status');
                cy.contains('Actions');
            });
        });

        it('displays Add Game button', () => {
            cy.contains('Add Game').should('be.visible');
        });

        it('can navigate to create game page', () => {
            cy.contains('Add Game').click();
            cy.url().should('include', '/admin/games/create');
            cy.contains('Create New Game');
        });

        it('shows game status badges', () => {
            cy.get('table tbody tr').first().within(() => {
                cy.get('span').contains(/Active|Inactive/).should('exist');
            });
        });

        it('shows edit and delete actions for each game', () => {
            cy.get('table tbody tr').first().within(() => {
                cy.contains('Edit').should('exist');
            });
        });
    });

    describe('Admin Games - Create', () => {
        beforeEach(() => {
            cy.login({ is_admin: true });
            cy.visit('/admin/games/create');
        });

        it('displays create game form', () => {
            cy.contains('Create New Game');
            cy.get('input#name').should('exist');
            cy.get('input#slug').should('exist');
            cy.get('input#icon').should('exist');
        });

        it('auto-generates slug from name', () => {
            cy.get('input#name').type('Test Game');
            cy.get('input#slug').should('have.value', 'test-game');
        });

        it('validates required fields', () => {
            cy.contains('button', 'Create Game').click();
            cy.contains('name field is required');
        });

        it('can create a new game', () => {
            const gameName = 'Cypress Game ' + Date.now();
            cy.get('input#name').type(gameName);
            cy.get('input#icon').type('🎮');
            cy.contains('button', 'Create Game').click();
            cy.url().should('include', '/admin/games');
            cy.contains(gameName).should('exist');
        });
    });

    describe('Admin Matches', () => {
        beforeEach(() => {
            cy.login({ is_admin: true });
            cy.visit('/admin/matches');
        });

        it('displays matches page', () => {
            cy.contains('h1', 'Matches');
        });

        it('shows all filter dropdowns', () => {
            cy.contains('label', 'Status');
            cy.contains('label', 'Game');
            cy.contains('label', 'Player');
            cy.contains('label', 'From Date');
            cy.contains('label', 'To Date');
        });

        it('has status filter with all options', () => {
            cy.get('select').first().within(() => {
                cy.contains('All Statuses');
                cy.contains('Pending');
                cy.contains('In Progress');
                cy.contains('Completed');
                cy.contains('Cancelled');
            });
        });

        it('can filter by status', () => {
            cy.get('select').first().select('completed');
            cy.url().should('include', 'status=completed');
        });

        it('can filter by date range', () => {
            const today = new Date().toISOString().split('T')[0];
            cy.get('input[type="date"]').first().type(today);
            cy.url().should('include', 'date_from=' + today);
        });

        it('shows Clear Filters button when filters are active', () => {
            cy.get('select').first().select('completed');
            cy.contains('button', 'Clear Filters').should('be.visible');
        });

        it('can clear all filters', () => {
            cy.get('select').first().select('completed');
            cy.contains('button', 'Clear Filters').click();
            cy.url().should('not.include', 'status=');
        });

        it('shows match table headers', () => {
            cy.get('table thead').within(() => {
                cy.contains('Match');
                cy.contains('Players');
                cy.contains('Status');
                cy.contains('Result');
                cy.contains('Date');
                cy.contains('Actions');
            });
        });

        it('displays empty state when no matches', () => {
            // Filter to get no results
            cy.get('select').first().select('cancelled');
            cy.get('input[type="date"]').first().type('2020-01-01');
            cy.get('input[type="date"]').last().type('2020-01-02');
            cy.contains('No matches found').should('be.visible');
        });
    });

    describe('Admin Backups', () => {
        beforeEach(() => {
            cy.login({ is_admin: true });
            cy.visit('/admin/backups');
        });

        it('displays backups page', () => {
            cy.contains('h1', 'Backups');
        });

        it('shows create backup button', () => {
            cy.contains('button', 'Create Backup').should('be.visible');
        });
    });
});
