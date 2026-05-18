// Firebase Authentication Module
const AuthModule = {
    init: function() {
        this.setupEventListeners();
        this.checkAuthState();
    },

    setupEventListeners: function() {
        const loginForm = document.getElementById('login-form');
        if (loginForm) {
            loginForm.addEventListener('submit', (e) => this.handleLogin(e));
        }

        const registerForm = document.getElementById('register-form');
        if (registerForm) {
            registerForm.addEventListener('submit', (e) => this.handleRegister(e));
        }

        const logoutBtn = document.getElementById('logout-btn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', (e) => this.handleLogout(e));
        }
    },

    checkAuthState: function() {
        auth.onAuthStateChanged((user) => {
            if (user) {
                console.log('User authenticated:', user.email);
                this.syncSessionWithServer(user);
            } else {
                console.log('User not authenticated');
            }
        });
    },

    handleLogin: function(e) {
        e.preventDefault();
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;

        if (!email || !password) {
            App.showNotification('Bitte alle Felder ausfüllen', 'error');
            return;
        }

        auth.signInWithEmailAndPassword(email, password)
            .then((userCredential) => {
                App.showNotification('Erfolgreich angemeldet!', 'success');
                this.syncSessionWithServer(userCredential.user);
                setTimeout(() => {
                    window.location.href = '/profile';
                }, 1000);
            })
            .catch((error) => {
                console.error('Login error:', error);
                App.showNotification('Login fehlgeschlagen: ' + error.message, 'error');
            });
    },

    handleRegister: function(e) {
        e.preventDefault();
        const name = document.getElementById('name').value;
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        const passwordConfirm = document.getElementById('password-confirm').value;

        if (!name || !email || !password || !passwordConfirm) {
            App.showNotification('Bitte alle Felder ausfüllen', 'error');
            return;
        }

        if (password !== passwordConfirm) {
            App.showNotification('Passwörter stimmen nicht überein', 'error');
            return;
        }

        if (password.length < 6) {
            App.showNotification('Passwort muss mindestens 6 Zeichen lang sein', 'error');
            return;
        }

        auth.createUserWithEmailAndPassword(email, password)
            .then((userCredential) => {
                const user = userCredential.user;

                // Update user profile
                return user.updateProfile({
                    displayName: name
                }).then(() => {
                    // Create user document in Firestore
                    return db.collection('users').doc(user.uid).set({
                        displayName: name,
                        email: email,
                        isAdmin: false,
                        createdAt: firebase.firestore.FieldValue.serverTimestamp(),
                        address: {}
                    });
                }).then(() => {
                    App.showNotification('Registrierung erfolgreich!', 'success');
                    this.syncSessionWithServer(user);
                    setTimeout(() => {
                        window.location.href = '/profile';
                    }, 1000);
                });
            })
            .catch((error) => {
                console.error('Register error:', error);
                let message = error.message;
                if (error.code === 'auth/email-already-in-use') {
                    message = 'E-Mail-Adresse wird bereits verwendet';
                } else if (error.code === 'auth/invalid-email') {
                    message = 'Ungültige E-Mail-Adresse';
                }
                App.showNotification('Registrierung fehlgeschlagen: ' + message, 'error');
            });
    },

    handleLogout: function(e) {
        e.preventDefault();
        auth.signOut().then(() => {
            App.showNotification('Erfolgreich abgemeldet', 'success');
            setTimeout(() => {
                window.location.href = '/';
            }, 500);
        });
    },

    syncSessionWithServer: function(user) {
        // Get ID token and send to server for session verification
        user.getIdToken(true).then((token) => {
            fetch('/auth/verify', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    idToken: token,
                    email: user.email,
                    displayName: user.displayName || '',
                    uid: user.uid
                })
            })
            .then((response) => {
                if (response.ok) {
                    console.log('Session verified on server');
                } else {
                    console.error('Session verification failed');
                }
            })
            .catch((error) => {
                console.error('Session sync error:', error);
            });
        });
    }
};

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    AuthModule.init();
});
