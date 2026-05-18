<?php

namespace App\Controllers\Admin;

use App\Firebase;

class DashboardController
{
    public function index($params = [], $post = [], $get = [])
    {
        // Require admin
        \App\Middleware\AuthMiddleware::requireAdmin();

        $news = Firebase::getPosts('news', null, 5, 0);
        $blog = Firebase::getPosts('blog', null, 5, 0);
        $products = Firebase::getProducts(false, 5, 0);
        $newsCount = count($news);
        $blogCount = count($blog);
        $productCount = count($products);

        $title = 'Admin Dashboard';
        $pageTitle = 'Dashboard';

        ob_start();
        ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded shadow p-6">
                <div class="text-4xl font-bold text-primary mb-2"><?= $newsCount ?></div>
                <p class="text-gray-600">News-Artikel</p>
                <a href="/admin/news" class="text-primary hover:text-blue-600 transition text-sm mt-2 inline-block">Verwalten →</a>
            </div>
            <div class="bg-white rounded shadow p-6">
                <div class="text-4xl font-bold text-secondary mb-2"><?= $blogCount ?></div>
                <p class="text-gray-600">Blog-Artikel</p>
                <a href="/admin/blog" class="text-primary hover:text-blue-600 transition text-sm mt-2 inline-block">Verwalten →</a>
            </div>
            <div class="bg-white rounded shadow p-6">
                <div class="text-4xl font-bold text-accent mb-2"><?= $productCount ?></div>
                <p class="text-gray-600">Produkte</p>
                <a href="/admin/products" class="text-primary hover:text-blue-600 transition text-sm mt-2 inline-block">Verwalten →</a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Recent News -->
            <div class="bg-white rounded shadow p-6">
                <h2 class="text-xl font-bold mb-4">Neueste News</h2>
                <div class="space-y-3">
                    <?php if (!empty($news)): ?>
                        <?php foreach ($news as $item): ?>
                            <div class="p-3 border rounded hover:bg-gray-50 transition">
                                <p class="font-semibold line-clamp-1"><?= htmlspecialchars($item['title']) ?></p>
                                <p class="text-sm text-gray-600">
                                    <?= $item['published'] ? '✅ Veröffentlicht' : '❌ Entwurf' ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-gray-600">Keine News vorhanden</p>
                    <?php endif; ?>
                </div>
                <a href="/admin/news/create" class="mt-4 inline-block bg-primary text-white px-4 py-2 rounded hover:bg-blue-600 transition">+ Neue News</a>
            </div>

            <!-- Recent Blog Posts -->
            <div class="bg-white rounded shadow p-6">
                <h2 class="text-xl font-bold mb-4">Neueste Blog-Artikel</h2>
                <div class="space-y-3">
                    <?php if (!empty($blog)): ?>
                        <?php foreach ($blog as $item): ?>
                            <div class="p-3 border rounded hover:bg-gray-50 transition">
                                <p class="font-semibold line-clamp-1"><?= htmlspecialchars($item['title']) ?></p>
                                <p class="text-sm text-gray-600">
                                    <?= $item['published'] ? '✅ Veröffentlicht' : '❌ Entwurf' ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-gray-600">Keine Artikel vorhanden</p>
                    <?php endif; ?>
                </div>
                <a href="/admin/blog/create" class="mt-4 inline-block bg-primary text-white px-4 py-2 rounded hover:bg-blue-600 transition">+ Neuer Artikel</a>
            </div>
        </div>
        <?php
        $content = ob_get_clean();
        require __DIR__ . '/../../templates/layouts/admin.php';
    }
}
