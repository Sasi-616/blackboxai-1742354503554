<?php
// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'vellanki_foods');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application configuration
define('SITE_NAME', 'VellankiFoods');
define('SITE_URL', 'http://localhost:8000');
define('ADMIN_EMAIL', 'admin@vellankifoods.com');

// Session configuration
define('SESSION_LIFETIME', 3600); // 1 hour
define('SESSION_NAME', 'vellanki_session');

// Security configuration
define('HASH_SALT', 'your-secret-salt-here'); // Change this in production
define('TOKEN_LIFETIME', 3600); // 1 hour

// File upload configuration
define('UPLOAD_DIR', __DIR__ . '/../public/assets/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);

// Payment configuration (example for multiple payment gateways)
define('PAYMENT_GATEWAYS', [
    'stripe' => [
        'public_key' => 'your-stripe-public-key',
        'secret_key' => 'your-stripe-secret-key',
        'webhook_secret' => 'your-stripe-webhook-secret'
    ],
    'razorpay' => [
        'key_id' => 'your-razorpay-key-id',
        'key_secret' => 'your-razorpay-key-secret'
    ]
]);

// Email configuration
define('SMTP_HOST', 'smtp.mailtrap.io');
define('SMTP_PORT', 2525);
define('SMTP_USER', 'your-smtp-username');
define('SMTP_PASS', 'your-smtp-password');

// Cache configuration
define('CACHE_ENABLED', true);
define('CACHE_DIR', __DIR__ . '/../cache/');
define('CACHE_LIFETIME', 3600); // 1 hour

// Pagination configuration
define('ITEMS_PER_PAGE', 12);

// API configuration
define('API_VERSION', 'v1');
define('API_KEY_LIFETIME', 30 * 24 * 3600); // 30 days

// Social media configuration
define('SOCIAL_MEDIA', [
    'facebook' => 'https://facebook.com/vellankifoods',
    'instagram' => 'https://instagram.com/vellankifoods',
    'twitter' => 'https://twitter.com/vellankifoods'
]);

// Shopping cart configuration
define('CART_COOKIE_NAME', 'vellanki_cart');
define('CART_COOKIE_LIFETIME', 30 * 24 * 3600); // 30 days

// Order configuration
define('ORDER_PREFIX', 'VF');
define('ORDER_STATUSES', [
    'pending' => 'Pending',
    'processing' => 'Processing',
    'shipped' => 'Shipped',
    'delivered' => 'Delivered',
    'cancelled' => 'Cancelled'
]);

// Shipping configuration
define('SHIPPING_METHODS', [
    'standard' => [
        'name' => 'Standard Shipping',
        'price' => 5.99,
        'days' => '3-5'
    ],
    'express' => [
        'name' => 'Express Shipping',
        'price' => 12.99,
        'days' => '1-2'
    ]
]);

// Tax configuration
define('TAX_RATE', 0.08); // 8%

// Image sizes configuration
define('IMAGE_SIZES', [
    'thumbnail' => [
        'width' => 150,
        'height' => 150
    ],
    'medium' => [
        'width' => 300,
        'height' => 300
    ],
    'large' => [
        'width' => 800,
        'height' => 800
    ]
]);

// Logger configuration
define('LOG_DIR', __DIR__ . '/../logs/');
define('LOG_LEVEL', 'debug'); // debug, info, warning, error

// Function to get configuration value with default fallback
function config($key, $default = null) {
    return defined($key) ? constant($key) : $default;
}

// Function to get environment-specific configuration
function env($key, $default = null) {
    return getenv($key) ?: $default;
}

// Initialize error logging
if (!file_exists(LOG_DIR)) {
    mkdir(LOG_DIR, 0777, true);
}

// Set error log file
ini_set('error_log', LOG_DIR . 'error.log');

// Set timezone
date_default_timezone_set('UTC');

// Initialize session with secure settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.cookie_samesite', 'Strict');
session_name(SESSION_NAME);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
