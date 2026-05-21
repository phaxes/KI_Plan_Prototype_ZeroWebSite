#!/usr/bin/env node

/**
 * Admin Debug Script
 *
 * Checks if user is admin in Firestore
 *
 * Usage: node debug-admin.js [email]
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

console.log('\n=== Admin Debug Script ===');
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
            console.log(`  Created: ${new Date(user.metadata.creationTime).toLocaleString()}`);
        } catch (error) {
            if (error.code === 'auth/user-not-found') {
                console.log(`✗ User not found in Firebase Auth`);
                process.exit(1);
            }
            throw error;
        }

        console.log('\nStep 2: Checking Firestore user document...');

        // Try to update and check
        const docRef = db.collection('users').doc(user.uid);

        try {
            // Directly set the admin flag
            console.log('Setting isAdmin = true...');
            await docRef.set({
                email: email,
                displayName: email.split('@')[0],
                isAdmin: true,
                updatedAt: new Date()
            }, { merge: true });

            console.log('✓ Firestore document updated with isAdmin = true');
            console.log('\n=== Next Steps ===');
            console.log('1. Logout from the website (click Logout button)');
            console.log('2. Login again with:');
            console.log(`   Email: ${email}`);
            console.log(`   Password: password123`);
            console.log('3. Visit /admin - should now have access');

        } catch (error) {
            console.error('Error updating Firestore:', error.message);
            throw error;
        }

        console.log('\n✓ Debug check complete!\n');
        process.exit(0);

    } catch (error) {
        console.error('\n❌ Error:', error.message);
        console.error('\nStack trace:\n', error.stack);
        process.exit(1);
    }
})();
