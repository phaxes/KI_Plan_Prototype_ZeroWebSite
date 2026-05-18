<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Config;
use App\Router;

Config::load();

session_start();

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
$router->get('/logout', 'AuthController@logout');
$router->get('/profile', 'ProfileController@index');

// Newsletter
$router->post('/api/newsletter/subscribe', 'NewsletterController@subscribe');

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

$router->dispatch();
