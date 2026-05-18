<?php
// Check for real composer autoloader first (when dependencies are installed)
if (file_exists(__DIR__ . '/composer/autoload_real.php')) {
    return require __DIR__ . '/composer/autoload_real.php';
}

// Fallback: Minimal autoloader for App namespace only
// External dependencies (Firebase, Stripe, Mailchimp) will gracefully degrade
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    if (strpos($class, $prefix) === 0) {
        $relative_class = substr($class, strlen($prefix));
        $file = __DIR__ . '/../src/' . str_replace('\\', '/', $relative_class) . '.php';
        if (file_exists($file)) {
            require $file;
            return true;
        }
    }
    return false;
}, true, true);

return array();
