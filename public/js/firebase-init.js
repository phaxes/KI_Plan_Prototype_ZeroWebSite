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
