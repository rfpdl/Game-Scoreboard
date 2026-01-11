import { test, expect, Page, BrowserContext } from '@playwright/test';

/**
 * WebSocket Real-time Tests using Playwright
 *
 * These tests verify WebSocket connections and real-time updates.
 * Playwright can intercept and monitor WebSocket connections.
 *
 * Before running:
 * 1. Ensure the app is running: make up
 * 2. Ensure Reverb WebSocket server is running
 * 3. Seed test users: make cy-setup
 */

const TEST_USERS = {
    playerA: { email: 'player.a@example.com', password: 'password' },
    playerB: { email: 'player.b@example.com', password: 'password' },
    admin: { email: 'admin@example.com', password: 'password' },
};

async function login(page: Page, user: { email: string; password: string }) {
    await page.goto('/login');
    await page.fill('#email', user.email);
    await page.fill('#password', user.password);
    await page.click('button:has-text("Log in")');
    await page.waitForURL('**/dashboard');
}

async function createMatch(page: Page): Promise<string> {
    await page.goto('/match/create');
    await page.click('[data-testid^="game-card-"]');
    await page.click('[data-testid="create-match-button"]');
    await page.waitForURL(/\/match\/[a-f0-9-]+$/);
    return page.url();
}

test.describe('WebSocket Connection', () => {
    test('establishes WebSocket connection on match page', async ({ page }) => {
        const wsConnections: string[] = [];

        // Monitor WebSocket connections
        page.on('websocket', (ws) => {
            wsConnections.push(ws.url());
            console.log(`WebSocket opened: ${ws.url()}`);

            ws.on('framereceived', (event) => {
                console.log(`WS received: ${event.payload}`);
            });

            ws.on('close', () => {
                console.log(`WebSocket closed: ${ws.url()}`);
            });
        });

        await login(page, TEST_USERS.playerA);
        await createMatch(page);

        // Wait for WebSocket connection
        await page.waitForTimeout(3000);

        // Should have established a WebSocket connection
        expect(wsConnections.length).toBeGreaterThan(0);
        expect(wsConnections.some((url) => url.includes('reverb') || url.includes('socket'))).toBeTruthy();
    });

    test('WebSocket receives match updates', async ({ page }) => {
        const wsMessages: string[] = [];

        page.on('websocket', (ws) => {
            ws.on('framereceived', (event) => {
                if (typeof event.payload === 'string') {
                    wsMessages.push(event.payload);
                }
            });
        });

        await login(page, TEST_USERS.playerA);
        const matchUrl = await createMatch(page);

        // Wait for initial connection
        await page.waitForTimeout(2000);

        // Verify we're subscribed to the match channel
        const subscribeMessage = wsMessages.find(
            (msg) => msg.includes('subscribe') || msg.includes('match.')
        );
        expect(subscribeMessage).toBeDefined();
    });
});

