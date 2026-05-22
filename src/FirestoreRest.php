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
            throw new \Exception('Invalid service account configuration');
        }

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
        openssl_sign("$header.$payload", $signature, $serviceAccount['private_key'], 'SHA256');
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
                throw new \Exception('Failed to get access token');
            }

            error_log('FirestoreRest: Access token obtained');
        } catch (RequestException $e) {
            error_log('FirestoreRest: Token exchange failed: ' . $e->getMessage());
            throw new \Exception('Failed to authenticate with Firebase');
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
        if (isset($value['arrayValue']['values'])) {
            return array_map(fn($v) => $this->decodeValue($v), $value['arrayValue']['values']);
        }
        if (isset($value['timestampValue'])) {
            return new \DateTime($value['timestampValue']);
        }
        return null;
    }
}
