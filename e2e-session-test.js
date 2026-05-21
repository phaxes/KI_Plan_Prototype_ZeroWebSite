#!/usr/bin/env node

/**
 * E2E Test: Session Management with Real Firebase
 * Tests multiple users logging in/out and verifies session isolation
 */

require('dotenv').config();
const http = require('http');
const { initializeApp, cert } = require('firebase-admin/app');
const { getAuth } = require('firebase-admin/auth');
const { getFirestore } = require('firebase-admin/firestore');
const { initializeApp: initializeClientApp } = require('firebase/app');
const { getAuth: getClientAuth, signInWithEmailAndPassword, createUserWithEmailAndPassword } = require('firebase/auth');

const serviceAccount = require(process.env.FIREBASE_SERVICE_ACCOUNT_JSON);

// Initialize Firebase Admin SDK
const adminApp = initializeApp({
  credential: cert(serviceAccount),
  databaseURL: `https://${serviceAccount.project_id}.firebaseio.com`
});

const adminAuth = getAuth(adminApp);
const db = getFirestore(adminApp);

// Initialize Firebase Client SDK
const firebaseConfig = {
  apiKey: process.env.FIREBASE_API_KEY,
  authDomain: process.env.FIREBASE_AUTH_DOMAIN,
  projectId: process.env.FIREBASE_PROJECT_ID,
  storageBucket: process.env.FIREBASE_STORAGE_BUCKET,
  messagingSenderId: process.env.FIREBASE_MESSAGING_SENDER_ID,
  appId: process.env.FIREBASE_APP_ID
};

const clientApp = initializeClientApp(firebaseConfig);
const clientAuth = getClientAuth(clientApp);

// Test data
const TEST_USERS = [
  { email: `test1-${Date.now()}@example.com`, password: 'TestPassword123!', name: 'Test User 1' },
  { email: `test2-${Date.now()}@example.com`, password: 'TestPassword123!', name: 'Test User 2' }
];

let testResults = {
  passed: 0,
  failed: 0,
  tests: []
};

// HTTP request helper
function makeRequest(method, path, data = null, cookies = '') {
  return new Promise((resolve, reject) => {
    const options = {
      hostname: 'localhost',
      port: 8000,
      path: path,
      method: method,
      headers: {
        'Content-Type': 'application/json'
      }
    };

    if (cookies) {
      options.headers['Cookie'] = cookies;
    }

    const req = http.request(options, (res) => {
      let body = '';
      res.on('data', chunk => body += chunk);
      res.on('end', () => {
        resolve({
          status: res.statusCode,
          headers: res.headers,
          body: body,
          cookies: res.headers['set-cookie']
        });
      });
    });

    req.on('error', reject);

    if (data) {
      req.write(JSON.stringify(data));
    }
    req.end();
  });
}

function extractSessionCookie(cookies) {
  if (!cookies) return '';
  const sessionCookie = cookies.find(c => c.startsWith('PHPSESSID='));
  if (!sessionCookie) return '';
  return sessionCookie.split(';')[0];
}

async function test(name, fn) {
  try {
    await fn();
    testResults.passed++;
    testResults.tests.push({ name, status: '✓ PASS' });
    console.log(`✓ ${name}`);
  } catch (error) {
    testResults.failed++;
    testResults.tests.push({ name, status: '✗ FAIL', error: error.message });
    console.log(`✗ ${name}: ${error.message}`);
  }
}

