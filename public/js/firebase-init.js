// Firebase Initialization
// Config is injected from PHP via window.firebaseConfig in the base layout

const firebaseConfig = window.firebaseConfig || {};

// Firebase globals - will be set if initialization succeeds
let auth = null;
let db = null;

// Initialize Firebase with error handling
try {
    if (!firebaseConfig.apiKey || !firebaseConfig.projectId) {
        console.error('Firebase config incomplete - missing apiKey or projectId');
    } else {
        firebase.initializeApp(firebaseConfig);
        auth = firebase.auth();
        db = firebase.firestore();
        console.log('Firebase initialized successfully');
    }
} catch (error) {
    console.error('Firebase initialization error:', error);
}

// Check auth state and sync with server
auth.onAuthStateChanged((user) => {
    if (user) {
        console.log('User logged in:', user.email);
        // Send token to backend for session verification
        user.getIdToken(true).then(token => {
            fetch('/auth/verify', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    idToken: token,
                    uid: user.uid,
                    email: user.email,
                    displayName: user.displayName || ''
                })
            })
            .then(res => {
                if (res.ok) {
                    console.log('Session verified on server');
                }
            })
            .catch(err => console.error('Auth verification failed:', err));
        });
    } else {
        console.log('User not logged in');
    }
});

// Firebase Firestore utility functions
const FirebaseUtils = {
    getDocument: async (collection, docId) => {
        try {
            const doc = await db.collection(collection).doc(docId).get();
            if (doc.exists) {
                return doc.data();
            }
            return null;
        } catch (err) {
            console.error(`Error getting ${collection}/${docId}:`, err);
            return null;
        }
    },

    queryCollection: async (collection, constraints = []) => {
        try {
            let query = db.collection(collection);
            constraints.forEach(c => {
                query = query.where(c.field, c.operator, c.value);
            });
            const snapshot = await query.get();
            return snapshot.docs.map(doc => ({
                id: doc.id,
                ...doc.data()
            }));
        } catch (err) {
            console.error(`Error querying ${collection}:`, err);
            return [];
        }
    }
};
