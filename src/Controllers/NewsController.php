<?php

namespace App\Controllers;

use App\Firebase;

class NewsController
{
    public function index($params = [], $post = [], $get = [])
    {
        $page = intval($get['page'] ?? 1);
        $limit = 9;
        $offset = ($page - 1) * $limit;

        $news = Firebase::getPosts('news', true, $limit, $offset);
        $title = 'News';
        $pageTitle = 'Neueste News';

        ob_start();
        ?>
        <section class="py-12">
            <div class="container mx-auto px-4">
                <h1 class="text-4xl font-bold mb-8"><?= htmlspecialchars($pageTitle) ?></h1>

                <?php if (!empty($news)): ?>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
                        <?php foreach ($news as $article): ?>
                            <article class="bg-white rounded shadow hover:shadow-lg transition overflow-hidden">
                                <?php if (!empty($article['imageUrl'])): ?>
                                    <img src="<?= htmlspecialchars($article['imageUrl']) ?>" alt="News" class="w-full h-40 object-cover">
                                <?php else: ?>
                                    <div class="w-full h-40 bg-gradient-to-r from-primary to-secondary"></div>
                                <?php endif; ?>
                                <div class="p-6">
                                    <time class="text-gray-500 text-sm">
                                        <?= $article['createdAt']->format('d.m.Y H:i') ?? date('d.m.Y') ?>
                                    </time>
                                    <h3 class="text-xl font-bold mt-2 mb-2 line-clamp-2"><?= htmlspecialchars($article['title']) ?></h3>
                                    <p class="text-gray-600 line-clamp-2 mb-4"><?= htmlspecialchars(substr(strip_tags($article['content']), 0, 100)) ?>...</p>
                                    <?php if (!empty($article['tags'])): ?>
                                        <div class="flex gap-2 flex-wrap mb-4">
                                            <?php foreach ($article['tags'] as $tag): ?>
                                                <span class="text-xs bg-gray-100 px-2 py-1 rounded"><?= htmlspecialchars($tag) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    <a href="/news/<?= htmlspecialchars($article['id']) ?>" class="text-primary hover:text-blue-600 transition font-semibold">Weiterlesen →</a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <div class="flex justify-center gap-2 mt-12">
                        <?php if ($page > 1): ?>
                            <a href="/news?page=<?= $page - 1 ?>" class="px-4 py-2 border rounded hover:bg-gray-100">← Zurück</a>
                        <?php endif; ?>
                        <span class="px-4 py-2">Seite <?= $page ?></span>
                        <?php if (count($news) >= $limit): ?>
                            <a href="/news?page=<?= $page + 1 ?>" class="px-4 py-2 border rounded hover:bg-gray-100">Weiter →</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="bg-gray-100 p-12 rounded text-center">
                        <p class="text-gray-600 text-lg">Noch keine News verfügbar</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
        <?php
        $content = ob_get_clean();
        require __DIR__ . '/../../templates/layouts/base.php';
    }

    public function show($params = [], $post = [], $get = [])
    {
        $postId = $params['id'] ?? null;

        if (!$postId) {
            http_response_code(404);
            require __DIR__ . '/../../templates/errors/404.php';
            return;
        }

        $article = Firebase::getPostById($postId);

        if (!$article || $article['type'] !== 'news' || !$article['published']) {
            http_response_code(404);
            require __DIR__ . '/../../templates/errors/404.php';
            return;
        }

        $title = htmlspecialchars($article['title']);
        $pageTitle = 'News';

        ob_start();
        ?>
        <section class="py-12">
            <div class="container mx-auto px-4 max-w-3xl">
                <a href="/news" class="text-primary hover:text-blue-600 transition mb-8 inline-block">← Zurück zu News</a>

                <article>
                    <?php if (!empty($article['imageUrl'])): ?>
                        <img src="<?= htmlspecialchars($article['imageUrl']) ?>" alt="News" class="w-full rounded shadow mb-8">
                    <?php else: ?>
                        <div class="w-full h-96 bg-gradient-to-r from-primary to-secondary rounded mb-8"></div>
                    <?php endif; ?>

                    <h1 class="text-4xl font-bold mb-4"><?= htmlspecialchars($article['title']) ?></h1>

                    <div class="flex gap-4 items-center text-gray-600 mb-8 flex-wrap">
                        <time><?= $article['createdAt']->format('d.m.Y H:i') ?? date('d.m.Y') ?></time>
                        <?php if (!empty($article['authorId'])): ?>
                            <span>von <strong><?= htmlspecialchars($article['authorId']) ?></strong></span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($article['tags'])): ?>
                        <div class="flex gap-2 flex-wrap mb-8">
                            <?php foreach ($article['tags'] as $tag): ?>
                                <span class="text-xs bg-gray-200 px-3 py-1 rounded"><?= htmlspecialchars($tag) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="prose max-w-none mb-8">
                        <?= nl2br(htmlspecialchars($article['content'])) ?>
                    </div>

                    <div class="border-t pt-8">
                        <a href="/news" class="bg-primary text-white px-6 py-3 rounded hover:bg-blue-600 transition">Alle News</a>
                    </div>
                </article>
            </div>
        </section>
        <?php
        $content = ob_get_clean();
        require __DIR__ . '/../../templates/layouts/base.php';
    }
}
