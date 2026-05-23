<?php

namespace App;

use Google\Cloud\Core\GeoPoint;

class Firebase
{
    private static $accessToken = null;
    private static $projectId = null;
    private static $serviceAccount = null;

    private static function getServiceAccount()
    {
        if (self::$serviceAccount !== null) {
            return self::$serviceAccount;
        }

        $serviceAccountJson = Config::get('FIREBASE_SERVICE_ACCOUNT_JSON');

        if (!$serviceAccountJson || $serviceAccountJson === '/dev/null') {
            error_log('Firebase: Service account not configured');
            return null;
        }

        // Handle both file path (localhost) and base64-encoded JSON (Render)
        if (!file_exists($serviceAccountJson)) {
            // Try to decode if it's base64-encoded
            $decoded = base64_decode($serviceAccountJson, true);
            if ($decoded !== false) {
                $serviceAccountJson = $decoded;
            }
        } else {
            // Read from file
            $serviceAccountJson = file_get_contents($serviceAccountJson);
        }

        $parsed = json_decode($serviceAccountJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Firebase: Invalid JSON in FIREBASE_SERVICE_ACCOUNT_JSON: ' . json_last_error_msg());
            return null;
        }

        self::$serviceAccount = $parsed;
        return self::$serviceAccount;
    }

    private static function getAccessToken()
    {
        if (self::$accessToken !== null) {
            return self::$accessToken;
        }

        $serviceAccount = self::getServiceAccount();
        if (!$serviceAccount) {
            return null;
        }

        // Get or refresh access token
        $cacheFile = sys_get_temp_dir() . '/firebase_token_cache.json';
        if (file_exists($cacheFile)) {
            $cache = json_decode(file_get_contents($cacheFile), true);
            if ($cache && isset($cache['expires_at']) && $cache['expires_at'] > time() + 300) {
                self::$accessToken = $cache['access_token'];
                return self::$accessToken;
            }
        }

        // Create JWT and exchange for access token
        $now = time();
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $payload = [
            'iss' => $serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/cloud-platform',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now
        ];

        $headerEncoded = rtrim(strtr(base64_encode(json_encode($header)), '+/', '-_'), '=');
        $payloadEncoded = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
        $signatureInput = $headerEncoded . '.' . $payloadEncoded;

        $privateKey = openssl_pkey_get_private($serviceAccount['private_key']);
        if (!$privateKey) {
            error_log('Firebase: Invalid private key format');
            return null;
        }

        openssl_sign($signatureInput, $signature, $privateKey, 'sha256');

        $signatureEncoded = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
        $jwt = $signatureInput . '.' . $signatureEncoded;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://oauth2.googleapis.com/token',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            error_log('Firebase: Failed to get access token. Response: ' . $response);
            return null;
        }

        $tokenData = json_decode($response, true);
        if (!isset($tokenData['access_token'])) {
            error_log('Firebase: No access token in response');
            return null;
        }

        self::$accessToken = $tokenData['access_token'];

        // Cache the token
        @mkdir(dirname($cacheFile), 0755, true);
        file_put_contents($cacheFile, json_encode([
            'access_token' => $tokenData['access_token'],
            'expires_at' => time() + $tokenData['expires_in'] - 600
        ]));

