/**
 * k6 HTTP Load Test
 *
 * Tests the application's HTTP endpoints under load.
 *
 * Install k6:
 *   brew install k6
 *
 * Run test:
 *   k6 run tests/k6/http-load.js
 *
 * Run with more VUs:
 *   k6 run --vus 50 --duration 60s tests/k6/http-load.js
 */

import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Trend } from 'k6/metrics';

// Custom metrics
const errorRate = new Rate('errors');
const loginDuration = new Trend('login_duration');
const dashboardDuration = new Trend('dashboard_duration');
const leaderboardDuration = new Trend('leaderboard_duration');

// Test configuration
export const options = {
    stages: [
        { duration: '30s', target: 10 }, // Ramp up to 10 users
        { duration: '1m', target: 10 },  // Stay at 10 users
        { duration: '30s', target: 20 }, // Ramp up to 20 users
        { duration: '1m', target: 20 },  // Stay at 20 users
        { duration: '30s', target: 0 },  // Ramp down
    ],
    thresholds: {
        http_req_duration: ['p(95)<2000'], // 95% of requests should be < 2s
        errors: ['rate<0.1'],              // Error rate should be < 10%
    },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:9090';

// Test users (must exist in database - run `make cy-setup` first)
const TEST_USERS = [
    { email: 'player.a@example.com', password: 'password' },
    { email: 'player.b@example.com', password: 'password' },
    { email: 'player.c@example.com', password: 'password' },
    { email: 'admin@example.com', password: 'password' },
];

export function setup() {
    // Verify the app is reachable
    const res = http.get(`${BASE_URL}/login`);
    if (res.status !== 200) {
        throw new Error(`App not reachable at ${BASE_URL}`);
    }
    console.log(`Testing against: ${BASE_URL}`);
}

export default function () {
    const user = TEST_USERS[Math.floor(Math.random() * TEST_USERS.length)];

    // Test 1: Home/Login page (unauthenticated)
    testPublicPages();

    // Test 2: Login flow
    const authCookies = testLogin(user);
    if (!authCookies) {
        errorRate.add(1);
        return;
    }

    // Test 3: Authenticated pages
    testAuthenticatedPages(authCookies);

    // Test 4: API endpoints
    testApiEndpoints(authCookies);

    sleep(1);
}

function testPublicPages() {
    const responses = http.batch([
        ['GET', `${BASE_URL}/`, null, { tags: { name: 'home' } }],
        ['GET', `${BASE_URL}/login`, null, { tags: { name: 'login_page' } }],
        ['GET', `${BASE_URL}/register`, null, { tags: { name: 'register_page' } }],
    ]);

    responses.forEach((res) => {
        const success = check(res, {
            'public page status is 200': (r) => r.status === 200,
        });
        errorRate.add(!success);
    });
}

function testLogin(user) {
    // Get CSRF token from login page
    const loginPage = http.get(`${BASE_URL}/login`);
    const csrfMatch = loginPage.body.match(/name="_token"\s+value="([^"]+)"/);
    if (!csrfMatch) {
        console.error('Could not find CSRF token');
        return null;
    }
    const csrfToken = csrfMatch[1];

    // Perform login
    const startTime = Date.now();
    const loginRes = http.post(
        `${BASE_URL}/login`,
        {
            _token: csrfToken,
            email: user.email,
            password: user.password,
        },
        {
            redirects: 0,
            tags: { name: 'login_submit' },
        }
    );
    loginDuration.add(Date.now() - startTime);

    const loginSuccess = check(loginRes, {
        'login redirects': (r) => r.status === 302,
        'login redirects to dashboard': (r) =>
            r.headers['Location'] && r.headers['Location'].includes('dashboard'),
    });

    if (!loginSuccess) {
        errorRate.add(1);
        return null;
    }

    errorRate.add(0);
    return loginRes.cookies;
}

function testAuthenticatedPages(cookies) {
    const jar = http.cookieJar();
    Object.entries(cookies).forEach(([name, values]) => {
        values.forEach((cookie) => {
            jar.set(BASE_URL, name, cookie.value);
        });
    });

    // Dashboard
    const startDashboard = Date.now();
    const dashboardRes = http.get(`${BASE_URL}/dashboard`, {
        tags: { name: 'dashboard' },
    });
    dashboardDuration.add(Date.now() - startDashboard);

    check(dashboardRes, {
        'dashboard status is 200': (r) => r.status === 200,
    });

    // Leaderboard
    const startLeaderboard = Date.now();
    const leaderboardRes = http.get(`${BASE_URL}/leaderboard`, {
        tags: { name: 'leaderboard' },
    });
    leaderboardDuration.add(Date.now() - startLeaderboard);

    check(leaderboardRes, {
        'leaderboard status is 200': (r) => r.status === 200,
    });

    // Match history
    const historyRes = http.get(`${BASE_URL}/my-matches`, {
        tags: { name: 'match_history' },
    });
    check(historyRes, {
        'match history status is 200': (r) => r.status === 200,
    });

    // Match creation page
    const createRes = http.get(`${BASE_URL}/match/create`, {
        tags: { name: 'match_create' },
    });
    check(createRes, {
        'match create status is 200': (r) => r.status === 200,
    });
}

function testApiEndpoints(cookies) {
    const jar = http.cookieJar();
    Object.entries(cookies).forEach(([name, values]) => {
        values.forEach((cookie) => {
            jar.set(BASE_URL, name, cookie.value);
        });
    });

    // Test leaderboard API (if exists)
    const leaderboardApi = http.get(`${BASE_URL}/api/leaderboard`, {
        tags: { name: 'api_leaderboard' },
    });

    // 200 or 404 (if API doesn't exist) are acceptable
    check(leaderboardApi, {
        'leaderboard API responds': (r) => r.status === 200 || r.status === 404,
    });
}

export function teardown(data) {
    console.log('Load test completed');
}
