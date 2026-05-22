<?php
/**
 * W114 Simple Seeding via Direct Firestore REST API (no GuzzleHttp)
 */

header('Content-Type: application/json');

$key = $_GET['key'] ?? $_POST['key'] ?? null;
if ($key !== 'W114_SEED_2024_RENDER') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/src/Config.php';
use App\Config;

Config::load();

$projectId = Config::get('FIREBASE_PROJECT_ID');
$serviceAccountJson = Config::get('FIREBASE_SERVICE_ACCOUNT_JSON');

if (!$projectId || !$serviceAccountJson) {
    http_response_code(500);
    echo json_encode(['error' => 'Firebase not configured']);
    exit;
}

// Handle file path
if (file_exists($serviceAccountJson)) {
    $serviceAccountJson = file_get_contents($serviceAccountJson);
}

$serviceAccount = json_decode($serviceAccountJson, true);
if (!$serviceAccount) {
    http_response_code(500);
    echo json_encode(['error' => 'Invalid service account JSON']);
    exit;
}

// Get access token
function getAccessToken($serviceAccount) {
    $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $now = time();
    $payload = base64_encode(json_encode([
        'iss' => $serviceAccount['client_email'],
        'scope' => 'https://www.googleapis.com/auth/cloud-platform',
        'aud' => 'https://oauth2.googleapis.com/token',
        'exp' => $now + 3600,
        'iat' => $now,
    ]));

    openssl_sign("$header.$payload", $signature, $serviceAccount['private_key'], 'SHA256');
    $signature = base64_encode($signature);
    $jwt = "$header.$payload.$signature";

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://oauth2.googleapis.com/token',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query(['grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion' => $jwt]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    return $data['access_token'] ?? null;
}

// Simple value encoder for Firestore
function encodeValue($value) {
    if ($value === null) {
        return ['nullValue' => null];
    } elseif (is_bool($value)) {
        return ['booleanValue' => $value];
    } elseif (is_int($value) || is_float($value)) {
        return ['doubleValue' => (float)$value];
    } elseif ($value instanceof DateTime) {
        return ['timestampValue' => $value->format('Y-m-d\TH:i:s\Z')];
    } elseif (is_array($value)) {
        if (empty($value)) {
            return ['arrayValue' => ['values' => []]];
        }
        return ['arrayValue' => ['values' => array_map('encodeValue', $value)]];
    } else {
        return ['stringValue' => (string)$value];
    }
}

function encodeFields($data) {
    $result = [];
    foreach ($data as $key => $value) {
        $result[$key] = encodeValue($value);
    }
    return $result;
}

// Set document via Firestore REST API
function setDocument($projectId, $accessToken, $collection, $documentId, $data) {
    $url = "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)/documents/$collection/$documentId";

    $encodedData = ['fields' => encodeFields($data)];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_CUSTOMREQUEST => 'PATCH',
        CURLOPT_POSTFIELDS => json_encode($encodedData),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $httpCode >= 200 && $httpCode < 300;
}