test.describe('Multi-User Real-time Updates', () => {
    test('Player B joining updates Player A view in real-time', async ({ browser }) => {
        // Create two browser contexts (simulating two users)
        const contextA = await browser.newContext();
        const contextB = await browser.newContext();
        const pageA = await contextA.newPage();
        const pageB = await contextB.newPage();

        try {
            // Player A creates a match
            await login(pageA, TEST_USERS.playerA);
            const matchUrl = await createMatch(pageA);

            // Verify Player A sees "Waiting" status
            await expect(pageA.locator('[data-testid="match-status"]')).toContainText('Waiting');

            // Track WebSocket messages on Player A's page
            const wsUpdates: string[] = [];
            pageA.on('websocket', (ws) => {
                ws.on('framereceived', (event) => {
                    if (typeof event.payload === 'string' && event.payload.includes('player_joined')) {
                        wsUpdates.push(event.payload);
                    }
                });
            });

            // Player B logs in and joins the match
            await login(pageB, TEST_USERS.playerB);
            await pageB.goto(matchUrl);
            await pageB.click('[data-testid="join-match-button"]');

            // Wait for WebSocket update
            await pageA.waitForTimeout(2000);

            // Player A's page should update to show "Ready" status
            await expect(pageA.locator('[data-testid="match-status"]')).toContainText('Ready');

            // Verify we received the WebSocket update
            expect(wsUpdates.length).toBeGreaterThan(0);
        } finally {
            await contextA.close();
            await contextB.close();
        }
    });

    test('Match start updates both players in real-time', async ({ browser }) => {
        const contextA = await browser.newContext();
        const contextB = await browser.newContext();
        const pageA = await contextA.newPage();
        const pageB = await contextB.newPage();

        try {
            // Player A creates match
            await login(pageA, TEST_USERS.playerA);
            const matchUrl = await createMatch(pageA);

            // Player B joins
            await login(pageB, TEST_USERS.playerB);
            await pageB.goto(matchUrl);
            await pageB.click('[data-testid="join-match-button"]');

            // Wait for join to complete
            await expect(pageB.locator('[data-testid="match-status"]')).toContainText('Ready');
            await expect(pageA.locator('[data-testid="match-status"]')).toContainText('Ready');

            // Player B starts the match
            await pageB.click('[data-testid="start-match-button"]');

            // Both players should see "In Progress"
            await expect(pageA.locator('[data-testid="match-status"]')).toContainText('Progress');
            await expect(pageB.locator('[data-testid="match-status"]')).toContainText('Progress');
        } finally {
            await contextA.close();
            await contextB.close();
        }
    });

    test('Match completion updates both players', async ({ browser }) => {
        const contextA = await browser.newContext();
        const contextB = await browser.newContext();
        const pageA = await contextA.newPage();
        const pageB = await contextB.newPage();

        try {
            // Setup: Create, join, and start match
            await login(pageA, TEST_USERS.playerA);
            const matchUrl = await createMatch(pageA);

            await login(pageB, TEST_USERS.playerB);
            await pageB.goto(matchUrl);
            await pageB.click('[data-testid="join-match-button"]');
            await expect(pageB.locator('[data-testid="match-status"]')).toContainText('Ready');

            await pageB.click('[data-testid="start-match-button"]');
            await expect(pageB.locator('[data-testid="match-status"]')).toContainText('Progress');

            // Player B selects winner
            await pageB.click('[data-testid^="select-winner-"]');

            // Both players should see "Complete"
            await expect(pageA.locator('[data-testid="match-status"]')).toContainText('Complete');
            await expect(pageB.locator('[data-testid="match-status"]')).toContainText('Complete');
        } finally {
            await contextA.close();
            await contextB.close();
        }
    });

    test('Match cancellation notifies opponent in real-time', async ({ browser }) => {
        const contextA = await browser.newContext();
        const contextB = await browser.newContext();
        const pageA = await contextA.newPage();
        const pageB = await contextB.newPage();

        try {
            // Player A creates match
            await login(pageA, TEST_USERS.playerA);
            const matchUrl = await createMatch(pageA);

            // Player B joins
            await login(pageB, TEST_USERS.playerB);
            await pageB.goto(matchUrl);
            await pageB.click('[data-testid="join-match-button"]');
            await expect(pageB.locator('[data-testid="match-status"]')).toContainText('Ready');

            // Player A (host) cancels the match
            await pageA.click('[data-testid="cancel-match-button"]');
            await pageA.click('[data-testid="confirm-cancel-button"]');

            // Both players should see "Cancelled"
            await expect(pageA.locator('[data-testid="match-status"]')).toContainText('Cancelled');
            await expect(pageB.locator('[data-testid="match-status"]')).toContainText('Cancelled');
        } finally {
            await contextA.close();
            await contextB.close();
        }
    });
});

test.describe('WebSocket Reconnection', () => {
    test('reconnects after temporary network issue', async ({ page, context }) => {
        await login(page, TEST_USERS.playerA);
        const matchUrl = await createMatch(page);

        // Wait for initial WebSocket connection
        await page.waitForTimeout(2000);

        // Simulate offline mode
        await context.setOffline(true);
        await page.waitForTimeout(1000);

        // Go back online
        await context.setOffline(false);
        await page.waitForTimeout(3000);

        // Page should still be functional
        await expect(page.locator('[data-testid="match-status"]')).toBeVisible();
        await expect(page.locator('[data-testid="match-code"]')).toBeVisible();
    });

    test('page remains functional after reconnection', async ({ page, context }) => {
        await login(page, TEST_USERS.playerA);
        await createMatch(page);

        // Simulate network disruption
        await context.setOffline(true);
        await page.waitForTimeout(500);
        await context.setOffline(false);
        await page.waitForTimeout(2000);

        // Cancel button should still work
        await page.click('[data-testid="cancel-match-button"]');
        await expect(page.locator('[data-testid="confirm-cancel-button"]')).toBeVisible();
        await page.click('[data-testid="confirm-cancel-button"]');

        await expect(page.locator('[data-testid="match-status"]')).toContainText('Cancelled');
    });
});
