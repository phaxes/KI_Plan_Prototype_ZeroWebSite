// Firebase Authentication Module
const AuthModule = {
    init: function() {
        console.log('AuthModule.init() called');
        this.setupEventListeners();
        this.checkAuthState();
    },

    setupEventListeners: function() {
        console.log('Setting up event listeners...');

        const loginForm = document.getElementById('login-form');
        console.log('Login form found:', !!loginForm);
        if (loginForm) {
            loginForm.addEventListener('submit', (e) => this.handleLogin(e));
        }

        const registerForm = document.getElementById('register-form');
        console.log('Register form found:', !!registerForm);
        if (registerForm) {
            registerForm.addEventListener('submit', (e) => this.handleRegister(e));
            console.log('Register form event listener attached');
        } else {
            console.warn('Register form not found on this page');
        }

        // Handle logout links - intercept them to call Firebase signOut
        document.querySelectorAll('a[href="/logout"]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleLogout(e);
            });
        });
    },

    checkAuthState: function() {
        // Check if we're in the logout process
        const isLoggingOut = sessionStorage.getItem('logging_out');

        auth.onAuthStateChanged((user) => {
            if (user) {
                console.log('User authenticated:', user.email);
                // Don't auto-sync during logout process
                if (isLoggingOut) {
                    console.log('Logout in progress, skipping auto-sync');
                    return;
                }
                // Only sync if we have all required data
                if (user.email && user.uid) {
                    this.syncSessionWithServer(user);
                    App.updateCartCount();
                } else {
                    console.log('User object incomplete, waiting for full load');
                }
            } else {
                console.log('User not authenticated');
                sessionStorage.removeItem('logging_out');
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
                this.syncSessionWithServer(userCredential.user, true); // true = isLoginFlow
            })
            .catch((error) => {
                console.error('Login error:', error);
                App.showNotification('Login fehlgeschlagen: ' + error.message, 'error');
            });
    },

    handleRegister: function(e) {
        e.preventDefault();
        console.log('handleRegister called');

        if (!auth) {
            App.showNotification('Firebase nicht initialisiert. Bitte laden Sie die Seite neu.', 'error');
            console.error('Firebase auth not initialized');
            return;
        }

        const name = document.getElementById('name').value.trim();
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;
        const passwordConfirm = document.getElementById('password-confirm').value;

        console.log('Register attempt - email:', email);

        // Validation
        if (!name || !email || !password || !passwordConfirm) {
            App.showNotification('Bitte alle Felder ausfüllen', 'error');
            return;
        }

        if (name.length < 2) {
            App.showNotification('Name muss mindestens 2 Zeichen lang sein', 'error');
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

        // Validate email format
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            App.showNotification('Ungültige E-Mail-Adresse', 'error');
            return;
        }

        // Show loading state
        this.setRegisterButtonLoading(true);

        auth.createUserWithEmailAndPassword(email, password)
            .then((userCredential) => {
                console.log('Firebase user created:', userCredential.user.uid);
                const user = userCredential.user;

                // Update user profile
                return user.updateProfile({
                    displayName: name
                }).then(() => {
                    console.log('User profile updated');
                    App.showNotification('Registrierung erfolgreich! Leite weiter zum Profil...', 'success');
                    // Sync with server - this will create the user in Firestore on the backend
                    this.syncSessionWithServer(user, true); // true = isLoginFlow
                });
            })
            .catch((error) => {
                console.error('Register error:', error);
                console.error('Error code:', error.code);
                let message = error.message;

                // Better error messages
                switch (error.code) {
                    case 'auth/email-already-in-use':
                        message = 'Diese E-Mail-Adresse ist bereits registriert';
                        break;
                    case 'auth/invalid-email':
                        message = 'Ungültige E-Mail-Adresse';
                        break;
                    case 'auth/weak-password':
                        message = 'Passwort ist zu schwach. Nutze Zahlen, Großbuchstaben und Sonderzeichen';
                        break;
                    case 'auth/operation-not-allowed':
                        message = 'Registrierung ist derzeit deaktiviert';
                        break;
                    case 'auth/internal-error':
                        message = 'Ein interner Fehler ist aufgetreten. Bitte versuche es später erneut';
                        break;
                    default:
                        message = error.message || 'Registrierung fehlgeschlagen';
                }

                App.showNotification('Registrierung fehlgeschlagen: ' + message, 'error');
                this.setRegisterButtonLoading(false);
            });
    },

    setRegisterButtonLoading: function(isLoading) {
        const button = document.getElementById('register-button');
        const buttonText = document.getElementById('register-button-text');
        const loadingSpinner = document.getElementById('register-loading');

        if (!button) return;

        if (isLoading) {
            button.disabled = true;
            button.classList.add('opacity-75', 'cursor-not-allowed');
            buttonText.textContent = 'Wird registriert...';
            if (loadingSpinner) loadingSpinner.classList.remove('hidden');
        } else {
            button.disabled = false;
            button.classList.remove('opacity-75', 'cursor-not-allowed');
            buttonText.textContent = 'Registrieren';
            if (loadingSpinner) loadingSpinner.classList.add('hidden');
        }
    },

    handleLogout: function(e) {
        e.preventDefault();
        console.log('Logout initiated - signing out from Firebase');

        // Set flag to prevent auto-login during logout process
        sessionStorage.setItem('logging_out', 'true');

        auth.signOut().then(() => {
            console.log('Firebase signOut successful');
            App.showNotification('Erfolgreich abgemeldet', 'success');

            // Clear any stored authentication data
            localStorage.removeItem('firebase_auth');
            sessionStorage.removeItem('firebase_auth');

            // Clear cart from localStorage
            localStorage.removeItem('cart');
            App.updateCartCount();

            // Small delay to ensure Firebase state is updated
            setTimeout(() => {
                console.log('Redirecting to /logout for PHP session cleanup');
                window.location.href = '/logout';
            }, 500);
        }).catch((error) => {
            console.error('Firebase signOut error:', error);
            // Even if Firebase signOut fails, proceed to PHP logout
            localStorage.removeItem('cart');
            App.updateCartCount();
            setTimeout(() => {
                window.location.href = '/logout';
            }, 500);
        });
    },

    syncSessionWithServer: function(user, isLoginFlow = false) {
        // Validate user object has required data
        if (!user || !user.email || !user.uid) {
            console.error('Cannot sync session: incomplete user object', user);
            return;
        }

        // Get fresh ID token and send to server for session verification
        user.getIdToken(true).then((token) => {
            console.log('Got ID token, length:', token.length);
            console.log('Token parts:', (token.match(/\./g) || []).length + 1);

            // Log token preview (first and last 50 chars)
            console.log('Token preview:', token.substring(0, 50) + '...' + token.substring(token.length - 50));

            const payload = {
                idToken: token,
                email: user.email,
                displayName: user.displayName || '',
                uid: user.uid
            };

            console.log('Syncing session with payload:', {
                email: payload.email,
                uid: payload.uid,
                tokenLength: token.length
            });

            fetch('/auth/verify', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then((response) => {
                return response.json().then(data => {
                    if (response.ok) {
                        console.log('Session verified on server:', data);
                        // Store admin status for later use
                        if (data.isAdmin === true) {
                            sessionStorage.setItem('user_is_admin', 'true');
                        } else {
                            sessionStorage.removeItem('user_is_admin');
                        }

                        // If this is a login flow, redirect based on admin status
                        if (isLoginFlow) {
                            setTimeout(() => {
                                if (data.isAdmin === true) {
                                    console.log('User is admin, redirecting to /admin');
                                    window.location.href = '/admin';
                                } else {
                                    console.log('User is not admin, redirecting to /profile');
                                    window.location.href = '/profile';
                                }
                            }, 500);
                        }
                    } else {
                        console.error('Session verification failed:', response.status);
                        console.error('Server response:', JSON.stringify(data, null, 2));
                        if (isLoginFlow) {
                            setTimeout(() => {
                                window.location.href = '/profile';
                            }, 1000);
                        }
                    }
                });
            })
            .catch((error) => {
                console.error('Session sync error:', error);
                if (isLoginFlow) {
                    setTimeout(() => {
                        window.location.href = '/profile';
                    }, 1000);
                }
            });
        }).catch((error) => {
            console.error('Failed to get ID token:', error);
            if (isLoginFlow) {
                setTimeout(() => {
                    window.location.href = '/profile';
                }, 1000);
            }
        });
    }
};

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    AuthModule.init();
});
