<?php

namespace App;

use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;

class Firebase
{
    private static $firestore = null;
    private static $instance = null;
    private static $available = null;

    public static function getInstance()
    {
        if (self::$instance === null) {
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

                // Now validate JSON
                $parsed = json_decode($serviceAccountJson, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log('Firebase: Invalid JSON in FIREBASE_SERVICE_ACCOUNT_JSON: ' . json_last_error_msg());
                    return null;
                }

                // Try to create temporary file for kreait library
                $tempFile = self::createTempServiceAccountFile($serviceAccountJson);
                if ($tempFile) {
                    $serviceAccountJson = $tempFile;
                } else {
                    error_log('Firebase: Could not create temporary service account file');
                    return null;
                }
            }

            try {
                $factory = new Factory();
                self::$instance = $factory->withServiceAccount($serviceAccountJson);
            } catch (\Exception $e) {
                error_log('Firebase init: ' . $e->getMessage());
                return null;
            }
        }

        return self::$instance;
    }

    private static function createTempServiceAccountFile($jsonContent)
    {
        // Try multiple possible temp directories
        $tempDirs = [
            sys_get_temp_dir(),
            '/tmp',
            getcwd() . '/.cache',
            __DIR__ . '/../.cache'
        ];

        foreach ($tempDirs as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }

            if (is_dir($dir) && is_writable($dir)) {
                $tempFile = $dir . '/firebase_sa_' . uniqid() . '.json';
                if (file_put_contents($tempFile, $jsonContent) !== false) {
                    @chmod($tempFile, 0600);
                    error_log('Created temp service account file: ' . $tempFile);
                    return $tempFile;
                }
            }
        }

        error_log('Could not create temp service account file in any directory. Tried: ' . implode(', ', $tempDirs));
        return null;
    }

    public static function firestore()
    {
        if (self::$firestore === null) {
            $instance = self::getInstance();
            if ($instance) {
                try {
                    self::$firestore = $instance->createFirestore();
                } catch (\Exception $e) {
                    error_log('Firestore error: ' . $e->getMessage());
                }
            }
        }
        return self::$firestore;
    }

    public static function isAvailable()
    {
        if (self::$available === null) {
            self::$available = self::firestore() !== null;
        }
        return self::$available;
    }

    private static function normalizeTimestamp($value): \DateTime
    {
        if ($value instanceof \Google\Cloud\Core\Timestamp) {
            $dt = $value->get();
            return \DateTime::createFromInterface($dt);
        }
        if ($value instanceof \DateTimeInterface) {
            return \DateTime::createFromInterface($value);
        }
        return new \DateTime();
    }

    // ===== Posts (News/Blog) =====

    public static function getPosts($type = null, $published = null, $limit = 10, $offset = 0)
    {
        if (!self::isAvailable()) return [];

        try {
            $query = self::firestore()->collection('posts');

            if ($type) {
                $query = $query->where('type', '==', $type);
            }

            if ($published !== null) {
                $query = $query->where('published', '==', $published);
            }

            $query = $query->orderBy('createdAt', 'DESCENDING');
            $documents = $query->offset($offset)->limit($limit + 1)->documents();

            $posts = [];
            $count = 0;
            foreach ($documents as $doc) {
                if ($count >= $limit) break;
                $data = $doc->data();
                if (isset($data['createdAt'])) $data['createdAt'] = self::normalizeTimestamp($data['createdAt']);
                if (isset($data['updatedAt'])) $data['updatedAt'] = self::normalizeTimestamp($data['updatedAt']);
                $posts[] = [
                    'id' => $doc->id(),
                    ...$data
                ];
                $count++;
            }

            return $posts;
        } catch (\Exception $e) {
            error_log('getPosts: ' . $e->getMessage());
            return [];
        }
    }

    public static function getPostById($postId)
    {
        if (!self::isAvailable()) return null;

        try {
            $document = self::firestore()->collection('posts')->document($postId)->snapshot();

            if ($document->exists()) {
                $data = $document->data();
                if (isset($data['createdAt'])) $data['createdAt'] = self::normalizeTimestamp($data['createdAt']);
                if (isset($data['updatedAt'])) $data['updatedAt'] = self::normalizeTimestamp($data['updatedAt']);
                return [
                    'id' => $document->id(),
                    ...$data
                ];
            }

            return null;
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

            $ref = self::firestore()->collection('posts')->newDocument();
            $ref->set($postData);

            return $ref->id();
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

            self::firestore()->collection('posts')->document($postId)->set($updateData, ['merge' => true]);

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
            self::firestore()->collection('posts')->document($postId)->delete();
            return true;
        } catch (\Exception $e) {
            error_log('deletePost: ' . $e->getMessage());
            return false;
        }
    }

    // ===== Products =====

    public static function getProducts($published = true, $limit = 10, $offset = 0)
    {
        if (!self::isAvailable()) return [];

        try {
            $query = self::firestore()->collection('products');

            if ($published) {
                $query = $query->where('active', '==', true);
            }

            $query = $query->orderBy('createdAt', 'DESCENDING');
            $documents = $query->offset($offset)->limit($limit + 1)->documents();

            $products = [];
            $count = 0;
            foreach ($documents as $doc) {
                if ($count >= $limit) break;
                $data = $doc->data();
                if (isset($data['createdAt'])) $data['createdAt'] = self::normalizeTimestamp($data['createdAt']);
                if (isset($data['updatedAt'])) $data['updatedAt'] = self::normalizeTimestamp($data['updatedAt']);
                $products[] = [
                    'id' => $doc->id(),
                    ...$data
                ];
                $count++;
            }

            return $products;
        } catch (\Exception $e) {
            error_log('getProducts: ' . $e->getMessage());
            return [];
        }
    }

    public static function getProductById($productId)
    {
        if (!self::isAvailable()) return null;

        try {
            $document = self::firestore()->collection('products')->document($productId)->snapshot();

            if ($document->exists()) {
                $data = $document->data();
                if (isset($data['createdAt'])) $data['createdAt'] = self::normalizeTimestamp($data['createdAt']);
                if (isset($data['updatedAt'])) $data['updatedAt'] = self::normalizeTimestamp($data['updatedAt']);
                return [
                    'id' => $document->id(),
                    ...$data
                ];
            }

            return null;
        } catch (\Exception $e) {
            error_log('getProductById: ' . $e->getMessage());
            return null;
        }
    }

    public static function createProduct($data)
    {
        if (!self::isAvailable()) return null;

        try {
            if (empty($data['name']) || !isset($data['price'])) {
                throw new \Exception('Missing required fields');
            }

            $productData = [
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'price' => floatval($data['price']),
                'imageUrl' => $data['imageUrl'] ?? '',
                'category' => $data['category'] ?? '',
                'stock' => intval($data['stock'] ?? 0),
                'active' => $data['active'] ?? true,
                'createdAt' => new \DateTime(),
                'updatedAt' => new \DateTime()
            ];

            $ref = self::firestore()->collection('products')->newDocument();
            $ref->set($productData);

            return $ref->id();
        } catch (\Exception $e) {
            error_log('createProduct: ' . $e->getMessage());
            return null;
        }
    }

    public static function updateProduct($productId, $data)
    {
        if (!self::isAvailable()) return false;

        try {
            $updateData = [
                'updatedAt' => new \DateTime()
            ];

            if (isset($data['name'])) $updateData['name'] = $data['name'];
            if (isset($data['description'])) $updateData['description'] = $data['description'];
            if (isset($data['price'])) $updateData['price'] = floatval($data['price']);
            if (isset($data['imageUrl'])) $updateData['imageUrl'] = $data['imageUrl'];
            if (isset($data['category'])) $updateData['category'] = $data['category'];
            if (isset($data['stock'])) $updateData['stock'] = intval($data['stock']);
            if (isset($data['active'])) $updateData['active'] = $data['active'];

            self::firestore()->collection('products')->document($productId)->set($updateData, ['merge' => true]);

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
            self::firestore()->collection('products')->document($productId)->delete();
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
                'createdAt' => new \DateTime(),
                'updatedAt' => new \DateTime()
            ];

            $ref = self::firestore()->collection('orders')->newDocument();
            $ref->set($orderData);

            return $ref->id();
        } catch (\Exception $e) {
            error_log('createOrder: ' . $e->getMessage());
            return null;
        }
    }

    public static function getOrderById($orderId)
    {
        if (!self::isAvailable()) return null;

        try {
            $document = self::firestore()->collection('orders')->document($orderId)->snapshot();

            if ($document->exists()) {
                return [
                    'id' => $document->id(),
                    ...$document->data()
                ];
            }

            return null;
        } catch (\Exception $e) {
            error_log('getOrderById: ' . $e->getMessage());
            return null;
        }
    }

    public static function getUserOrders($userId)
    {
        if (!self::isAvailable()) return [];

        try {
            $documents = self::firestore()
                ->collection('orders')
                ->where('userId', '==', $userId)
                ->orderBy('createdAt', 'DESCENDING')
                ->documents();

            $orders = [];
            foreach ($documents as $doc) {
                $orders[] = [
                    'id' => $doc->id(),
                    ...$doc->data()
                ];
            }

            return $orders;
        } catch (\Exception $e) {
            error_log('getUserOrders: ' . $e->getMessage());
            return [];
        }
    }
}
