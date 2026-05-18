<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Admin - ZeroWeb' ?></title>
    <link rel="stylesheet" href="/public/css/app.css">
    <!-- Firebase SDK -->
    <script src="https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js"></script>
    <script src="https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js"></script>
    <script src="https://www.gstatic.com/firebasejs/10.7.1/firebase-firestore.js"></script>
    <!-- SimpleMDE for editor -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/simplemde/latest/simplemde.min.css">
    <script src="https://cdn.jsdelivr.net/simplemde/latest/simplemde.min.js"></script>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-gray-900 text-white p-6 overflow-y-auto">
            <h2 class="text-2xl font-bold mb-8">Admin Panel</h2>
            <nav class="space-y-2">
                <a href="/admin" class="block px-4 py-2 rounded hover:bg-gray-800 transition">Dashboard</a>
                <div class="pt-4">
                    <p class="text-xs uppercase text-gray-400 font-semibold px-4 mb-2">Content</p>
                    <a href="/admin/news" class="block px-4 py-2 rounded hover:bg-gray-800 transition">News</a>
                    <a href="/admin/blog" class="block px-4 py-2 rounded hover:bg-gray-800 transition">Blog</a>
                </div>
                <div class="pt-4">
                    <p class="text-xs uppercase text-gray-400 font-semibold px-4 mb-2">Shop</p>
                    <a href="/admin/products" class="block px-4 py-2 rounded hover:bg-gray-800 transition">Produkte</a>
                </div>
                <div class="pt-4">
                    <p class="text-xs uppercase text-gray-400 font-semibold px-4 mb-2">Marketing</p>
                    <a href="/admin/subscribers" class="block px-4 py-2 rounded hover:bg-gray-800 transition">Newsletter</a>
                </div>
                <div class="pt-8 border-t border-gray-700">
                    <a href="/" class="block px-4 py-2 rounded hover:bg-gray-800 transition">Zur Website</a>
                    <a href="/logout" class="block px-4 py-2 rounded hover:bg-gray-800 transition">Logout</a>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 overflow-auto">
            <!-- Top Bar -->
            <header class="bg-white shadow p-6">
                <div class="flex justify-between items-center">
                    <h1 class="text-3xl font-bold"><?= $pageTitle ?? 'Admin' ?></h1>
                    <div class="text-sm text-gray-600">
                        <?php if (isset($_SESSION['email'])): ?>
                            Willkommen, <strong><?= htmlspecialchars($_SESSION['email']) ?></strong>
                        <?php endif; ?>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <section class="p-6">
                <?= $content ?? '' ?>
            </section>
        </main>
    </div>

    <script src="/public/js/app.js"></script>
    <script src="/public/js/firebase-init.js"></script>
    <script src="/public/js/auth.js"></script>
</body>
</html>