async function runTests() {
  console.log('=== E2E Session Management Test ===\n');

  try {
    // Create test users
    console.log('Creating test users in Firebase...');
    const users = [];

    for (const testUser of TEST_USERS) {
      try {
        const userRecord = await adminAuth.createUser({
          email: testUser.email,
          password: testUser.password,
          displayName: testUser.name
        });
        users.push({
          ...testUser,
          uid: userRecord.uid
        });
        console.log(`  Created: ${testUser.email}`);
      } catch (error) {
        if (error.code === 'auth/email-already-exists') {
          console.log(`  Already exists: ${testUser.email}`);
          // Get the user
          const userRecord = await adminAuth.getUserByEmail(testUser.email);
          users.push({
            ...testUser,
            uid: userRecord.uid
          });
        } else {
          throw error;
        }
      }
    }

    console.log('\n=== Running Tests ===\n');

    let user1SessionCookie = '';
    let user2SessionCookie = '';
    let user1IdToken = '';
    let user2IdToken = '';

    // Test 1: User 1 Login
    await test('User 1: Get login page', async () => {
      const response = await makeRequest('GET', '/login');
      if (response.status !== 200) throw new Error(`Expected 200, got ${response.status}`);
    });

    // Test 2: User 1 Firebase sign in and get ID token
    await test('User 1: Firebase authentication', async () => {
      const userCredential = await signInWithEmailAndPassword(clientAuth, users[0].email, users[0].password);
      user1IdToken = await userCredential.user.getIdToken();
      if (!user1IdToken || user1IdToken.length < 100) {
        throw new Error('Failed to get ID token');
      }
    });

    // Test 3: User 1 Verify token and establish session
    await test('User 1: Verify token and establish session', async () => {
      const response = await makeRequest('POST', '/auth/verify', {
        idToken: user1IdToken,
        email: users[0].email,
        uid: users[0].uid,
        displayName: users[0].name
      });

      if (response.status !== 200) {
        throw new Error(`Token verification failed: ${response.status} - ${response.body}`);
      }

      const responseData = JSON.parse(response.body);
      if (!responseData.success) {
        throw new Error(`Verification returned false: ${response.body}`);
      }

      user1SessionCookie = extractSessionCookie(response.cookies);
      if (!user1SessionCookie) {
        throw new Error('No session cookie received');
      }
    });

    // Test 4: User 1 Access profile
    await test('User 1: Access protected profile page', async () => {
      const response = await makeRequest('GET', '/profile', null, user1SessionCookie);
      if (response.status !== 200) {
        throw new Error(`Expected 200, got ${response.status}`);
      }
      if (!response.body.includes(users[0].email)) {
        throw new Error(`Profile doesn't show user email: ${users[0].email}`);
      }
    });

    // Test 5: User 2 Login in different session
    await test('User 2: Create separate session', async () => {
      const response = await makeRequest('GET', '/login');
      if (response.status !== 200) throw new Error(`Expected 200, got ${response.status}`);
      user2SessionCookie = extractSessionCookie(response.cookies);
      if (!user2SessionCookie) {
        throw new Error('No session cookie for User 2');
      }
    });

    // Test 6: Sessions are different
    await test('Sessions: User 1 and User 2 have different session IDs', async () => {
      if (user1SessionCookie === user2SessionCookie) {
        throw new Error('Users share the same session ID');
      }
      console.log(`    User 1 Session: ${user1SessionCookie.substring(0, 20)}...`);
      console.log(`    User 2 Session: ${user2SessionCookie.substring(0, 20)}...`);
    });

    // Test 7: User 2 Firebase sign in
    await test('User 2: Firebase authentication', async () => {
      const userCredential = await signInWithEmailAndPassword(clientAuth, users[1].email, users[1].password);
      user2IdToken = await userCredential.user.getIdToken();
      if (!user2IdToken || user2IdToken.length < 100) {
        throw new Error('Failed to get ID token');
      }
    });

    // Test 8: User 2 Verify token
    await test('User 2: Verify token and establish session', async () => {
      const response = await makeRequest('POST', '/auth/verify', {
        idToken: user2IdToken,
        email: users[1].email,
        uid: users[1].uid,
        displayName: users[1].name
      });

      if (response.status !== 200) {
        throw new Error(`Token verification failed: ${response.status}`);
      }

      const newSessionCookie = extractSessionCookie(response.cookies);
      // After login, user 2 should get a new session ID (regenerated)
      if (newSessionCookie === user2SessionCookie) {
        console.log(`    WARNING: Session ID not regenerated on login`);
      }
      user2SessionCookie = newSessionCookie;
    });

    // Test 9: User 2 Access profile (not User 1)
    await test('User 2: Access profile shows correct user data', async () => {
      const response = await makeRequest('GET', '/profile', null, user2SessionCookie);
      if (response.status !== 200) {
        throw new Error(`Expected 200, got ${response.status}`);
      }
      if (response.body.includes(users[0].email)) {
        throw new Error(`Profile shows User 1's email (${users[0].email}) - session isolation broken!`);
      }
      if (!response.body.includes(users[1].email)) {
        throw new Error(`Profile doesn't show User 2's email`);
      }
    });

    // Test 10: User 1 Logout
    await test('User 1: Logout', async () => {
      const response = await makeRequest('GET', '/logout', null, user1SessionCookie);
      if (response.status !== 302) {
        throw new Error(`Expected redirect (302), got ${response.status}`);
      }
    });

    // Test 11: User 1 Session is cleared after logout
    await test('User 1: Session cleared after logout', async () => {
      const response = await makeRequest('GET', '/profile', null, user1SessionCookie);
      // Should redirect to /login (not 200)
      if (response.status === 200) {
        throw new Error('User 1 still has access after logout - session not cleared!');
      }
    });

    // Test 12: User 2 Still logged in after User 1 logout
    await test('User 2: Still logged in after User 1 logout', async () => {
      const response = await makeRequest('GET', '/profile', null, user2SessionCookie);
      if (response.status !== 200) {
        throw new Error(`User 2 logged out when User 1 logged out! Status: ${response.status}`);
      }
      if (!response.body.includes(users[1].email)) {
        throw new Error(`User 2's data not in profile after User 1 logout`);
      }
    });

    // Test 13: User 2 Logout
    await test('User 2: Logout', async () => {
      const response = await makeRequest('GET', '/logout', null, user2SessionCookie);
      if (response.status !== 302) {
        throw new Error(`Expected redirect (302), got ${response.status}`);
      }
    });

    // Test 14: User 2 Session is cleared
    await test('User 2: Session cleared after logout', async () => {
      const response = await makeRequest('GET', '/profile', null, user2SessionCookie);
      if (response.status === 200) {
        throw new Error('User 2 still has access after logout');
      }
    });

  } catch (error) {
    console.error('Test setup error:', error);
  }

  // Print results
  console.log('\n=== Test Results ===\n');
  testResults.tests.forEach(t => {
    console.log(`${t.status} ${t.name}`);
    if (t.error) console.log(`   Error: ${t.error}`);
  });

  console.log(`\nPassed: ${testResults.passed}`);
  console.log(`Failed: ${testResults.failed}`);
  console.log(`Total: ${testResults.passed + testResults.failed}`);

  if (testResults.failed === 0) {
    console.log('\n🎉 All tests passed! Session management is working correctly.\n');
    process.exit(0);
  } else {
    console.log('\n❌ Some tests failed. Please review the output above.\n');
    process.exit(1);
  }
}

// Start PHP server and run tests
const { spawn } = require('child_process');

console.log('Starting PHP development server...\n');
const phpServer = spawn('php', ['-S', 'localhost:8000'], {
  cwd: '/mnt/c/KI/KI_Plan_Prototype_ZeroWebSite/plan-prototyp-produktion-v1-0-phaxes',
  stdio: 'ignore'
});

// Wait for server to start
setTimeout(() => {
  runTests().catch(error => {
    console.error('Fatal error:', error);
    process.exit(1);
  }).finally(() => {
    phpServer.kill();
    process.exit(0);
  });
}, 2000);
