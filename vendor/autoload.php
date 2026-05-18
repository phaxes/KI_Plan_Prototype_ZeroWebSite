<?php
// Minimal autoloader for testing without external dependencies
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
});

// Create stub classes for external dependencies (Firebase, Stripe, Mailchimp)
if (!class_exists('Kreait\Firebase\Factory')) {
    class_alias('stdClass', 'Kreait\Firebase\Factory');
}
if (!class_exists('Stripe\Stripe')) {
    class_alias('stdClass', 'Stripe\Stripe');
}
if (!class_exists('Stripe\PaymentIntent')) {
    class_alias('stdClass', 'Stripe\PaymentIntent');
}
if (!class_exists('Stripe\Charge')) {
    class_alias('stdClass', 'Stripe\Charge');
}

return array();