        return self::$accessToken;
    }

    private static function getProjectId()
    {
        if (self::$projectId === null) {
            self::$projectId = Config::get('FIREBASE_PROJECT_ID');
        }
        return self::$projectId;
    }

    public static function isAvailable()
    {
        return self::getAccessToken() !== null && self::getProjectId() !== null;
    }

    private static function normalizeTimestamp($value)
    {
        if ($value instanceof \Google\Cloud\Core\Timestamp) {
            return $value->get();
        }
        if (is_array($value) && isset($value['_seconds'])) {
            return \DateTime::createFromFormat('U', $value['_seconds']);
        }
        if ($value instanceof \DateTime) {
            return $value;
        }
        return new \DateTime();
    }

    private static function apiCall($method, $path, $data = null)
    {
        $token = self::getAccessToken();
        if (!$token) {
            error_log('Firebase API: No access token');
            return null;
        }

        $projectId = self::getProjectId();
        $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents{$path}";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json'
            ]
        ]);

        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            error_log('Firebase API Error (' . $httpCode . '): ' . $response);
            return null;
        }

        return json_decode($response, true);
    }

    public static function getPosts($type = null, $published = null, $limit = 10, $offset = 0)
    {
        if (!self::isAvailable()) return [];

        try {
            // Get all posts
            $result = self::apiCall('GET', '/posts');
            if (!$result || !isset($result['documents'])) {
                return [];
            }

            $posts = [];
            foreach ($result['documents'] as $doc) {
                $data = self::documentToArray($doc);
                if ($data === null) continue;

                // Filter by type if specified
                if ($type && ($data['type'] ?? null) !== $type) {
                    continue;
                }

                // Filter by published if specified
                if ($published !== null && ($data['published'] ?? false) !== $published) {
                    continue;
                }

                $posts[] = $data;
            }

            // Sort by createdAt descending
            usort($posts, function ($a, $b) {
                $timeA = $a['createdAt'] instanceof \DateTime ? $a['createdAt']->getTimestamp() : 0;
                $timeB = $b['createdAt'] instanceof \DateTime ? $b['createdAt']->getTimestamp() : 0;
                return $timeB <=> $timeA;
            });

            // Apply pagination
            return array_slice($posts, $offset, $limit);
        } catch (\Exception $e) {
            error_log('getPosts: ' . $e->getMessage());
            return [];
        }
    }

    public static function getPostById($postId)
    {
        if (!self::isAvailable()) return null;

        try {
            $result = self::apiCall('GET', '/posts/' . $postId);
            if (!$result) {
                return null;
            }

            return self::documentToArray($result);
        } catch (\Exception $e) {
            error_log('getPostById: ' . $e->getMessage());
            return null;
        }
    }

    public static function createPost($data)
    {
        if (!self::isAvailable()) return null;

        try {
            if (empty($data['title']) || empty($data['content']) || empty($data['type'])) {
                throw new \Exception('Missing required fields');
            }

            $postData = [
                'title' => $data['title'],
                'content' => $data['content'],
                'type' => $data['type'],
                'published' => $data['published'] ?? false,
                'tags' => $data['tags'] ?? [],
                'imageUrl' => $data['imageUrl'] ?? '',
                'authorId' => $data['authorId'] ?? 'system',
                'createdAt' => new \DateTime(),
                'updatedAt' => new \DateTime()
            ];

            $documentData = self::arrayToDocument($postData);
            $result = self::apiCall('POST', '/posts', ['fields' => $documentData]);

            if ($result && isset($result['name'])) {
                // Extract document ID from name: "projects/.../documents/posts/docId"
                $parts = explode('/', $result['name']);
                return end($parts);
            }

            return null;
        } catch (\Exception $e) {
            error_log('createPost: ' . $e->getMessage());
            return null;
        }
    }

    public static function updatePost($postId, $data)
    {
        if (!self::isAvailable()) return false;

        try {
            $updateData = [
                'updatedAt' => new \DateTime()
            ];

            if (isset($data['title'])) $updateData['title'] = $data['title'];
            if (isset($data['content'])) $updateData['content'] = $data['content'];
            if (isset($data['published'])) $updateData['published'] = $data['published'];
            if (isset($data['tags'])) $updateData['tags'] = $data['tags'];
            if (isset($data['imageUrl'])) $updateData['imageUrl'] = $data['imageUrl'];

            $documentData = self::arrayToDocument($updateData);
            self::apiCall('PATCH', '/posts/' . $postId, ['fields' => $documentData]);

            return true;
        } catch (\Exception $e) {
            error_log('updatePost: ' . $e->getMessage());
            return false;
        }
    }

    public static function deletePost($postId)
    {
        if (!self::isAvailable()) return false;

        try {
            self::apiCall('DELETE', '/posts/' . $postId);
            return true;
        } catch (\Exception $e) {
            error_log('deletePost: ' . $e->getMessage());
            return false;
        }
    }

    // Utility methods for Firebase document format conversion
    private static function arrayToDocument($data)
    {
        $fields = [];
        foreach ($data as $key => $value) {
            $fields[$key] = self::valueToFirestore($value);
        }
        return $fields;
    }

    private static function valueToFirestore($value)
    {
        if ($value === null) {
            return ['nullValue' => null];
        } elseif (is_bool($value)) {
            return ['booleanValue' => $value];
        } elseif (is_int($value) || is_float($value)) {
            return ['doubleValue' => floatval($value)];
        } elseif ($value instanceof \DateTime) {
            return ['timestampValue' => $value->format('Y-m-d\TH:i:s\Z')];
        } elseif (is_array($value)) {
            if (empty($value)) {
                return ['arrayValue' => ['values' => []]];
            }
            return ['arrayValue' => ['values' => array_map([self::class, 'valueToFirestore'], $value)]];
        } else {
            return ['stringValue' => (string)$value];
        }
    }

    private static function documentToArray($doc)
    {
        if (!isset($doc['fields'])) {
            return null;
        }

        $result = [];
        $parts = explode('/', $doc['name']);
        $result['id'] = end($parts);

        foreach ($doc['fields'] as $key => $field) {
            $result[$key] = self::firestoreToValue($field);
        }

        return $result;
    }

    private static function firestoreToValue($field)
    {
        if (isset($field['nullValue'])) {
            return null;
        } elseif (isset($field['booleanValue'])) {
            return $field['booleanValue'];
        } elseif (isset($field['integerValue'])) {
            return intval($field['integerValue']);
        } elseif (isset($field['doubleValue'])) {
            return floatval($field['doubleValue']);
        } elseif (isset($field['stringValue'])) {
            return $field['stringValue'];
        } elseif (isset($field['timestampValue'])) {
            return \DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $field['timestampValue']);
        } elseif (isset($field['arrayValue'])) {
            return array_map([self::class, 'firestoreToValue'], $field['arrayValue']['values'] ?? []);
        } elseif (isset($field['mapValue'])) {
            return self::documentToArray(['fields' => $field['mapValue']['fields']]);
        }

        return null;
    }

    // ===== Products =====

    public static function getProducts($published = true, $limit = 10, $offset = 0)
    {
        if (!self::isAvailable()) return [];

        try {
            $result = self::apiCall('GET', '/products');
            if (!$result || !isset($result['documents'])) {
                return [];
            }

            $products = [];
            foreach ($result['documents'] as $doc) {
                $data = self::documentToArray($doc);
                if ($data === null) continue;

                if ($published && !($data['active'] ?? false)) {
                    continue;
                }

                $products[] = $data;
            }

            usort($products, function ($a, $b) {
                $timeA = (isset($a['createdAt']) && $a['createdAt'] instanceof \DateTime) ? $a['createdAt']->getTimestamp() : 0;
                $timeB = (isset($b['createdAt']) && $b['createdAt'] instanceof \DateTime) ? $b['createdAt']->getTimestamp() : 0;
                return $timeB <=> $timeA;
            });

            return array_slice($products, $offset, $limit);
        } catch (\Exception $e) {
            error_log('getProducts: ' . $e->getMessage());
            return [];
        }
    }

    public static function getProductById($productId)
    {
        if (!self::isAvailable()) return null;

        try {
            $result = self::apiCall('GET', '/products/' . $productId);
            return $result ? self::documentToArray($result) : null;
        } catch (\Exception $e) {
            error_log('getProductById: ' . $e->getMessage());
            return null;
        }
    }

    public static function createProduct($data)
    {
        if (!self::isAvailable()) return null;

        try {
            if (empty($data['name']) || empty($data['price'])) {
                throw new \Exception('Missing required fields');
            }

            $productData = [
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'price' => floatval($data['price']),
                'sku' => $data['sku'] ?? '',
                'category' => $data['category'] ?? '',
                'image' => $data['image'] ?? '',
                'stock' => intval($data['stock'] ?? 0),
                'active' => $data['active'] ?? true,
                'createdAt' => new \DateTime(),
                'updatedAt' => new \DateTime()
            ];

            $documentData = self::arrayToDocument($productData);
            $result = self::apiCall('POST', '/products', ['fields' => $documentData]);

            if ($result && isset($result['name'])) {
                $parts = explode('/', $result['name']);
                return end($parts);
            }

            return null;
        } catch (\Exception $e) {
            error_log('createProduct: ' . $e->getMessage());
            return null;
        }
    }

    public static function updateProduct($productId, $data)
    {
        if (!self::isAvailable()) return false;

        try {
            $updateData = ['updatedAt' => new \DateTime()];

            foreach (['name', 'description', 'price', 'sku', 'category', 'image', 'stock', 'active'] as $key) {
                if (isset($data[$key])) {
                    $updateData[$key] = $key === 'price' ? floatval($data[$key]) : $data[$key];
                }
            }

            $documentData = self::arrayToDocument($updateData);
            self::apiCall('PATCH', '/products/' . $productId, ['fields' => $documentData]);

            return true;
        } catch (\Exception $e) {
            error_log('updateProduct: ' . $e->getMessage());
            return false;
        }
    }

    public static function deleteProduct($productId)
    {
        if (!self::isAvailable()) return false;

        try {
            self::apiCall('DELETE', '/products/' . $productId);
            return true;
        } catch (\Exception $e) {
            error_log('deleteProduct: ' . $e->getMessage());
            return false;
        }
    }

    // ===== Orders =====

    public static function createOrder($data)
    {
        if (!self::isAvailable()) return null;

        try {
            if (empty($data['userId']) || empty($data['items'])) {
                throw new \Exception('Missing required fields');
            }

            $orderData = [
                'userId' => $data['userId'],
                'items' => $data['items'],
                'total' => floatval($data['total'] ?? 0),
                'status' => $data['status'] ?? 'pending',
                'shippingAddress' => $data['shippingAddress'] ?? '',
                'billingAddress' => $data['billingAddress'] ?? '',
                'paymentMethod' => $data['paymentMethod'] ?? '',
                'notes' => $data['notes'] ?? '',
                'createdAt' => new \DateTime(),
                'updatedAt' => new \DateTime()
            ];

            $documentData = self::arrayToDocument($orderData);
            $result = self::apiCall('POST', '/orders', ['fields' => $documentData]);

            if ($result && isset($result['name'])) {
                $parts = explode('/', $result['name']);
                return end($parts);
            }

            return null;
        } catch (\Exception $e) {
            error_log('createOrder: ' . $e->getMessage());
            return null;
        }
    }

    public static function getOrderById($orderId)
    {
        if (!self::isAvailable()) return null;

        try {
            $result = self::apiCall('GET', '/orders/' . $orderId);
            return $result ? self::documentToArray($result) : null;
        } catch (\Exception $e) {
            error_log('getOrderById: ' . $e->getMessage());
            return null;
        }
    }

    public static function getUserOrders($userId, $limit = 10, $offset = 0)
    {
        if (!self::isAvailable()) return [];

        try {
            $result = self::apiCall('GET', '/orders');
            if (!$result || !isset($result['documents'])) {
                return [];
            }

            $orders = [];
            foreach ($result['documents'] as $doc) {
                $data = self::documentToArray($doc);
                if ($data === null) continue;

                if (($data['userId'] ?? null) !== $userId) {
                    continue;
                }

                $orders[] = $data;
            }

            usort($orders, function ($a, $b) {
                $timeA = $a['createdAt'] instanceof \DateTime ? $a['createdAt']->getTimestamp() : 0;
                $timeB = $b['createdAt'] instanceof \DateTime ? $b['createdAt']->getTimestamp() : 0;
                return $timeB <=> $timeA;
            });

            return array_slice($orders, $offset, $limit);
        } catch (\Exception $e) {
            error_log('getUserOrders: ' . $e->getMessage());
            return [];
        }
    }

    public static function getOrderByPaymentIntentId($paymentIntentId)
    {
        if (!self::isAvailable()) return null;

        try {
            $result = self::apiCall('GET', '/orders');
            if (!$result || !isset($result['documents'])) {
                return null;
            }

            foreach ($result['documents'] as $doc) {
                $data = self::documentToArray($doc);
                if ($data === null) continue;

                if (($data['paymentIntentId'] ?? null) === $paymentIntentId) {
                    return $data;
                }
            }

            return null;
        } catch (\Exception $e) {
            error_log('getOrderByPaymentIntentId: ' . $e->getMessage());
            return null;
        }
    }

    public static function updateOrder($orderId, $data)
    {
        if (!self::isAvailable()) return false;

        try {
            $updateData = ['updatedAt' => new \DateTime()];

            foreach (['status', 'email', 'paymentIntentId', 'shippingAddress', 'billingAddress', 'paymentMethod', 'notes'] as $key) {
                if (isset($data[$key])) {
                    $updateData[$key] = $data[$key];
                }
            }

            $documentData = self::arrayToDocument($updateData);
            self::apiCall('PATCH', '/orders/' . $orderId, ['fields' => $documentData]);

            return true;
        } catch (\Exception $e) {
            error_log('updateOrder: ' . $e->getMessage());
            return false;
        }
    }

    public static function createSubscriber(array $data): ?string
    {
        if (!self::isAvailable()) return null;

        try {
            $email = strtolower(trim($data['email']));
            $docId = md5($email);

            $subscriberData = [
                'email' => $email,
                'name' => $data['name'] ?? '',
                'source' => $data['source'] ?? 'website',
                'active' => true,
                'subscribedAt' => $data['subscribedAt'] ?? new \DateTime(),
            ];

            $documentData = self::arrayToDocument($subscriberData);
            $result = self::apiCall('PATCH', '/subscribers/' . $docId, ['fields' => $documentData]);

            return $result ? $docId : null;
        } catch (\Exception $e) {
            error_log('createSubscriber: ' . $e->getMessage());
            return null;
        }
    }
}
?>
