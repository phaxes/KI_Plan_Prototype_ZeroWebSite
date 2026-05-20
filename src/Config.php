<?php

namespace App;

class Config
{
    private static array $config = [];

    public static function load(): void
    {
        // Load from .env file first (for development)
        if (file_exists(__DIR__ . '/../.env')) {
            $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (str_starts_with($line, '#')) continue;
                if (str_contains($line, '=')) {
                    [$key, $value] = explode('=', $line, 2);
                    self::$config[trim($key)] = trim($value);
                }
            }
        }

        // Override with environment variables (for production on Render.com)
        // This allows Render dashboard environment variables to override .env values
        $envVars = ['FIREBASE_SERVICE_ACCOUNT_JSON', 'FIREBASE_PROJECT_ID', 'FIREBASE_API_KEY',
                    'FIREBASE_AUTH_DOMAIN', 'FIREBASE_STORAGE_BUCKET', 'FIREBASE_MESSAGING_SENDER_ID',
                    'FIREBASE_APP_ID', 'APP_ENV', 'STRIPE_SECRET_KEY', 'STRIPE_PUBLISHABLE_KEY',
                    'MAILCHIMP_API_KEY', 'MAILCHIMP_SERVER_PREFIX', 'MAILCHIMP_LIST_ID'];

        foreach ($envVars as $var) {
            $envValue = getenv($var);
            if ($envValue !== false && !empty($envValue)) {
                self::$config[$var] = $envValue;
            }
        }

        // Set defaults for missing values
        if (empty(self::$config['APP_ENV'])) {
            self::$config['APP_ENV'] = 'development';
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::$config[$key] ?? $default;
    }

    public static function all(): array
    {
        return self::$config;
    }
}
