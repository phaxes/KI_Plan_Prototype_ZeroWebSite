#!/usr/bin/env node

/**
 * Admin Setup Script
 *
 * Promotes test@example.com to admin in Firestore
 *
 * Usage: node setup-admin.js [email]
 */

require('dotenv').config();
const { initializeApp, cert } = require('firebase-admin/app');
const { getAuth } = require('firebase-admin/auth');
const { getFirestore } = require('firebase-admin/firestore');
const path = require('path');

const serviceAccountPath = process.env.FIREBASE_SERVICE_ACCOUNT_JSON;

if (!serviceAccountPath) {
    console.error('❌ FIREBASE_SERVICE_ACCOUNT_JSON not set in .env');
    process.exit(1);
}

let serviceAccount;

try {
    // Handle both file paths and JSON strings
    if (serviceAccountPath.startsWith('{')) {
        serviceAccount = JSON.parse(serviceAccountPath);
    } else {
        // Try as file path
        const resolvedPath = path.resolve(serviceAccountPath);
        serviceAccount = require(resolvedPath);
    }
} catch (error) {
    console.error('❌ Failed to load service account:', error.message);
    process.exit(1);
}

// Initialize Firebase Admin SDK
const app = initializeApp({
    credential: cert(serviceAccount)
});

const auth = getAuth(app);
const db = getFirestore(app);

const email = process.argv[2] || 'test@example.com';

console.log('\n=== Admin Setup Script ===');
console.log(`Email: ${email}\n`);

(async () => {
    try {
        console.log('Step 1: Finding user in Firebase Auth...');

        let user;
        try {
            user = await auth.getUserByEmail(email);
            console.log(`✓ User found`);
            console.log(`  Email: ${user.email}`);
            console.log(`  UID: ${user.uid}`);
        } catch (error) {
            if (error.code === 'auth/user-not-found') {
                console.log(`✗ User not found in Firebase Auth`);
                console.log(`  Please create the user first or use a different email`);
                process.exit(1);
            }
            throw error;
        }

        console.log('\nStep 2: Updating Firestore user document...');

        // Update user document in Firestore
        await db.collection('users').doc(user.uid).set({
            email: email,
            displayName: 'Admin User',
            isAdmin: true,
            updatedAt: new Date()
        }, { merge: true });

        console.log('✓ Firestore document updated');
        console.log('\n✓ Admin flag set to: true')

        console.log('\n✓ Admin setup completed successfully!');
        console.log('\nYou can now login with:');
        console.log(`  Email: ${email}`);
        console.log(`  Password: password123 (or your password)`);
        console.log('\nAfter login, you will have access to:');
        console.log('  - /admin (Admin Dashboard)');
        console.log('  - /admin/news (News Management)');
        console.log('  - /admin/blog (Blog Management)');
        console.log('  - /admin/products (Product Management)');
        console.log('  - /admin/subscribers (Subscriber Management)\n');

        process.exit(0);
    } catch (error) {
        console.error('\n❌ Error:', error.message);
        console.error('\nStack trace:\n', error.stack);
        process.exit(1);
    }
})();
