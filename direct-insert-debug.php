<?php
require_once __DIR__ . '/src/Config.php';
use App\Config;

Config::load();

header('Content-Type: application/json');

$key = $_GET['key'] ?? $_POST['key'] ?? null;
if ($key !== 'W114_INSERT_NOW') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$projectId = Config::get('FIREBASE_PROJECT_ID');
$serviceAccountJson = Config::get('FIREBASE_SERVICE_ACCOUNT_JSON');

echo json_encode([
    'debug' => [
        'projectId_set' => !empty($projectId),
        'projectId_value' => $projectId,
        'serviceAccountJson_set' => !empty($serviceAccountJson),
        'serviceAccountJson_length' => strlen($serviceAccountJson ?? ''),
        'serviceAccountJson_first_50' => substr($serviceAccountJson ?? '', 0, 50),
    ]
]);

if (!$projectId || !$serviceAccountJson) {
    http_response_code(500);
    echo json_encode(['error' => 'Firebase not configured', 'debug' => ['projectId' => $projectId, 'accountLength' => strlen($serviceAccountJson ?? '')]]);
    exit;
}

// Try to parse
if (file_exists($serviceAccountJson)) {
    $serviceAccountJson = file_get_contents($serviceAccountJson);
}

$serviceAccount = json_decode($serviceAccountJson, true);
if (!$serviceAccount) {
    http_response_code(500);
    echo json_encode(['error' => 'Invalid service account JSON', 'json_error' => json_last_error_msg()]);
    exit;
}

echo json_encode(['success' => true, 'parsed' => true, 'account_keys' => array_keys($serviceAccount)]);
?>
