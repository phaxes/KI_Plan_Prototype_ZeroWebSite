<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Config;
use App\Router;

Config::load();

// Configure session before starting
session_name('PHPSESSID');

// Set session cookie parameters BEFORE session_start()
$isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
session_set_cookie_params([
    'lifetime' => 0,           // Session cookie (deleted when browser closes)
    'path' => '/',
    'domain' => '',
    'secure' => $isSecure,     // HTTPS only on production
    'httponly' => true,        // No JavaScript access
    'samesite' => 'Lax'        // CSRF protection
]);

// Use custom save path if on Render (ephemeral filesystem)
if (getenv('RENDER')) {
    session_save_path('/tmp/php-sessions');
    @mkdir('/tmp/php-sessions', 0700, true);
}

session_start();

// Prevent session fixation on every page load
// Only regenerate if not already done in this request
if (!isset($_SESSION['_session_regenerated'])) {
    session_regenerate_id(false);
    $_SESSION['_session_regenerated'] = true;
}

$router = new Router();

// Home
$router->get('/', 'HomeController@index');

// News
$router->get('/news', 'NewsController@index');
$router->get('/news/{id}', 'NewsController@show');

// Blog
$router->get('/blog', 'BlogController@index');
$router->get('/blog/{id}', 'BlogController@show');

// Shop
$router->get('/shop', 'ShopController@index');
$router->get('/shop/{id}', 'ShopController@show');
$router->get('/cart', 'CartController@index');
$router->post('/api/cart/add', 'CartController@add');
$router->post('/api/cart/remove', 'CartController@remove');
$router->post('/api/cart/update', 'CartController@update');

// Checkout
$router->get('/checkout', 'CheckoutController@index');
$router->post('/checkout/process', 'CheckoutController@process');
$router->get('/checkout/success', 'CheckoutController@success');

// Auth
$router->get('/login', 'AuthController@login');
$router->get('/register', 'AuthController@register');
$router->post('/auth/verify', 'AuthController@verify');
$router->post('/auth/debug-token', 'AuthController@debugToken');
$router->get('/logout', 'AuthController@logout');

// Profile
$router->get('/profile', 'ProfileController@index');
$router->post('/api/profile/update', 'ProfileController@updateProfile');
$router->get('/profile/change-password', 'ProfileController@changePasswordForm');
$router->post('/auth/change-password', 'AuthController@changePassword');
$router->get('/profile/newsletter', 'ProfileController@newsletterForm');
$router->post('/api/profile/newsletter-preference', 'ProfileController@updateNewsletterPreference');

// Newsletter
$router->post('/api/newsletter/subscribe', 'NewsletterController@subscribe');

// Debug session (remove in production)
$router->get('/debug/session', function() {
    header('Content-Type: application/json');
    echo json_encode($_SESSION, JSON_PRETTY_PRINT);
});

// Admin Dashboard
$router->get('/admin', 'Admin\\DashboardController@index');

// Admin News
$router->get('/admin/news', 'Admin\\NewsAdminController@index');
$router->get('/admin/news/create', 'Admin\\NewsAdminController@create');
$router->post('/admin/news/store', 'Admin\\NewsAdminController@store');
$router->get('/admin/news/{id}/edit', 'Admin\\NewsAdminController@edit');
$router->post('/admin/news/{id}/update', 'Admin\\NewsAdminController@update');
$router->post('/admin/news/{id}/delete', 'Admin\\NewsAdminController@delete');

// Admin Blog
$router->get('/admin/blog', 'Admin\\BlogAdminController@index');
$router->get('/admin/blog/create', 'Admin\\BlogAdminController@create');
$router->post('/admin/blog/store', 'Admin\\BlogAdminController@store');
$router->get('/admin/blog/{id}/edit', 'Admin\\BlogAdminController@edit');
$router->post('/admin/blog/{id}/update', 'Admin\\BlogAdminController@update');
$router->post('/admin/blog/{id}/delete', 'Admin\\BlogAdminController@delete');

// Admin Products
$router->get('/admin/products', 'Admin\\ProductAdminController@index');
$router->get('/admin/products/create', 'Admin\\ProductAdminController@create');
$router->post('/admin/products/store', 'Admin\\ProductAdminController@store');
$router->get('/admin/products/{id}/edit', 'Admin\\ProductAdminController@edit');
$router->post('/admin/products/{id}/update', 'Admin\\ProductAdminController@update');
$router->post('/admin/products/{id}/delete', 'Admin\\ProductAdminController@delete');

// Admin Subscribers
$router->get('/admin/subscribers', 'Admin\\SubscriberController@index');

// Admin Seed (protected by key)
$router->get('/admin/seed-w114', 'AdminSeedController@seedW114Data');
$router->post('/admin/seed-w114', 'AdminSeedController@seedW114Data');

$router->dispatch();
