/**
 * k6 WebSocket Stress Test
 *
 * Tests WebSocket connections under load using Laravel Reverb.
 *
 * Install k6:
 *   brew install k6
 *
 * Run test:
 *   k6 run tests/k6/websocket-stress.js
 *
 * Run with more VUs:
 *   k6 run --vus 100 --duration 2m tests/k6/websocket-stress.js
 */

import ws from 'k6/ws';
import { check, sleep } from 'k6';
import { Counter, Rate, Trend } from 'k6/metrics';

// Custom metrics
const wsConnections = new Counter('ws_connections');
const wsMessages = new Counter('ws_messages_received');
const wsErrors = new Rate('ws_errors');
const wsConnectTime = new Trend('ws_connect_time');
const wsMessageLatency = new Trend('ws_message_latency');

// Test configuration
export const options = {
    stages: [
        { duration: '30s', target: 25 },  // Ramp up to 25 concurrent connections
        { duration: '1m', target: 25 },   // Stay at 25 connections
        { duration: '30s', target: 50 },  // Ramp up to 50 connections
        { duration: '1m', target: 50 },   // Stay at 50 connections
        { duration: '30s', target: 100 }, // Ramp up to 100 connections
        { duration: '1m', target: 100 },  // Stay at 100 connections
        { duration: '30s', target: 0 },   // Ramp down
    ],
    thresholds: {
        ws_connect_time: ['p(95)<5000'], // 95% of connections should be < 5s
        ws_errors: ['rate<0.1'],         // Error rate should be < 10%
    },
};

// WebSocket server URL (Laravel Reverb default)
const WS_URL = __ENV.WS_URL || 'ws://localhost:8080/app/local-key';

// Match UUIDs to subscribe to (you can create matches and add real UUIDs here)
const MATCH_UUIDS = [
    'test-match-001',
    'test-match-002',
    'test-match-003',
    'test-match-004',
    'test-match-005',
];

export default function () {
    const matchUuid = MATCH_UUIDS[Math.floor(Math.random() * MATCH_UUIDS.length)];
    const channelName = `match.${matchUuid}`;

    const startTime = Date.now();

    const res = ws.connect(WS_URL, {}, function (socket) {
        const connectTime = Date.now() - startTime;
        wsConnectTime.add(connectTime);
        wsConnections.add(1);

        socket.on('open', function () {
            // Send Pusher protocol connection message
            // Reverb uses Pusher protocol
            console.log(`Connected to WebSocket, subscribing to ${channelName}`);

            // Wait for connection established message before subscribing
        });

        socket.on('message', function (message) {
            wsMessages.add(1);
            const receiveTime = Date.now();

            try {
                const data = JSON.parse(message);

                // Handle Pusher protocol messages
                if (data.event === 'pusher:connection_established') {
                    // Subscribe to match channel
                    socket.send(
                        JSON.stringify({
                            event: 'pusher:subscribe',
                            data: {
                                channel: channelName,
                            },
                        })
                    );
                } else if (data.event === 'pusher_internal:subscription_succeeded') {
                    console.log(`Subscribed to ${data.channel}`);
                } else if (data.event === 'match.updated') {
                    // Track match update latency if timestamp is included
                    if (data.data && data.data.timestamp) {
                        const latency = receiveTime - data.data.timestamp;
                        wsMessageLatency.add(latency);
                    }
                    console.log(`Received match update: ${data.data.action}`);
                }
            } catch (e) {
                console.log(`Received non-JSON message: ${message}`);
            }
        });

        socket.on('error', function (e) {
            console.error(`WebSocket error: ${e.error()}`);
            wsErrors.add(1);
        });

        socket.on('close', function () {
            console.log('WebSocket closed');
        });

        // Keep connection open for the test duration
        // Send periodic ping to keep connection alive
        socket.setInterval(function () {
            socket.send(
                JSON.stringify({
                    event: 'pusher:ping',
                    data: {},
                })
            );
        }, 25000); // Ping every 25 seconds

        // Keep the connection open
        sleep(30);

        socket.close();
    });

    const connectionSuccess = check(res, {
        'WebSocket connection successful': (r) => r && r.status === 101,
    });

    if (!connectionSuccess) {
        wsErrors.add(1);
    }
}

export function teardown(data) {
    console.log('WebSocket stress test completed');
}
