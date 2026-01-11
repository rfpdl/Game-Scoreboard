// Cypress E2E Support File
// This file is processed and loaded automatically before your test files.

import './commands';

// Prevent tests from failing on uncaught exceptions from the app
Cypress.on('uncaught:exception', (err) => {
    // Ignore network errors (e.g., from axios)
    if (err.message.includes('Network Error')) {
        return false;
    }
    // Ignore ResizeObserver errors
    if (err.message.includes('ResizeObserver')) {
        return false;
    }
    // Return true to fail the test on other errors
    return true;
});
