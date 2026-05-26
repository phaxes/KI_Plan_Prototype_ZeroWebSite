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

    /**
     * Get service account JSON from file or string configuration
     * Handles multiple path resolution strategies for different environments
     *
     * @return string JSON string of service account, or throws on failure
     */
    public static function getServiceAccountJson(): string
    {
        $serviceAccountPath = self::get('FIREBASE_SERVICE_ACCOUNT_JSON');

        if (!$serviceAccountPath) {
            throw new \Exception('FIREBASE_SERVICE_ACCOUNT_JSON not configured');
        }

        // If it's already JSON (starts with {), return as-is
        $trimmed = trim($serviceAccountPath);
        if (strlen($trimmed) > 0 && $trimmed[0] === '{') {
            return $serviceAccountPath;
        }

        // Try to load from file: absolute path first, then relative to project root
        $paths = [
            $serviceAccountPath,  // Try as given
            dirname(__DIR__) . '/' . $serviceAccountPath,  // Relative to project root
        ];

        // Also try with getcwd() prefix in case working directory is different
        if (!str_starts_with($serviceAccountPath, '/')) {
            $paths[] = getcwd() . '/' . $serviceAccountPath;
        }

        foreach ($paths as $path) {
            if (file_exists($path)) {
                $content = file_get_contents($path);
                if ($content === false) {
                    continue;
                }
                return $content;
            }
        }

        // If no file found, assume it's a base64-encoded or raw JSON string
        // Try base64 decode
        $decoded = base64_decode($serviceAccountPath, true);
        if ($decoded !== false) {
            $decodedTrimmed = trim($decoded);
            if (strlen($decodedTrimmed) > 0 && $decodedTrimmed[0] === '{') {
                return $decoded;
            }
        }

        // Last resort: return as-is and let FirestoreRest handle validation
        return $serviceAccountPath;
    }
}
