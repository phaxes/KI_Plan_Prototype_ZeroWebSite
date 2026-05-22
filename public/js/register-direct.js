// Direct registration handler - simpler fallback
// This runs after auth.js, so auth should be available

console.log('[register-direct.js] Loading...');
console.log('[register-direct.js] auth available:', typeof auth !== 'undefined');

document.addEventListener('DOMContentLoaded', function() {
    console.log('[register-direct.js] DOM loaded');

    const form = document.getElementById('register-form');
    console.log('[register-direct.js] register-form found:', !!form);

    if (form) {
        // Remove any existing listeners (in case AuthModule also attached one)
        const newForm = form.cloneNode(true);
        form.parentNode.replaceChild(newForm, form);

        // Attach our own listener
        newForm.addEventListener('submit', function(e) {
            console.log('[register-direct] Form submitted');
            e.preventDefault();

            // Check if auth is available
            if (typeof auth === 'undefined') {
                alert('Firebase nicht initialisiert. Bitte laden Sie die Seite neu.');
                console.error('auth is not defined');
                return;
            }

            // Get form values
            const name = newForm.querySelector('#name').value.trim();
            const email = newForm.querySelector('#email').value.trim();
            const password = newForm.querySelector('#password').value;
            const passwordConfirm = newForm.querySelector('#password-confirm').value;

            console.log('[register-direct] Form values - name:', name, 'email:', email);

            // Validate
            if (!name || !email || !password || !passwordConfirm) {
                alert('Bitte alle Felder ausfüllen');
                return;
            }

            if (password !== passwordConfirm) {
                alert('Passwörter stimmen nicht überein');
                return;
            }

            if (password.length < 6) {
                alert('Passwort muss mindestens 6 Zeichen lang sein');
                return;
            }

            // Show loading
            const button = newForm.querySelector('#register-button');
            const buttonText = newForm.querySelector('#register-button-text');
            button.disabled = true;
            buttonText.textContent = 'Wird registriert...';

            // Create Firebase user
            console.log('[register-direct] Creating Firebase user...');
            auth.createUserWithEmailAndPassword(email, password)
                .then((userCredential) => {
                    console.log('[register-direct] User created:', userCredential.user.uid);
                    const user = userCredential.user;

                    // Update profile
                    return user.updateProfile({
                        displayName: name
                    }).then(() => {
                        console.log('[register-direct] Profile updated');
                        alert('Registrierung erfolgreich! Weiterleitung...');

                        // Redirect to profile
                        setTimeout(() => {
                            window.location.href = '/profile';
                        }, 1000);
                    });
                })
                .catch((error) => {
                    console.error('[register-direct] Error:', error);
                    alert('Fehler: ' + error.message);
                    button.disabled = false;
                    buttonText.textContent = 'Registrieren';
                });
        });

        console.log('[register-direct.js] Event listener attached successfully');
    } else {
        console.warn('[register-direct.js] register-form not found');
    }
});
