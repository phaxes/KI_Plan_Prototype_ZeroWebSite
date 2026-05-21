#!/usr/bin/env node

/**
 * End-to-End Admin Flow Test
 *
 * Tests the complete admin login and access flow
 */

const { chromium } = require('playwright');

const BASE_URL = 'https://zero-cost-website.onrender.com';
const EMAIL = 'test@example.com';
const PASSWORD = 'password123';

async function test() {
    console.log('\n=== Testing Admin Flow ===\n');

    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext();
    const page = await context.newPage();

    try {
        // Step 1: Visit login page
        console.log('Step 1: Visiting login page...');
        await page.goto(`${BASE_URL}/login`, { waitUntil: 'networkidle' });
        console.log('✓ Login page loaded');

        // Wait for Firebase to initialize
        await page.waitForTimeout(2000);

        // Step 2: Fill login form
        console.log('\nStep 2: Filling login form...');
        await page.fill('input[type="email"]', EMAIL);
        await page.fill('input[type="password"]', PASSWORD);
        console.log(`✓ Form filled with ${EMAIL}`);

        // Step 3: Submit form
        console.log('\nStep 3: Submitting login form...');
        await page.click('button[type="submit"]');

        // Wait for redirect to profile
        console.log('Waiting for redirect...');
        await page.waitForURL(`${BASE_URL}/profile`, { timeout: 10000 });
        console.log('✓ Redirected to profile');

        // Step 4: Check session data
        console.log('\nStep 4: Checking session data...');
        const displayName = await page.locator('text=Admin User, test@example.com').isVisible().catch(() => false);
        const sessionData = await page.evaluate(() => {
            return {
                displayName: document.body.innerText.includes('Admin User') || document.body.innerText.includes('test@example.com'),
                hasLogoutButton: !!document.querySelector('a[href="/logout"]'),
                htmlContent: document.body.innerText.substring(0, 500)
            };
        });

        console.log('✓ Session data:');
        console.log(`  - Display Name visible: ${sessionData.displayName}`);
        console.log(`  - Logout button found: ${sessionData.hasLogoutButton}`);

        // Step 5: Try to visit /admin
        console.log('\nStep 5: Attempting to visit /admin...');
        const adminResponse = await page.goto(`${BASE_URL}/admin`, { waitUntil: 'networkidle' });
        const statusCode = adminResponse.status();
        console.log(`Response status: ${statusCode}`);

        // Check page content
        const adminPageContent = await page.evaluate(() => {
            return {
                statusCode: document.statusCode,
                pageTitle: document.title,
                bodyText: document.body.innerText.substring(0, 200),
                hasAdminLinks: !!document.querySelector('a[href="/admin/news"]'),
                hasDashboard: document.body.innerText.includes('Dashboard')
            };
        });

        console.log('\n=== Admin Page Analysis ===');
        console.log(`Status Code: ${statusCode}`);
        console.log(`Page Title: ${adminPageContent.pageTitle}`);
        console.log(`Has Admin Links: ${adminPageContent.hasAdminLinks}`);
        console.log(`Has Dashboard Text: ${adminPageContent.hasDashboard}`);
        console.log(`\nPage Content (first 200 chars):\n${adminPageContent.bodyText}\n`);

        // Step 6: Check session in browser storage
        console.log('\nStep 6: Checking browser storage...');
        const storageData = await page.evaluate(() => {
            return {
                sessionStorage: Object.fromEntries(Object.entries(sessionStorage)),
                localStorage: Object.fromEntries(Object.entries(localStorage))
            };
        });

        console.log('Session Storage keys:', Object.keys(storageData.sessionStorage));
        console.log('Local Storage keys:', Object.keys(storageData.localStorage));

        // Step 7: Check PHP session via console
        console.log('\nStep 7: Checking network requests...');
        const cookies = await context.cookies();
        console.log('Cookies:', cookies.map(c => `${c.name}=${c.value.substring(0, 20)}...`));

        // Step 8: Check if isAdmin is set
        if (statusCode === 403) {
            console.log('\n❌ FOUND THE PROBLEM: Got 403 Forbidden');
            console.log('This means $_SESSION["isAdmin"] is FALSE or not set');
            console.log('\nDebugging info:');

            // Try to access a page that shows session info
            console.log('\nStep 8: Trying to access /profile to check session...');
            await page.goto(`${BASE_URL}/profile`, { waitUntil: 'networkidle' });

            const profileContent = await page.evaluate(() => {
                return {
                    displayName: document.body.innerText,
                    hasAdminLink: document.body.innerText.includes('Admin')
                };
            });

            console.log('Profile page shows:');
            console.log(profileContent.displayName.substring(0, 300));
        }

    } catch (error) {
        console.error('\n❌ Test failed:', error.message);
        console.error(error.stack);
    } finally {
        await browser.close();
        console.log('\n=== Test Complete ===\n');
    }
}

test();
