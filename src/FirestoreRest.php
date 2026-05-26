<?php

namespace App;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;

/**
 * Firestore REST API Client
 * Uses Firestore REST API instead of gRPC (which requires ext-grpc)
 */
class FirestoreRest
{
    private static $instance = null;
    private GuzzleClient $client;
    private string $projectId;
    private ?string $accessToken = null;
    private int $tokenExpiry = 0;

    private function __construct(string $projectId, string $serviceAccountJson)
    {
        $this->projectId = $projectId;
        $this->client = new GuzzleClient();
        $this->refreshAccessToken($serviceAccountJson);
    }

    private function refreshAccessToken(string $serviceAccountJson)
    {
        $serviceAccount = json_decode($serviceAccountJson, true);

        if (!$serviceAccount || !isset($serviceAccount['private_key'], $serviceAccount['client_email'])) {
            error_log('FirestoreRest: Invalid service account - missing private_key or client_email');
            throw new \Exception('Invalid service account configuration');
        }

        error_log('FirestoreRest: Refreshing access token for ' . $serviceAccount['client_email']);

        // Create JWT
        $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $now = time();
        $payload = base64_encode(json_encode([
            'iss' => $serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/cloud-platform',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now,
        ]));

        $signature = '';
        $signSuccess = openssl_sign("$header.$payload", $signature, $serviceAccount['private_key'], 'SHA256');

        if (!$signSuccess) {
            error_log('FirestoreRest: Failed to sign JWT');
            throw new \Exception('Failed to sign JWT');
        }

        $signature = base64_encode($signature);
        $jwt = "$header.$payload.$signature";

        // Exchange JWT for access token
        try {
            $response = $this->client->post('https://oauth2.googleapis.com/token', [
                'form_params' => [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ],
            ]);

            $data = json_decode((string)$response->getBody(), true);
            $this->accessToken = $data['access_token'] ?? null;
            $this->tokenExpiry = $now + ($data['expires_in'] ?? 3600) - 300;

            if (!$this->accessToken) {
                error_log('FirestoreRest: No access token in response: ' . json_encode($data));
                throw new \Exception('Failed to get access token from Google');
            }

            error_log('FirestoreRest: Access token obtained successfully, expires at ' . $this->tokenExpiry);
        } catch (RequestException $e) {
            $statusCode = $e->getResponse()?->getStatusCode() ?? 'unknown';
            $errorBody = $e->getResponse()?->getBody() ?? 'no body';
            error_log('FirestoreRest: Token exchange failed (HTTP ' . $statusCode . '): ' . $e->getMessage());
            error_log('FirestoreRest: Google error: ' . (string)$errorBody);
            throw new \Exception('Failed to authenticate with Firebase: ' . $e->getMessage());
        }
    }

    public static function getInstance(string $projectId, string $serviceAccountJson): self
    {
        if (self::$instance === null) {
            self::$instance = new self($projectId, $serviceAccountJson);
        }

        if (time() >= self::$instance->tokenExpiry) {
            self::$instance->refreshAccessToken($serviceAccountJson);
        }

        return self::$instance;
    }

    public function getDocument(string $collection, string $documentId): ?array
    {
        try {
            $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/{$collection}/{$documentId}";

            $response = $this->client->get($url, [
                'headers' => [
                    'Authorization' => "Bearer {$this->accessToken}",
                ],
            ]);

            $data = json_decode((string)$response->getBody(), true);

            if (isset($data['fields'])) {
                return $this->decodeFieldsMap($data['fields']);
            }

            return null;
        } catch (RequestException $e) {
            if ($e->getResponse()?->getStatusCode() === 404) {
                return null;
            }
            error_log('FirestoreRest: Get document failed: ' . $e->getMessage());
            return null;
        }
    }

