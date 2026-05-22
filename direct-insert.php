<?php
/**
 * Direct Firestore Insert - No GuzzleHttp, Pure PHP
 */

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

if (!$projectId || !$serviceAccountJson) {
    http_response_code(500);
    echo json_encode(['error' => 'Firebase not configured']);
    exit;
}

// Load service account
if (file_exists($serviceAccountJson)) {
    $serviceAccountJson = file_get_contents($serviceAccountJson);
} else {
    // Decode base64 if not a file path (Render.com environment)
    $decoded = base64_decode($serviceAccountJson, true);
    if ($decoded !== false) {
        $serviceAccountJson = $decoded;
    }
}

$serviceAccount = json_decode($serviceAccountJson, true);
if (!$serviceAccount) {
    http_response_code(500);
    echo json_encode(['error' => 'Invalid service account']);
    exit;
}

// Get access token using curl
function getFirebaseToken($serviceAccount) {
    $now = time();
    $header = ['alg' => 'RS256', 'typ' => 'JWT'];
    $payload = [
        'iss' => $serviceAccount['client_email'],
        'scope' => 'https://www.googleapis.com/auth/cloud-platform',
        'aud' => 'https://oauth2.googleapis.com/token',
        'exp' => $now + 3600,
        'iat' => $now
    ];

    $headerB64 = rtrim(strtr(base64_encode(json_encode($header)), '+/', '-_'), '=');
    $payloadB64 = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
    $signatureInput = $headerB64 . '.' . $payloadB64;

    openssl_sign($signatureInput, $signature, $serviceAccount['private_key'], 'sha256');
    $signatureB64 = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
    $jwt = $signatureInput . '.' . $signatureB64;

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://oauth2.googleapis.com/token',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    return $data['access_token'] ?? null;
}

// Encode data to Firestore format
function encVal($v) {
    if ($v === null) return ['nullValue' => null];
    if (is_bool($v)) return ['booleanValue' => $v];
    if (is_numeric($v)) return ['doubleValue' => (float)$v];
    if ($v instanceof DateTime) return ['timestampValue' => $v->format('Y-m-d\TH:i:s\Z')];
    if (is_array($v)) {
        $enc = [];
        foreach ($v as $k => $val) {
            $enc[$k] = encVal($val);
        }
        return ['mapValue' => ['fields' => $enc]];
    }
    return ['stringValue' => (string)$v];
}

// Insert document via Firestore REST API
function insertFirestoreDoc($projectId, $token, $collection, $docId, $data, &$errors = null) {
    $url = "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)/documents/$collection/$docId";

    $fields = [];
    foreach ($data as $k => $v) {
        $fields[$k] = encVal($v);
    }

    $payload = json_encode(['fields' => $fields]);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_CUSTOMREQUEST => 'PATCH',
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $success = $httpCode >= 200 && $httpCode < 300;
    if (!$success && is_array($errors)) {
        $errors[] = [
            'docId' => $docId,
            'collection' => $collection,
            'httpCode' => $httpCode,
            'response' => json_decode($response, true)
        ];
    }

    return $success;
}