try {
    $accessToken = getAccessToken($serviceAccount);
    if (!$accessToken) {
        throw new Exception('Failed to get access token');
    }

    $results = ['news' => 0, 'blog' => 0, 'products' => 0, 'errors' => []];

    // News
    $newsData = [
        ['title' => 'Rare 1971 Mercedes W114 250 US Sedan Discovered', 'content' => 'A pristine 1971 Mercedes-Benz W114 250 with 2.8L M130 engine found in California.', 'type' => 'news', 'published' => true, 'imageUrl' => 'https://images.unsplash.com/photo-1605559424843-9e4c3ca3806d?w=800&q=80', 'tags' => ['W114', '1971'], 'authorId' => 'editor'],
        ['title' => 'W114 Market Values Rise 12% in 2024', 'content' => 'Classic.com reports strong appreciation for Mercedes W114 models. Average prices at $17,247.', 'type' => 'news', 'published' => true, 'imageUrl' => 'https://images.unsplash.com/photo-1552820728-8ac41f1ce891?w=800&q=80', 'tags' => ['Market'], 'authorId' => 'editor'],
        ['title' => '1971 W114 Restoration Guide Published', 'content' => 'Complete guide covers engine, electrical, interior and exterior restoration for US models.', 'type' => 'news', 'published' => true, 'imageUrl' => 'https://images.unsplash.com/photo-1486262715619-67b519e0edd3?w=800&q=80', 'tags' => ['Restoration'], 'authorId' => 'editor'],
        ['title' => 'Sealed Beam Headlamp Restoration Techniques', 'content' => 'Modern restoration methods preserve authenticity of US-market sealed-beam headlamps.', 'type' => 'news', 'published' => true, 'imageUrl' => 'https://images.unsplash.com/photo-1549399542-7e3f8b83ad38?w=800&q=80', 'tags' => ['Restoration'], 'authorId' => 'editor'],
        ['title' => 'M114 vs M130: Engine Comparison', 'content' => '1971 transition from 2.5L M114 to 2.8L M130 engine detailed in technical analysis.', 'type' => 'news', 'published' => true, 'imageUrl' => 'https://images.unsplash.com/photo-1470225620780-dba8ba36b745?w=800&q=80', 'tags' => ['Engine'], 'authorId' => 'editor'],
        ['title' => 'W114 Paint Colors: 1971 Specifications', 'content' => 'Mercedes offered exceptional color palette for American market W114 models.', 'type' => 'news', 'published' => true, 'imageUrl' => 'https://images.unsplash.com/photo-1487730116645-74489c95b41b?w=800&q=80', 'tags' => ['Paint'], 'authorId' => 'editor'],
        ['title' => '1971 W114 Coupe Sells for $24,500', 'content' => 'Beautifully restored W114 250C with low mileage achieves strong auction result.', 'type' => 'news', 'published' => true, 'imageUrl' => 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?w=800&q=80', 'tags' => ['Auction'], 'authorId' => 'editor'],
        ['title' => 'NOS W114 Parts Still Available', 'content' => 'Investigation reveals which New Old Stock parts remain available for 1971 models.', 'type' => 'news', 'published' => true, 'imageUrl' => 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=800&q=80', 'tags' => ['Parts'], 'authorId' => 'editor'],
        ['title' => 'Climate Control: Original vs Aftermarket', 'content' => 'Exploring restoration options for aging 1971 W114 climate control systems.', 'type' => 'news', 'published' => true, 'imageUrl' => 'https://images.unsplash.com/photo-1489824904134-891ab64532f1?w=800&q=80', 'tags' => ['Technical'], 'authorId' => 'editor'],
        ['title' => 'Importing W114 from Europe to America', 'content' => 'Comprehensive guide covering customs, EPA compliance, and logistics for imports.', 'type' => 'news', 'published' => true, 'imageUrl' => 'https://images.unsplash.com/photo-1487958449943-edee5a9ae3c9?w=800&q=80', 'tags' => ['Import'], 'authorId' => 'editor'],
    ];

    foreach ($newsData as $idx => $item) {
        try {
            $item['createdAt'] = new DateTime();
            $item['updatedAt'] = new DateTime();
            if (setDocument($projectId, $accessToken, 'news', 'news-' . ($idx + 1), $item)) {
                $results['news']++;
            }
        } catch (Exception $e) {
            $results['errors'][] = "News " . ($idx + 1) . ": " . $e->getMessage();
        }
    }

    // Blog (simplified to 5 for testing)
    $blogData = [
        ['title' => 'The Story of "Strich Acht"', 'content' => 'How the nickname "/" became synonymous with automotive excellence in the W114/115.', 'type' => 'blog', 'published' => true, 'imageUrl' => 'https://images.unsplash.com/photo-1552820728-8ac41f1ce891?w=800&q=80', 'tags' => ['History'], 'authorId' => 'editor'],
        ['title' => 'Collecting Classic Mercedes: W114 Ownership', 'content' => 'Five years of experience preserving a 1971 W114 in original condition.', 'type' => 'blog', 'published' => true, 'imageUrl' => 'https://images.unsplash.com/photo-1605559424843-9e4c3ca3806d?w=800&q=80', 'tags' => ['Ownership'], 'authorId' => 'editor'],
        ['title' => 'American Market Specifics', 'content' => 'What made 1971 US-market W114 models unique compared to European counterparts.', 'type' => 'blog', 'published' => true, 'imageUrl' => 'https://images.unsplash.com/photo-1487730116645-74489c95b41b?w=800&q=80', 'tags' => ['Specifications'], 'authorId' => 'editor'],
        ['title' => 'Maintenance Secrets for Longevity', 'content' => 'How to keep your W114 running strong after 50+ years.', 'type' => 'blog', 'published' => true, 'imageUrl' => 'https://images.unsplash.com/photo-1486262715619-67b519e0edd3?w=800&q=80', 'tags' => ['Maintenance'], 'authorId' => 'editor'],
        ['title' => 'M130 Engine: Mercedes Response to Emissions', 'content' => '2.8L six-cylinder engineering excellence in response to 1971 regulations.', 'type' => 'blog', 'published' => true, 'imageUrl' => 'https://images.unsplash.com/photo-1470225620780-dba8ba36b745?w=800&q=80', 'tags' => ['Engine'], 'authorId' => 'editor'],
        ['title' => 'Interior Restoration: Bringing 1971 Elegance Back', 'content' => 'Techniques for restoring original leather, plastics, and trim.', 'type' => 'blog', 'published' => true, 'imageUrl' => 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=800&q=80', 'tags' => ['Interior'], 'authorId' => 'editor'],
        ['title' => 'Investment Potential: Why Buy Now', 'content' => 'Financial analysis of W114 appreciation trends and future outlook.', 'type' => 'blog', 'published' => true, 'imageUrl' => 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?w=800&q=80', 'tags' => ['Investment'], 'authorId' => 'editor'],
        ['title' => 'Finding Your Perfect 1971 W114', 'content' => 'What to look for when purchasing a classic W114 sedan or coupe.', 'type' => 'blog', 'published' => true, 'imageUrl' => 'https://images.unsplash.com/photo-1489824904134-891ab64532f1?w=800&q=80', 'tags' => ['Buying'], 'authorId' => 'editor'],
        ['title' => 'W114 Community: Enthusiasts Worldwide', 'content' => 'Forums, clubs, and networks connecting collectors and restorers.', 'type' => 'blog', 'published' => true, 'imageUrl' => 'https://images.unsplash.com/photo-1487958449943-edee5a9ae3c9?w=800&q=80', 'tags' => ['Community'], 'authorId' => 'editor'],
        ['title' => 'Barn Find to Road Queen in 18 Months', 'content' => 'Complete restoration journey with documentation and lessons learned.', 'type' => 'blog', 'published' => true, 'imageUrl' => 'https://images.unsplash.com/photo-1552820728-8ac41f1ce891?w=800&q=80', 'tags' => ['Restoration'], 'authorId' => 'editor'],
    ];

    foreach ($blogData as $idx => $item) {
        try {
            $item['createdAt'] = new DateTime();
            $item['updatedAt'] = new DateTime();
            if (setDocument($projectId, $accessToken, 'blog', 'blog-' . ($idx + 1), $item)) {
                $results['blog']++;
            }
        } catch (Exception $e) {
            $results['errors'][] = "Blog " . ($idx + 1) . ": " . $e->getMessage();
        }
    }

    // Products (20)
    $productsData = [
        ['name' => 'Ignition Coil OEM Mercedes (272-906-00-60)', 'description' => 'NOS ignition coil for 1971 W114. Original specifications.', 'price' => 145.00, 'sku' => 'MERC-272-906-00-60', 'category' => 'Engine - Electrical', 'image' => 'https://images.unsplash.com/photo-1513828583688-c52646db42da?w=500&q=80', 'stock' => 3, 'active' => true],
        ['name' => 'Solex Carburetor Rebuild Kit', 'description' => 'Complete NOS kit for 1971 W114 Solex carburetor.', 'price' => 89.95, 'sku' => 'SOLEX-KIT-71', 'category' => 'Engine - Fuel', 'image' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=500&q=80', 'stock' => 5, 'active' => true],
        ['name' => 'Thermostat Housing OEM', 'description' => 'Original Mercedes thermostat for M130 engine.', 'price' => 67.50, 'sku' => 'MERC-THERM-71', 'category' => 'Engine - Cooling', 'image' => 'https://images.unsplash.com/photo-1552820728-8ac41f1ce891?w=500&q=80', 'stock' => 2, 'active' => true],
        ['name' => 'Water Pump Gasket Set NOS', 'description' => 'Authentic gasket set for water pump replacement.', 'price' => 34.95, 'sku' => 'PUMP-GASKET-71', 'category' => 'Engine - Cooling', 'image' => 'https://images.unsplash.com/photo-1487730116645-74489c95b41b?w=500&q=80', 'stock' => 8, 'active' => true],
        ['name' => 'Spark Plug Set (6)', 'description' => 'Complete set for original M130 specification.', 'price' => 42.00, 'sku' => 'PLUGS-6PC-71', 'category' => 'Engine - Ignition', 'image' => 'https://images.unsplash.com/photo-1465056836643-15cea6d2f840?w=500&q=80', 'stock' => 12, 'active' => true],
        ['name' => 'Alternator Bosch Design', 'description' => 'OEM-equivalent 55A alternator for 1971 W114.', 'price' => 156.00, 'sku' => 'BOSCH-ALT-71', 'category' => 'Electrical - Charging', 'image' => 'https://images.unsplash.com/photo-1486262715619-67b519e0edd3?w=500&q=80', 'stock' => 2, 'active' => true],
        ['name' => 'Battery Terminal Clamps', 'description' => 'Copper-plated reproduction terminals.', 'price' => 28.50, 'sku' => 'TERM-CLAMP-71', 'category' => 'Electrical - Battery', 'image' => 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=500&q=80', 'stock' => 15, 'active' => true],
        ['name' => 'Wiper Motor 2-Speed', 'description' => 'Original specification two-speed wiper motor.', 'price' => 94.75, 'sku' => 'WIPER-2SPD-71', 'category' => 'Electrical - Wipers', 'image' => 'https://images.unsplash.com/photo-1470225620780-dba8ba36b745?w=500&q=80', 'stock' => 4, 'active' => true],
        ['name' => 'Sealed Beam Headlamp Bulbs', 'description' => 'Authentic sealed-beam bulbs for US models.', 'price' => 56.00, 'sku' => 'BEAM-BULB-71', 'category' => 'Electrical - Lighting', 'image' => 'https://images.unsplash.com/photo-1549399542-7e3f8b83ad38?w=500&q=80', 'stock' => 8, 'active' => true],
        ['name' => 'Instrument Cluster Bulbs (8 pcs)', 'description' => 'Complete set for dashboard illumination.', 'price' => 24.95, 'sku' => 'CLUSTER-BULBS-71', 'category' => 'Electrical - Lighting', 'image' => 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=500&q=80', 'stock' => 20, 'active' => true],
        ['name' => 'Front Wheel Bearing Set', 'description' => 'Complete bearing assembly for both front wheels.', 'price' => 78.50, 'sku' => 'BEARING-FRONT-71', 'category' => 'Suspension', 'image' => 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?w=500&q=80', 'stock' => 3, 'active' => true],
        ['name' => 'Brake Pad Set - Metallic', 'description' => 'Premium pads for front disc brakes.', 'price' => 64.00, 'sku' => 'BRAKE-PAD-71', 'category' => 'Brakes', 'image' => 'https://images.unsplash.com/photo-1487960412217-8148a778289c?w=500&q=80', 'stock' => 6, 'active' => true],
        ['name' => 'Shock Absorber Pair', 'description' => 'Gas-pressurized front suspension shocks.', 'price' => 156.00, 'sku' => 'SHOCK-PAIR-71', 'category' => 'Suspension', 'image' => 'https://images.unsplash.com/photo-1489824904134-891ab64532f1?w=500&q=80', 'stock' => 2, 'active' => true],
        ['name' => 'Steering Rack Bellows', 'description' => 'Protective dust boots for steering rack.', 'price' => 45.75, 'sku' => 'STEER-BELLOWS-71', 'category' => 'Steering', 'image' => 'https://images.unsplash.com/photo-1487958449943-edee5a9ae3c9?w=500&q=80', 'stock' => 7, 'active' => true],
        ['name' => 'Weatherstripping Set', 'description' => 'EPDM rubber for doors and windows.', 'price' => 87.50, 'sku' => 'WEATHER-71', 'category' => 'Interior - Sealing', 'image' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=500&q=80', 'stock' => 4, 'active' => true],
        ['name' => 'Instrument Cluster Lens', 'description' => 'UV-resistant plexiglass replacement.', 'price' => 38.00, 'sku' => 'CLUSTER-LENS-71', 'category' => 'Interior - Instruments', 'image' => 'https://images.unsplash.com/photo-1494976866556-6812c9d1c72e?w=500&q=80', 'stock' => 5, 'active' => true],
        ['name' => 'Floor Mat Set', 'description' => 'Wool blend mats with authentic Mercedes pattern.', 'price' => 156.00, 'sku' => 'MATS-4PC-71', 'category' => 'Interior - Trim', 'image' => 'https://images.unsplash.com/photo-1487730116645-74489c95b41b?w=500&q=80', 'stock' => 3, 'active' => true],
        ['name' => 'Chrome Restoration Kit', 'description' => 'Professional system for bumpers and trim.', 'price' => 52.00, 'sku' => 'CHROME-KIT-71', 'category' => 'Exterior - Chrome', 'image' => 'https://images.unsplash.com/photo-1486262715619-67b519e0edd3?w=500&q=80', 'stock' => 12, 'active' => true],
        ['name' => 'Door Gasket Set', 'description' => 'EPDM rubber for all four doors.', 'price' => 94.50, 'sku' => 'DOOR-GASKET-71', 'category' => 'Exterior - Sealing', 'image' => 'https://images.unsplash.com/photo-1552820728-8ac41f1ce891?w=500&q=80', 'stock' => 2, 'active' => true],
        ['name' => 'Mercedes Badge Set', 'description' => 'Reproduction front and rear badges.', 'price' => 34.00, 'sku' => 'BADGE-SET-71', 'category' => 'Exterior - Badges', 'image' => 'https://images.unsplash.com/photo-1614162692292-7ac56d7f7f1e?w=500&q=80', 'stock' => 10, 'active' => true],
    ];

    foreach ($productsData as $idx => $item) {
        try {
            $item['createdAt'] = new DateTime();
            $item['updatedAt'] = new DateTime();
            if (setDocument($projectId, $accessToken, 'products', 'product-' . ($idx + 1), $item)) {
                $results['products']++;
            }
        } catch (Exception $e) {
            $results['errors'][] = "Product " . ($idx + 1) . ": " . $e->getMessage();
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'W114 data seeding completed',
        'results' => $results,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