    public function setDocument(string $collection, string $documentId, array $data): bool
    {
        try {
            $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/{$collection}/{$documentId}";

            $encodedData = [
                'fields' => $this->encodeFieldsMap($data),
            ];

            // Build updateMask with all field names
            $fieldNames = array_keys($data);
            $updateMaskPaths = array_map(fn($f) => 'fields.' . $f, $fieldNames);

            error_log('FirestoreRest: Setting document at ' . $url);
            error_log('FirestoreRest: Fields to update: ' . json_encode($fieldNames));
            error_log('FirestoreRest: Encoded data: ' . json_encode($encodedData));

            // Add updateMask parameter
            $urlWithMask = $url . '?updateMask.fieldPaths=' . implode('&updateMask.fieldPaths=', array_map('urlencode', $updateMaskPaths));

            $response = $this->client->patch($urlWithMask, [
                'headers' => [
                    'Authorization' => "Bearer {$this->accessToken}",
                    'Content-Type' => 'application/json',
                ],
                'json' => $encodedData,
            ]);

            $statusCode = $response->getStatusCode();
            $responseBody = json_decode((string)$response->getBody(), true);
            error_log('FirestoreRest: Document set successfully (HTTP ' . $statusCode . ')');
            error_log('FirestoreRest: Response: ' . json_encode($responseBody));
            return true;
        } catch (RequestException $e) {
            $statusCode = $e->getResponse()?->getStatusCode() ?? 'unknown';
            $errorBody = $e->getResponse()?->getBody() ?? 'no body';
            error_log('FirestoreRest: Set document failed (HTTP ' . $statusCode . '): ' . $e->getMessage());
            error_log('FirestoreRest: Response body: ' . (string)$errorBody);
            return false;
        } catch (\Exception $e) {
            error_log('FirestoreRest: Unexpected error setting document: ' . $e->getMessage());
            error_log('FirestoreRest: Stack trace: ' . $e->getTraceAsString());
            return false;
        }
    }

    private function encodeFieldsMap(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $result[$key] = $this->encodeValue($value);
        }
        return $result;
    }

    private function encodeValue($value): array
    {
        if ($value === null) {
            return ['nullValue' => null];
        }

        if (is_bool($value)) {
            return ['booleanValue' => $value];
        }

        if (is_int($value)) {
            return ['integerValue' => (string)$value];
        }

        if (is_float($value)) {
            return ['doubleValue' => $value];
        }

        if (is_string($value)) {
            return ['stringValue' => $value];
        }

        if ($value instanceof \DateTime || $value instanceof \DateTimeInterface) {
            return ['timestampValue' => $value->format('c')];
        }

        if (is_array($value)) {
            // Check if it's a sequential array (list) or associative (map)
            if (array_keys($value) === range(0, count($value) - 1)) {
                return [
                    'arrayValue' => [
                        'values' => array_map(fn($v) => $this->encodeValue($v), $value),
                    ],
                ];
            } else {
                return [
                    'mapValue' => [
                        'fields' => $this->encodeFieldsMap($value),
                    ],
                ];
            }
        }

        return ['stringValue' => (string)$value];
    }

    private function decodeFieldsMap(array $fieldsMap): array
    {
        $result = [];
        foreach ($fieldsMap as $key => $field) {
            $result[$key] = $this->decodeValue($field);
        }
        return $result;
    }

    private function decodeValue(array $value)
    {
        if (isset($value['nullValue'])) {
            return null;
        }
        if (isset($value['booleanValue'])) {
            return $value['booleanValue'];
        }
        if (isset($value['integerValue'])) {
            return (int)$value['integerValue'];
        }
        if (isset($value['doubleValue'])) {
            return (float)$value['doubleValue'];
        }
        if (isset($value['stringValue'])) {
            return $value['stringValue'];
        }
        if (isset($value['mapValue']['fields'])) {
            return $this->decodeFieldsMap($value['mapValue']['fields']);
        }
        if (isset($value['arrayValue'])) {
            return isset($value['arrayValue']['values'])
                ? array_map(fn($v) => $this->decodeValue($v), $value['arrayValue']['values'])
                : [];
        }
        if (isset($value['timestampValue'])) {
            return new \DateTime($value['timestampValue']);
        }
        return null;
    }

    public function getCollection(string $collection): array
    {
        try {
            $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/{$collection}";

            $response = $this->client->get($url, [
                'headers' => [
                    'Authorization' => "Bearer {$this->accessToken}",
                ],
            ]);

            $data = json_decode((string)$response->getBody(), true);
            $documents = [];

            if (isset($data['documents']) && is_array($data['documents'])) {
                foreach ($data['documents'] as $doc) {
                    // Extract document ID from full path: "projects/xxx/databases/(default)/documents/collection/documentId"
                    $pathParts = explode('/', $doc['name']);
                    $docId = end($pathParts);

                    if (isset($doc['fields'])) {
                        $documents[$docId] = $this->decodeFieldsMap($doc['fields']);
                    }
                }
            }

            error_log("FirestoreRest: Retrieved " . count($documents) . " documents from {$collection}");
            return $documents;
        } catch (RequestException $e) {
            error_log('FirestoreRest: List collection failed: ' . $e->getMessage());
            return [];
        }
    }
}