try {
    $token = getFirebaseToken($serviceAccount);
    if (!$token) {
        throw new Exception('Failed to get Firebase token');
    }

    $results = ['news' => 0, 'blog' => 0, 'products' => 0];
    $errors = [];

    // NEWS
    $newsData = [
        ['title' => 'Rare 1971 Mercedes W114 250 US Sedan Discovered', 'content' => 'A pristine 1971 Mercedes W114 with 2.8L M130 engine discovered in California.', 'type' => 'news', 'published' => true, 'imageUrl' => 'https://images.unsplash.com/photo-1605559424843-9e4c3ca3806d?w=800', 'tags' => ['W114', 'Mercedes'], 'authorId' => 'admin', 'createdAt' => new DateTime(), 'updatedAt' => new DateTime()],
        ['title' => 'W114 Market Values Rise 12%', 'content' => 'Collectors driving strong demand for 1971 models.', 'type' => 'news', 'published' => true, 'imageUrl' => 'https://images.unsplash.com/photo-1552820728-8ac41f1ce891?w=800', 'tags' => ['Market'], 'authorId' => 'admin', 'createdAt' => new DateTime(), 'updatedAt' => new DateTime()],
        ['title' => '1971 W114 Restoration Guide', 'content' => 'Complete guide for US-market restoration.', 'type' => 'news', 'published' => true, 'imageUrl' => 'https://images.unsplash.com/photo-1486262715619-67b519e0edd3?w=800', 'tags' => ['Restoration'], 'authorId' => 'admin', 'createdAt' => new DateTime(), 'updatedAt' => new DateTime()],
        ['title' => 'Sealed Beam Restoration', 'content' => 'Authentic US-spec headlamp restoration.', 'type' => 'news', 'published' => true, 'imageUrl' => 'https://images.unsplash.com/photo-1549399542-7e3f8b83ad38?w=800', 'tags' => ['Technical'], 'authorId' => 'admin', 'createdAt' => new DateTime(), 'updatedAt' => new DateTime()],
        ['title' => 'M114 vs M130 Engine Analysis', 'content' => '1971 transition to 2.8L engine explained.', 'type' => 'news', 'published' => true, 'imageUrl' => 'https://images.unsplash.com/photo-1470225620780-dba8ba36b745?w=800', 'tags' => ['Engine'], 'authorId' => 'admin', 'createdAt' => new DateTime(), 'updatedAt' => new DateTime()],
        ['title' => 'W114 Paint Colors 1971', 'content' => 'Original Mercedes color specifications.', 'type' => 'news', 'published' => true, 'imageUrl' => 'https://images.unsplash.com/photo-1487730116645-74489c95b41b?w=800', 'tags' => ['Colors'], 'authorId' => 'admin', 'createdAt' => new DateTime(), 'updatedAt' => new DateTime()],
        ['title' => '1971 W114 Coupe $24,500 Auction', 'content' => 'Restored model achieves strong results.', 'type' => 'news', 'published' => true, 'imageUrl' => 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?w=800', 'tags' => ['Auction'], 'authorId' => 'admin', 'createdAt' => new DateTime(), 'updatedAt' => new DateTime()],
        ['title' => 'NOS Parts Still Available', 'content' => 'New Old Stock sourcing guide.', 'type' => 'news', 'published' => true, 'imageUrl' => 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=800', 'tags' => ['Parts'], 'authorId' => 'admin', 'createdAt' => new DateTime(), 'updatedAt' => new DateTime()],
        ['title' => 'Climate Control Systems', 'content' => 'Original vs modern upgrades.', 'type' => 'news', 'published' => true, 'imageUrl' => 'https://images.unsplash.com/photo-1489824904134-891ab64532f1?w=800', 'tags' => ['Systems'], 'authorId' => 'admin', 'createdAt' => new DateTime(), 'updatedAt' => new DateTime()],
        ['title' => 'European to America Import Guide', 'content' => 'Complete logistics and compliance.', 'type' => 'news', 'published' => true, 'imageUrl' => 'https://images.unsplash.com/photo-1487958449943-edee5a9ae3c9?w=800', 'tags' => ['Import'], 'authorId' => 'admin', 'createdAt' => new DateTime(), 'updatedAt' => new DateTime()],
    ];

    foreach ($newsData as $i => $item) {
        if (insertFirestoreDoc($projectId, $token, 'news', 'news-' . ($i + 1), $item, $errors)) {
            $results['news']++;
        }
    }

    // BLOG
    $blogData = [
        ['title' => '"Strich Acht" Story', 'content' => 'How the W114 became a legend.', 'type' => 'blog', 'published' => true, 'imageUrl' => 'https://images.unsplash.com/photo-1552820728-8ac41f1ce891?w=800', 'tags' => ['History'], 'authorId' => 'admin', 'createdAt' => new DateTime(), 'updatedAt' => new DateTime()],
        ['title' => 'W114 Owner Experience', 'content' => '5 years of classic ownership.', 'type' => 'blog', 'published' => true, 'imageUrl' => 'https://images.unsplash.com/photo-1605559424843-9e4c3ca3806d?w=800', 'tags' => ['Ownership'], 'authorId' => 'admin', 'createdAt' => new DateTime(), 'updatedAt' => new DateTime()],
        ['title' => 'US Market Specifics', 'content' => 'What made 1971 W114s different.', 'type' => 'blog', 'published' => true, 'imageUrl' => 'https://images.unsplash.com/photo-1487730116645-74489c95b41b?w=800', 'tags' => ['Markets'], 'authorId' => 'admin', 'createdAt' => new DateTime(), 'updatedAt' => new DateTime()],
        ['title' => 'Maintenance Secrets', 'content' => 'Keep it running strong.', 'type' => 'blog', 'published' => true, 'imageUrl' => 'https://images.unsplash.com/photo-1486262715619-67b519e0edd3?w=800', 'tags' => ['Maintenance'], 'authorId' => 'admin', 'createdAt' => new DateTime(), 'updatedAt' => new DateTime()],
        ['title' => 'M130 Engine Excellence', 'content' => 'Engineering response to regulations.', 'type' => 'blog', 'published' => true, 'imageUrl' => 'https://images.unsplash.com/photo-1470225620780-dba8ba36b745?w=800', 'tags' => ['Engine'], 'authorId' => 'admin', 'createdAt' => new DateTime(), 'updatedAt' => new DateTime()],
        ['title' => 'Interior Restoration', 'content' => 'Bringing elegance back.', 'type' => 'blog', 'published' => true, 'imageUrl' => 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=800', 'tags' => ['Interior'], 'authorId' => 'admin', 'createdAt' => new DateTime(), 'updatedAt' => new DateTime()],
        ['title' => 'Investment Potential', 'content' => 'Why collectors buy now.', 'type' => 'blog', 'published' => true, 'imageUrl' => 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?w=800', 'tags' => ['Investment'], 'authorId' => 'admin', 'createdAt' => new DateTime(), 'updatedAt' => new DateTime()],
        ['title' => 'Finding Your W114', 'content' => 'Buying guide for collectors.', 'type' => 'blog', 'published' => true, 'imageUrl' => 'https://images.unsplash.com/photo-1489824904134-891ab64532f1?w=800', 'tags' => ['Buying'], 'authorId' => 'admin', 'createdAt' => new DateTime(), 'updatedAt' => new DateTime()],
        ['title' => 'W114 Community', 'content' => 'Connect with enthusiasts worldwide.', 'type' => 'blog', 'published' => true, 'imageUrl' => 'https://images.unsplash.com/photo-1487958449943-edee5a9ae3c9?w=800', 'tags' => ['Community'], 'authorId' => 'admin', 'createdAt' => new DateTime(), 'updatedAt' => new DateTime()],
        ['title' => 'Restoration Timeline', 'content' => '18 months to perfection.', 'type' => 'blog', 'published' => true, 'imageUrl' => 'https://images.unsplash.com/photo-1552820728-8ac41f1ce891?w=800', 'tags' => ['Journey'], 'authorId' => 'admin', 'createdAt' => new DateTime(), 'updatedAt' => new DateTime()],
    ];

    foreach ($blogData as $i => $item) {
        if (insertFirestoreDoc($projectId, $token, 'blog', 'blog-' . ($i + 1), $item, $errors)) {
            $results['blog']++;
        }
    }

    // PRODUCTS
    $productData = [
        ['name' => 'Ignition Coil OEM', 'description' => 'NOS 272-906-00-60', 'price' => 145.00, 'sku' => 'MERC-IGN-COIL', 'category' => 'Engine', 'image' => 'https://images.unsplash.com/photo-1513828583688-c52646db42da?w=500', 'stock' => 3, 'active' => true, 'createdAt' => new DateTime(), 'updatedAt' => new DateTime()],
        ['name' => 'Carburetor Rebuild Kit', 'description' => 'Solex complete NOS', 'price' => 89.95, 'sku' => 'SOLEX-KIT', 'category' => 'Engine', 'image' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=500', 'stock' => 5, 'active' => true, 'createdAt' => new DateTime(), 'updatedAt' => new DateTime()],
        ['name' => 'Thermostat Housing', 'description' => 'M130 engine spec', 'price' => 67.50, 'sku' => 'MERC-THERMO', 'category' => 'Engine', 'image' => 'https://images.unsplash.com/photo-1552820728-8ac41f1ce891?w=500', 'stock' => 2, 'active' => true, 'createdAt' => new DateTime(), 'updatedAt' => new DateTime()],
        ['name' => 'Water Pump Gasket Set', 'description' => 'NOS original', 'price' => 34.95, 'sku' => 'PUMP-GASKET', 'category' => 'Engine', 'image' => 'https://images.unsplash.com/photo-1487730116645-74489c95b41b?w=500', 'stock' => 8, 'active' => true, 'createdAt' => new DateTime(), 'updatedAt' => new DateTime()],
        ['name' => 'Spark Plugs (6)', 'description' => 'Original spec set', 'price' => 42.00, 'sku' => 'PLUGS-6', 'category' => 'Engine', 'image' => 'https://images.unsplash.com/photo-1465056836643-15cea6d2f840?w=500', 'stock' => 12, 'active' => true, 'createdAt' => new DateTime(), 'updatedAt' => new DateTime()],
        ['name' => 'Alternator Bosch', 'description' => '55A output', 'price' => 156.00, 'sku' => 'BOSCH-ALT', 'category' => 'Electrical', 'image' => 'https://images.unsplash.com/photo-1486262715619-67b519e0edd3?w=500', 'stock' => 2, 'active' => true, 'createdAt' => new DateTime(), 'updatedAt' => new DateTime()],
        ['name' => 'Battery Clamps', 'description' => 'Copper plated', 'price' => 28.50, 'sku' => 'BATT-CLAMP', 'category' => 'Electrical', 'image' => 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=500', 'stock' => 15, 'active' => true, 'createdAt' => new DateTime(), 'updatedAt' => new DateTime()],
        ['name' => 'Wiper Motor 2-Speed', 'description' => 'OEM spec', 'price' => 94.75, 'sku' => 'WIPER-MOTOR', 'category' => 'Electrical', 'image' => 'https://images.unsplash.com/photo-1470225620780-dba8ba36b745?w=500', 'stock' => 4, 'active' => true, 'createdAt' => new DateTime(), 'updatedAt' => new DateTime()],
        ['name' => 'Headlamp Bulbs', 'description' => 'Sealed beam', 'price' => 56.00, 'sku' => 'BEAM-BULB', 'category' => 'Electrical', 'image' => 'https://images.unsplash.com/photo-1549399542-7e3f8b83ad38?w=500', 'stock' => 8, 'active' => true, 'createdAt' => new DateTime(), 'updatedAt' => new DateTime()],
        ['name' => 'Cluster Bulbs (8)', 'description' => 'Dashboard set', 'price' => 24.95, 'sku' => 'CLUSTER-BULB', 'category' => 'Electrical', 'image' => 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=500', 'stock' => 20, 'active' => true, 'createdAt' => new DateTime(), 'updatedAt' => new DateTime()],
        ['name' => 'Wheel Bearings Front', 'description' => 'Complete pair', 'price' => 78.50, 'sku' => 'BEARING-FRT', 'category' => 'Suspension', 'image' => 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?w=500', 'stock' => 3, 'active' => true, 'createdAt' => new DateTime(), 'updatedAt' => new DateTime()],
        ['name' => 'Brake Pads', 'description' => 'Metallic compound', 'price' => 64.00, 'sku' => 'BRAKE-PAD', 'category' => 'Brakes', 'image' => 'https://images.unsplash.com/photo-1487960412217-8148a778289c?w=500', 'stock' => 6, 'active' => true, 'createdAt' => new DateTime(), 'updatedAt' => new DateTime()],
        ['name' => 'Shock Absorbers Pair', 'description' => 'Gas-pressurized', 'price' => 156.00, 'sku' => 'SHOCK-PAIR', 'category' => 'Suspension', 'image' => 'https://images.unsplash.com/photo-1489824904134-891ab64532f1?w=500', 'stock' => 2, 'active' => true, 'createdAt' => new DateTime(), 'updatedAt' => new DateTime()],
        ['name' => 'Steering Bellows', 'description' => 'Dust boots', 'price' => 45.75, 'sku' => 'STEER-BELLOW', 'category' => 'Steering', 'image' => 'https://images.unsplash.com/photo-1487958449943-edee5a9ae3c9?w=500', 'stock' => 7, 'active' => true, 'createdAt' => new DateTime(), 'updatedAt' => new DateTime()],
        ['name' => 'Weatherstripping', 'description' => 'Door and window', 'price' => 87.50, 'sku' => 'WEATHER-STRIP', 'category' => 'Interior', 'image' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=500', 'stock' => 4, 'active' => true, 'createdAt' => new DateTime(), 'updatedAt' => new DateTime()],
        ['name' => 'Cluster Lens', 'description' => 'UV-resistant', 'price' => 38.00, 'sku' => 'CLUSTER-LENS', 'category' => 'Interior', 'image' => 'https://images.unsplash.com/photo-1494976866556-6812c9d1c72e?w=500', 'stock' => 5, 'active' => true, 'createdAt' => new DateTime(), 'updatedAt' => new DateTime()],
        ['name' => 'Floor Mats (4)', 'description' => 'Wool blend', 'price' => 156.00, 'sku' => 'MATS-4PC', 'category' => 'Interior', 'image' => 'https://images.unsplash.com/photo-1487730116645-74489c95b41b?w=500', 'stock' => 3, 'active' => true, 'createdAt' => new DateTime(), 'updatedAt' => new DateTime()],
        ['name' => 'Chrome Restoration Kit', 'description' => 'Bumpers and trim', 'price' => 52.00, 'sku' => 'CHROME-KIT', 'category' => 'Exterior', 'image' => 'https://images.unsplash.com/photo-1486262715619-67b519e0edd3?w=500', 'stock' => 12, 'active' => true, 'createdAt' => new DateTime(), 'updatedAt' => new DateTime()],
        ['name' => 'Door Gasket Set', 'description' => 'All four doors', 'price' => 94.50, 'sku' => 'DOOR-GASKET', 'category' => 'Exterior', 'image' => 'https://images.unsplash.com/photo-1552820728-8ac41f1ce891?w=500', 'stock' => 2, 'active' => true, 'createdAt' => new DateTime(), 'updatedAt' => new DateTime()],
        ['name' => 'Mercedes Badges', 'description' => 'Front and rear', 'price' => 34.00, 'sku' => 'BADGE-SET', 'category' => 'Exterior', 'image' => 'https://images.unsplash.com/photo-1614162692292-7ac56d7f7f1e?w=500', 'stock' => 10, 'active' => true, 'createdAt' => new DateTime(), 'updatedAt' => new DateTime()],
    ];

    foreach ($productData as $i => $item) {
        if (insertFirestoreDoc($projectId, $token, 'products', 'product-' . ($i + 1), $item, $errors)) {
            $results['products']++;
        }
    }

    echo json_encode([
        'success' => true,
        'results' => $results,
        'total' => $results['news'] + $results['blog'] + $results['products'],
        'timestamp' => date('Y-m-d H:i:s'),
        'errors' => $errors
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
