#!/usr/bin/env node

require('dotenv').config();
const { initializeApp, cert } = require('firebase-admin/app');
const { getFirestore } = require('firebase-admin/firestore');
const path = require('path');

const serviceAccountPath = process.env.FIREBASE_SERVICE_ACCOUNT_JSON;
let serviceAccount;

try {
    if (serviceAccountPath.startsWith('{')) {
        serviceAccount = JSON.parse(serviceAccountPath);
    } else {
        const resolvedPath = path.resolve(serviceAccountPath);
        serviceAccount = require(resolvedPath);
    }
} catch (error) {
    console.error('❌ Failed to load service account:', error.message);
    process.exit(1);
}

const app = initializeApp({ credential: cert(serviceAccount) });
const db = getFirestore(app);

const UID = 'Cx2gaIuFwVSkHHrvpjPMdlx6ao42'; // test@example.com UID

(async () => {
    console.log('\n=== Firestore Check ===\n');
    console.log(`Checking user document for UID: ${UID}\n`);

    try {
        // Get the raw document
        const docRef = db.collection('users').doc(UID);
        const snapshot = await docRef.get();

        console.log('Document exists:', snapshot.exists());

        if (snapshot.exists()) {
            const data = snapshot.data();
            console.log('\nDocument Data:');
            console.log(JSON.stringify(data, null, 2));

            console.log('\n=== Field Check ===');
            console.log(`isAdmin field exists: ${('isAdmin' in data)}`);
            console.log(`isAdmin value: ${data.isAdmin}`);
            console.log(`isAdmin === true: ${data.isAdmin === true}`);

            if (!data.isAdmin) {
                console.log('\n⚠️  isAdmin is not true. Fixing now...');

                await docRef.update({
                    isAdmin: true,
                    updatedAt: new Date()
                });

                console.log('✓ Updated isAdmin to true');

                // Verify
                const updated = await docRef.get();
                console.log('\nVerified - isAdmin is now:', updated.data().isAdmin);
            }
        } else {
            console.log('❌ Document does not exist!');
            console.log('\nCreating document...');

            await docRef.set({
                email: 'test@example.com',
                displayName: 'Admin User',
                isAdmin: true,
                createdAt: new Date(),
                updatedAt: new Date()
            });

            console.log('✓ Document created');
        }

    } catch (error) {
        console.error('Error:', error.message);
    }

    console.log('\n✓ Check complete!\n');
    process.exit(0);
})();
