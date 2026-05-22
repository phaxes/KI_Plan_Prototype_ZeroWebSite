<?php

namespace App\Controllers;

use DateTime;
use App\Config;
use App\FirestoreRest;

class AdminSeedController
{
    public function seedW114Data($params = [], $post = [], $get = [])
    {
        header('Content-Type: application/json');

        // Security check - only allow in development or with admin key
        $adminKey = $get['key'] ?? $post['key'] ?? null;
        if ($adminKey !== 'W114_SEED_2024_RENDER') {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        try {
            $projectId = Config::get('FIREBASE_PROJECT_ID');
            $serviceAccountJson = Config::get('FIREBASE_SERVICE_ACCOUNT_JSON');

            if (!$projectId || !$serviceAccountJson) {
                throw new \Exception('Firebase not configured');
            }

            if (file_exists($serviceAccountJson)) {
                $serviceAccountJson = file_get_contents($serviceAccountJson);
            }

            $firestore = FirestoreRest::getInstance($projectId, $serviceAccountJson);

            $results = [
                'news' => 0,
                'blog' => 0,
                'products' => 0,
                'errors' => []
            ];

            // News Articles
            $newsData = $this->getNewsArticles();
            foreach ($newsData as $index => $article) {
                try {
                    $article['createdAt'] = new DateTime();
                    $article['updatedAt'] = new DateTime();
                    $docId = 'news-' . ($index + 1);
                    $firestore->setDocument('news', $docId, $article);
                    $results['news']++;
                } catch (\Exception $e) {
                    $results['errors'][] = "News " . ($index + 1) . ": " . $e->getMessage();
                }
            }

            // Blog Posts
            $blogData = $this->getBlogPosts();
            foreach ($blogData as $index => $post) {
                try {
                    $post['createdAt'] = new DateTime();
                    $post['updatedAt'] = new DateTime();
                    $docId = 'blog-' . ($index + 1);
                    $firestore->setDocument('blog', $docId, $post);
                    $results['blog']++;
                } catch (\Exception $e) {
                    $results['errors'][] = "Blog " . ($index + 1) . ": " . $e->getMessage();
                }
            }

            // Products
            $productsData = $this->getProducts();
            foreach ($productsData as $index => $product) {
                try {
                    $product['createdAt'] = new DateTime();
                    $product['updatedAt'] = new DateTime();
                    $docId = 'product-' . ($index + 1);
                    $firestore->setDocument('products', $docId, $product);
                    $results['products']++;
                } catch (\Exception $e) {
                    $results['errors'][] = "Product " . ($index + 1) . ": " . $e->getMessage();
                }
            }

            echo json_encode([
                'success' => true,
                'message' => 'W114 data seeding completed',
                'results' => $results
            ]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function getNewsArticles()
    {
        return [
            [
                'title' => 'Rare 1971 Mercedes W114 250 US Sedan Discovered in California Garage',
                'content' => 'A pristine 1971 Mercedes-Benz W114 250 Limousine with the rare 2.8L M130 engine has been discovered in a private collection in Los Angeles. This exceptional example features the authentic sealed-beam headlamp configuration specific to the American market, along with the legendary 4-speed automatic transmission. With only 42,000 original miles, this vehicle represents the pinnacle of early 1970s Mercedes engineering.',
                'type' => 'news',
                'published' => true,
                'imageUrl' => 'https://images.unsplash.com/photo-1605559424843-9e4c3ca3806d?w=800&q=80',
                'tags' => ['W114', '1971', 'Mercedes', 'Discovery'],
                'authorId' => 'editor'
            ],
            [
                'title' => 'W114 Market Values Continue to Rise in 2024',
                'content' => 'Classic.com reports a 12% increase in Mercedes W114 valuation over the past 12 months. The 1971 models are particularly sought after by collectors, with average prices reaching $17,247 for solid examples. Experts attribute this growth to increasing rarity and the model\'s reputation for reliability and durability.',
                'type' => 'news',
                'published' => true,
                'imageUrl' => 'https://images.unsplash.com/photo-1552820728-8ac41f1ce891?w=800&q=80',
                'tags' => ['Market', 'Valuation', 'Investment'],
                'authorId' => 'editor'
            ],
            [
                'title' => 'Comprehensive Restoration Guide: 1971 W114 US Models',
                'content' => 'Our team has compiled the definitive restoration resource for 1971 Mercedes W114 US models. This guide covers engine rebuilding, electrical system modernization, interior restoration, and authentic period-correct exterior refinishing. Whether you\'re tackling a complete restoration or maintenance updates, this comprehensive resource covers all aspects specific to American-market vehicles.',
                'type' => 'news',
                'published' => true,
                'imageUrl' => 'https://images.unsplash.com/photo-1486262715619-67b519e0edd3?w=800&q=80',
                'tags' => ['Restoration', 'Guide', 'W114'],
                'authorId' => 'editor'
            ],
            [
                'title' => 'Sealed Beam Headlamp Restoration - Preserve Your W114\'s Authenticity',
                'content' => 'US-market 1971 W114 models featured distinctive sealed-beam headlamp assemblies. Unlike European models, American variants incorporated larger turn signal indicators below the headlamps. Modern restoration techniques now allow collectors to restore these original components while improving reliability and light output.',
                'type' => 'news',
                'published' => true,
                'imageUrl' => 'https://images.unsplash.com/photo-1549399542-7e3f8b83ad38?w=800&q=80',
                'tags' => ['W114', 'Restoration', 'Authenticity'],
                'authorId' => 'editor'
            ],
            [
                'title' => 'Engine Swap Analysis: M114 vs M130 in 1971 Models',
                'content' => 'The 1971 model year marked a significant transition for US-market W114s. Mercedes replaced the 2.5L M114 engine with the more robust 2.8L M130, a response to increasingly stringent emission standards. This technical article examines the differences, performance implications, and what these engines mean for today\'s collectors and restorers.',
                'type' => 'news',
                'published' => true,
                'imageUrl' => 'https://images.unsplash.com/photo-1470225620780-dba8ba36b745?w=800&q=80',
                'tags' => ['Engine', 'Technical', 'M130'],
                'authorId' => 'editor'
            ],
            [
                'title' => 'W114 Paint Color Evolution: Original 1971 US Specifications',
                'content' => 'Mercedes offered an exceptional palette of colors for 1971 W114 models destined for America. From elegant silvers to bold metallic hues, each color tells a story about the era\'s automotive design philosophy. This article documents the authentic color options, including rare metallic variations and their proper reproduction for restoration projects.',
                'type' => 'news',
                'published' => true,
                'imageUrl' => 'https://images.unsplash.com/photo-1487730116645-74489c95b41b?w=800&q=80',
                'tags' => ['Paint', 'Colors', 'Restoration'],
                'authorId' => 'editor'
            ],
            [
                'title' => 'Record Sale: 1971 W114 Coupe Fetches $24,500 at Auction',
                'content' => 'A beautifully restored 1971 Mercedes W114 250 C Coupe with documented history sold for $24,500 at a European auction. The vehicle\'s exceptional condition, low mileage (31,000 miles), and original factory specifications made it highly desirable among serious collectors. Market analysts predict continued appreciation for well-maintained examples.',
                'type' => 'news',
                'published' => true,
                'imageUrl' => 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?w=800&q=80',
                'tags' => ['Auction', 'Investment', 'Market'],
                'authorId' => 'editor'
            ],
            [
                'title' => 'W114 New Old Stock Parts: What\'s Still Available',
                'content' => 'As W114 models age, finding authentic NOS (New Old Stock) parts becomes increasingly valuable. Our investigation reveals which components are still available through official Mercedes dealers and specialized suppliers. Learn which parts appreciate fastest and which are critical to source before they disappear entirely.',
                'type' => 'news',
                'published' => true,
                'imageUrl' => 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=800&q=80',
                'tags' => ['NOS', 'Parts', 'Availability'],
                'authorId' => 'editor'
            ],
            [
                'title' => 'Climate Control Systems: Original vs Aftermarket for 1971 Models',
                'content' => 'The 1971 W114 featured sophisticated climate control for its era. As these systems age, owners face the choice between authentic restoration and modern upgrades. This technical guide explores both approaches, examining reliability, efficiency, and how each decision impacts authenticity and resale value.',
                'type' => 'news',
                'published' => true,
                'imageUrl' => 'https://images.unsplash.com/photo-1489824904134-891ab64532f1?w=800&q=80',
                'tags' => ['Climate', 'Technical', 'Restoration'],
                'authorId' => 'editor'
            ],
            [
                'title' => 'International Shipping: Moving Your W114 from Europe to America',
                'content' => 'Importing a European W114 to the United States presents unique challenges and opportunities. This comprehensive guide covers customs regulations, EPA compliance considerations, shipping logistics, and the cost-benefit analysis of importing versus purchasing domestically. Learn from collectors who have successfully navigated this process.',
                'type' => 'news',
                'published' => true,
                'imageUrl' => 'https://images.unsplash.com/photo-1487958449943-edee5a9ae3c9?w=800&q=80',
                'tags' => ['Import', 'Logistics', 'International'],
                'authorId' => 'editor'
            ]
        ];
    }

    private function getBlogPosts()
    {
        return [
            [
                'title' => 'The Story Behind Mercedes\' "Strich Acht" - How a Nickname Became Legend',
                'content' => 'The Mercedes-Benz W114 became known as the "Strich Acht" (stroke eight) from the "/" designation Mercedes used to identify 1968-1976 models. This simple nomenclature concealed a revolutionary approach to mid-range luxury. The W114 represented Mercedes\' response to an increasingly competitive market while maintaining the brand\'s reputation for engineering excellence. In the American market, these cars faced unique challenges: new emission standards, changing consumer preferences, and the need to compete with domestic luxury offerings. The 1971 update, featuring the more powerful 2.8L M130 engine, exemplified Mercedes\' commitment to performance despite regulatory constraints. Today, the W114 stands as a testament to an era when engineering integrity trumped short-term profits.',
                'type' => 'blog',
                'published' => true,
                'imageUrl' => 'https://images.unsplash.com/photo-1552820728-8ac41f1ce891?w=800&q=80',
                'tags' => ['History', 'W114', 'Mercedes', 'Design'],
                'authorId' => 'editor'
            ],
            [
                'title' => 'Collecting Classic Mercedes: A 1971 W114 Owner\'s Perspective',
                'content' => 'After five years of ownership, I\'ve learned invaluable lessons about preserving a 1971 Mercedes W114 in original condition. The journey from acquisition through restoration has been transformative. What initially seemed like straightforward maintenance revealed layers of German engineering elegance. The 2.8L M130 engine, with its cast-iron block and robust internals, continues to deliver smooth, reliable power. The four-speed automatic transmission, while simple by modern standards, demonstrates perfect tuning for the engine\'s characteristics. The suspension geometry remains comfortable and responsive, a far cry from the harsh ride of contemporary American competitors. For collectors, these cars represent extraordinary value and cultural significance.',
                'type' => 'blog',
                'published' => true,
                'imageUrl' => 'https://images.unsplash.com/photo-1605559424843-9e4c3ca3806d?w=800&q=80',
                'tags' => ['Ownership', 'Experience', 'Restoration'],
                'authorId' => 'editor'
            ],
            [
                'title' => 'American Market Specifics: What Made 1971 W114 Models Unique',
                'content' => 'The US market demanded different solutions than European customers. Sealed-beam headlamps, modified bumper configurations, and emission-compliant engines were just the beginning. American regulatory bodies imposed increasingly stringent requirements, pushing Mercedes engineers to innovate. The 1971 model year transition from the 2.5L M114 to the 2.8L M130 engine wasn\'t a luxury—it was necessary engineering. These American-specific W114s, often overlooked by European enthusiasts, represent genuine automotive solutions to real-world problems.',
                'type' => 'blog',
                'published' => true,
                'imageUrl' => 'https://images.unsplash.com/photo-1487730116645-74489c95b41b?w=800&q=80',
                'tags' => ['American Market', 'Specifications', 'Unique Features'],
                'authorId' => 'editor'
            ],
            [
                'title' => 'Maintenance Secrets: Keeping Your W114 Running Strong',
                'content' => 'Fifty-year-old engineering requires understanding, respect, and proper maintenance. The W114, despite its age, rewards owners who approach maintenance systematically. Regular oil changes, proper fuel system maintenance, and careful attention to cooling systems ensure longevity. The Solex carburetor, original to many 1971 models, responds beautifully to skilled tuning and maintenance. Electronic systems, primitive by modern standards, prove remarkably reliable when properly cared for.',
                'type' => 'blog',
                'published' => true,
                'imageUrl' => 'https://images.unsplash.com/photo-1486262715619-67b519e0edd3?w=800&q=80',
                'tags' => ['Maintenance', 'Technical', 'Longevity'],
                'authorId' => 'editor'
            ],
            [
                'title' => 'The 2.8L M130 Engine: Mercedes\' Answer to Emission Standards',
                'content' => 'By 1971, emission regulations forced automotive manufacturers worldwide to reconsider engine design philosophy. Mercedes\' response was the M130, a 2.8L six-cylinder that replaced the smaller M114 in American-market W114 models. This wasn\'t merely a displacement increase—it represented thoughtful engineering. The larger engine, while consuming slightly more fuel, maintained drivability and performance despite new emissions equipment.',
                'type' => 'blog',
                'published' => true,
                'imageUrl' => 'https://images.unsplash.com/photo-1470225620780-dba8ba36b745?w=800&q=80',
                'tags' => ['Engine', 'M130', 'Technical'],
                'authorId' => 'editor'
            ],
            [
                'title' => 'Interior Restoration: Bringing 1971 Elegance Back to Life',
                'content' => 'The 1971 W114 interior design epitomized understated elegance. Soft-touch plastics, quality leather seats, and thoughtful ergonomics created an environment that aged surprisingly well. Restoration begins with careful assessment: original upholstery can sometimes be preserved with expert cleaning, while heavily worn examples require reproduction materials.',
                'type' => 'blog',
                'published' => true,
                'imageUrl' => 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=800&q=80',
                'tags' => ['Interior', 'Restoration', 'Design'],
                'authorId' => 'editor'
            ],
            [
                'title' => 'Investment Potential: Why Collectors Are Buying W114 Models Now',
                'content' => 'Financial analysts tracking classic car markets note accelerating interest in 1971 Mercedes W114 models. Unlike speculative trends that vanish quickly, W114 appreciation reflects fundamental factors: limited supply of quality examples, increasing recognition of design excellence, and appreciation for mechanical simplicity. A well-maintained 1971 W114 250 appreciates approximately 8-12% annually.',
                'type' => 'blog',
                'published' => true,
                'imageUrl' => 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?w=800&q=80',
                'tags' => ['Investment', 'Market', 'Value'],
                'authorId' => 'editor'
            ],
            [
                'title' => 'Finding Your Perfect 1971 W114: What to Look For',
                'content' => 'Purchasing a classic 1971 Mercedes W114 demands careful evaluation. Begin with documentation: service records, original purchase receipts, and maintenance history trump everything. Examine the body for rust, particularly in Mercedes\' notorious weak points—the floor pans, door bottoms, and around the rear wheel wells.',
                'type' => 'blog',
                'published' => true,
                'imageUrl' => 'https://images.unsplash.com/photo-1489824904134-891ab64532f1?w=800&q=80',
                'tags' => ['Buying Guide', 'Inspection', 'Tips'],
                'authorId' => 'editor'
            ],
            [
                'title' => 'W114 Community: Connecting with Fellow Enthusiasts',
                'content' => 'The W114 community, while smaller than Porsche or BMW clubs, demonstrates remarkable passion and expertise. Online forums dedicated to classic Mercedes connect owners worldwide, facilitating parts sourcing, technical advice, and event organization. Regional clubs organize rallies where W114s gather alongside other classic Mercedes models.',
                'type' => 'blog',
                'published' => true,
                'imageUrl' => 'https://images.unsplash.com/photo-1487958449943-edee5a9ae3c9?w=800&q=80',
                'tags' => ['Community', 'Clubs', 'Enthusiasts'],
                'authorId' => 'editor'
            ]
        ];
    }

    private function getProducts()
    {
        return [
            ['name' => 'Ignition Coil OEM Mercedes-Benz (272-906-00-60)', 'description' => 'Authentic Mercedes-Benz OEM ignition coil for 1971 W114 models. New Old Stock condition. Manufactured to original specifications with proper voltage regulation and dwell characteristics for the M130 engine. Includes original packaging.', 'price' => 145.00, 'sku' => 'MERC-272-906-00-60-NOS', 'category' => 'Engine - Electrical', 'image' => 'https://images.unsplash.com/photo-1513828583688-c52646db42da?w=500&q=80', 'stock' => 3, 'active' => true],
            ['name' => 'Solex Carburetor Rebuild Kit (Complete)', 'description' => 'Complete NOS rebuild kit for authentic Solex carburetor found on 1971 W114 250 models. Includes gaskets, seals, jets, and needle valves. German-made quality with original Mercedes specifications. Professional restoration required.', 'price' => 89.95, 'sku' => 'SOLEX-REBUILD-1971-W114', 'category' => 'Engine - Fuel System', 'image' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=500&q=80', 'stock' => 5, 'active' => true],
            ['name' => 'Thermostat Housing Assembly OEM', 'description' => 'Original Mercedes-Benz thermostat housing for 1971 W114 250 with 2.8L M130 engine. New Old Stock with original seals. Direct replacement, no modifications required.', 'price' => 67.50, 'sku' => 'MERC-THERMO-HOUSING-71', 'category' => 'Engine - Cooling', 'image' => 'https://images.unsplash.com/photo-1552820728-8ac41f1ce891?w=500&q=80', 'stock' => 2, 'active' => true],
            ['name' => 'Water Pump Gasket Set NOS', 'description' => 'Authentic Mercedes NOS water pump gasket set for 1971-1975 W114 models. Premium quality cork and rubber construction. Prevents coolant leaks and ensures proper engine cooling performance.', 'price' => 34.95, 'sku' => 'MERC-WATER-PUMP-GASKET', 'category' => 'Engine - Cooling', 'image' => 'https://images.unsplash.com/photo-1487730116645-74489c95b41b?w=500&q=80', 'stock' => 8, 'active' => true],
            ['name' => 'Spark Plug Set (6) - Original Specification', 'description' => 'Complete set of 6 spark plugs matching 1971 W114 original equipment specifications. Premium construction with exact heat range for the M130 engine. Installation includes proper gap settings.', 'price' => 42.00, 'sku' => 'MERC-SPARK-PLUGS-6PC', 'category' => 'Engine - Ignition', 'image' => 'https://images.unsplash.com/photo-1465056836643-15cea6d2f840?w=500&q=80', 'stock' => 12, 'active' => true],
            ['name' => 'Alternator - Original Mercedes Bosch Design', 'description' => 'Replacement alternator identical to original 1971 W114 specifications. Bosch manufacture with proper amperage output (55A) for charging system compatibility. Remanufactured to like-new condition.', 'price' => 156.00, 'sku' => 'BOSCH-ALT-W114-55A', 'category' => 'Electrical - Charging', 'image' => 'https://images.unsplash.com/photo-1486262715619-67b519e0edd3?w=500&q=80', 'stock' => 2, 'active' => true],
            ['name' => 'Battery Terminal Clamps - Copper Plated Set', 'description' => 'Authentic reproduction battery terminal clamps with copper plating for 1971 W114. Prevents corrosion and ensures reliable electrical connections. Includes both positive and negative clamps.', 'price' => 28.50, 'sku' => 'MERC-BATT-CLAMPS-COPPER', 'category' => 'Electrical - Battery', 'image' => 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=500&q=80', 'stock' => 15, 'active' => true],
            ['name' => 'Wiper Motor - 1971 W114 Specification', 'description' => 'OEM specification wiper motor for 1971 Mercedes W114. Two-speed operation with proper torque for original wiper blade resistance. Fully tested and functional.', 'price' => 94.75, 'sku' => 'MERC-WIPER-MOTOR-2SPD', 'category' => 'Electrical - Wipers', 'image' => 'https://images.unsplash.com/photo-1470225620780-dba8ba36b745?w=500&q=80', 'stock' => 4, 'active' => true],
            ['name' => 'Light Bulb Set - Sealed Beam Headlamps', 'description' => 'Authentic sealed-beam headlamp bulbs specific to 1971 US-market W114 models. High-quality construction with proper light output and beam pattern for American driving conditions.', 'price' => 56.00, 'sku' => 'SEALED-BEAM-H4-PAIR', 'category' => 'Electrical - Lighting', 'image' => 'https://images.unsplash.com/photo-1549399542-7e3f8b83ad38?w=500&q=80', 'stock' => 8, 'active' => true],
            ['name' => 'Instrument Cluster Bulb Set (8 pieces)', 'description' => 'Complete set of dashboard instrument cluster bulbs for 1971 W114. Proper wattage and base configuration for all gauge illumination and warning lights. Original specification replacements.', 'price' => 24.95, 'sku' => 'MERC-CLUSTER-BULBS-8PC', 'category' => 'Electrical - Lighting', 'image' => 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=500&q=80', 'stock' => 20, 'active' => true],
            ['name' => 'Front Wheel Bearing Set - Complete Assembly', 'description' => 'OEM-specification front wheel bearing assemblies for 1971 W114. Includes bearings, races, and seals. Precision manufacturing ensures smooth, reliable wheel rotation. Pair pricing for both wheels.', 'price' => 78.50, 'sku' => 'MERC-WHEEL-BEARING-FRONT', 'category' => 'Suspension', 'image' => 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?w=500&q=80', 'stock' => 3, 'active' => true],
            ['name' => 'Brake Pad Set - Metallic Compound', 'description' => 'Premium brake pad set for 1971 W114 front disc brakes. OEM-equivalent metallic compound offers improved braking performance and longevity. Direct fit, no modifications required.', 'price' => 64.00, 'sku' => 'MERC-BRAKE-PADS-FRONT', 'category' => 'Brakes', 'image' => 'https://images.unsplash.com/photo-1487960412217-8148a778289c?w=500&q=80', 'stock' => 6, 'active' => true],
            ['name' => 'Shock Absorber Pair - Original Specification', 'description' => 'Replacement shock absorbers (pair) for 1971 W114 front suspension. Engineered to match original Mercedes damping characteristics. Gas-pressurized design provides superior performance over aging original shocks.', 'price' => 156.00, 'sku' => 'MERC-SHOCK-PAIR-FRONT', 'category' => 'Suspension', 'image' => 'https://images.unsplash.com/photo-1489824904134-891ab64532f1?w=500&q=80', 'stock' => 2, 'active' => true],
            ['name' => 'Steering Rack Bellows Kit - Reproduction', 'description' => 'Reproduction steering rack bellows (dust boots) for 1971 W114. Protects rack from contamination and moisture. Authentic appearance with OEM-quality rubber compound.', 'price' => 45.75, 'sku' => 'MERC-STEERING-BELLOWS', 'category' => 'Steering', 'image' => 'https://images.unsplash.com/photo-1487958449943-edee5a9ae3c9?w=500&q=80', 'stock' => 7, 'active' => true],
            ['name' => 'Weatherstripping Set - Door and Window', 'description' => 'Complete weatherstripping set for 1971 W114 doors and windows. EPDM rubber matches original Mercedes specification. Prevents water leaks and improves climate control efficiency. Installation required.', 'price' => 87.50, 'sku' => 'MERC-WEATHERSTRIP-KIT', 'category' => 'Interior - Weathersealing', 'image' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=500&q=80', 'stock' => 4, 'active' => true],
            ['name' => 'Instrument Cluster Lens - Original Plexiglass', 'description' => 'Replacement instrument cluster lens for 1971 W114. UV-resistant plexiglass resists yellowing. Exact reproduction of original, includes mounting hardware. Ready to install.', 'price' => 38.00, 'sku' => 'MERC-CLUSTER-LENS', 'category' => 'Interior - Instruments', 'image' => 'https://images.unsplash.com/photo-1494976866556-6812c9d1c72e?w=500&q=80', 'stock' => 5, 'active' => true],
            ['name' => 'Floor Mat Set - Wool Blend Authentic Pattern', 'description' => 'Complete floor mat set for 1971 W114 in authentic wool blend with original Mercedes pattern. Colors match factory specifications. Rubber backing prevents slipping. Four pieces.', 'price' => 156.00, 'sku' => 'MERC-FLOOR-MATS-4PC', 'category' => 'Interior - Trim', 'image' => 'https://images.unsplash.com/photo-1487730116645-74489c95b41b?w=500&q=80', 'stock' => 3, 'active' => true],
            ['name' => 'Chrome Trim Restoration Kit - Bumpers', 'description' => 'Professional chrome restoration system for 1971 W114 bumpers and trim. Includes cleaner, polish, and protective coating. Restores original shine to pitted or oxidized chrome.', 'price' => 52.00, 'sku' => 'CHROME-RESTORE-KIT', 'category' => 'Exterior - Chrome', 'image' => 'https://images.unsplash.com/photo-1486262715619-67b519e0edd3?w=500&q=80', 'stock' => 12, 'active' => true],
            ['name' => 'Rubber Gasket Set - Doors Complete', 'description' => 'Complete rubber door gasket set for 1971 W114. OEM-specification EPDM rubber prevents water intrusion and wind noise. All four doors included. Professional installation recommended.', 'price' => 94.50, 'sku' => 'MERC-DOOR-GASKET-SET', 'category' => 'Exterior - Sealing', 'image' => 'https://images.unsplash.com/photo-1552820728-8ac41f1ce891?w=500&q=80', 'stock' => 2, 'active' => true]
        ];
    }
}
?>
