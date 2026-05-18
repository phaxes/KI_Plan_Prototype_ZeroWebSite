<?php

namespace App\Controllers\Admin;

use App\Firebase;

class NewsAdminController
{
    protected function checkAdmin()
    {
        if (!isset($_SESSION['isAdmin']) || !$_SESSION['isAdmin']) {
            header('Location: /login');
            exit;
        }
    }

    public function index($params = [], $post = [], $get = [])
    {
        $this->checkAdmin();

        $page = intval($get['page'] ?? 1);
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $news = Firebase::getPosts('news', null, $limit, $offset);
        $title = 'News Management';
        $pageTitle = 'News verwalten';

        ob_start();
        ?>
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold"><?= htmlspecialchars($pageTitle) ?></h1>
            <a href="/admin/news/create" class="bg-primary text-white px-6 py-2 rounded hover:bg-blue-600 transition">+ Neue News</a>
        </div>

        <div class="bg-white rounded shadow overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-100 border-b">
                    <tr>
                        <th class="px-6 py-3 text-left">Titel</th>
                        <th class="px-6 py-3 text-left">Status</th>
                        <th class="px-6 py-3 text-left">Datum</th>
                        <th class="px-6 py-3 text-right">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($news)): ?>
                        <?php foreach ($news as $item): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-6 py-4"><?= htmlspecialchars(substr($item['title'], 0, 50)) ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded text-sm <?= $item['published'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                        <?= $item['published'] ? '✅ Veröffentlicht' : '❌ Entwurf' ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600"><?= $item['createdAt']->format('d.m.Y H:i') ?? date('d.m.Y') ?></td>
                                <td class="px-6 py-4 text-right">
                                    <a href="/admin/news/<?= htmlspecialchars($item['id']) ?>/edit" class="text-primary hover:text-blue-600 transition text-sm mr-4">Bearbeiten</a>
                                    <button onclick="deleteNews('<?= htmlspecialchars($item['id']) ?>')" class="text-red-600 hover:text-red-700 transition text-sm">Löschen</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-gray-600">Keine News vorhanden</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <script>
            function deleteNews(id) {
                if (confirm('News wirklich löschen?')) {
                    // Delete logic will be implemented
                    alert('Löschen wird noch implementiert');
                }
            }
        </script>
        <?php
        $content = ob_get_clean();
        require __DIR__ . '/../../templates/layouts/admin.php';
    }

    public function create($params = [], $post = [], $get = [])
    {
        $this->checkAdmin();
        $title = 'Neue News';
        $pageTitle = 'Neue News erstellen';
        $article = null;

        ob_start();
        ?>
        <div class="max-w-3xl">
            <h1 class="text-3xl font-bold mb-8"><?= htmlspecialchars($pageTitle) ?></h1>

            <form id="news-form" method="POST" action="/admin/news/store" class="bg-white rounded shadow p-8">
                <div class="mb-6">
                    <label class="block text-gray-700 font-semibold mb-2">Titel *</label>
                    <input type="text" name="title" required class="w-full px-4 py-2 border rounded" placeholder="News-Titel">
                </div>

                <div class="mb-6">
                    <label class="block text-gray-700 font-semibold mb-2">Bild-URL</label>
                    <input type="url" name="imageUrl" class="w-full px-4 py-2 border rounded" placeholder="https://example.com/image.jpg">
                </div>

                <div class="mb-6">
                    <label class="block text-gray-700 font-semibold mb-2">Inhalt *</label>
                    <textarea id="editor" name="content" required class="w-full px-4 py-2 border rounded" rows="10" placeholder="News-Inhalt hier..."></textarea>
                </div>

                <div class="mb-6">
                    <label class="block text-gray-700 font-semibold mb-2">Tags (komma-getrennt)</label>
                    <input type="text" name="tags" class="w-full px-4 py-2 border rounded" placeholder="Tag1, Tag2, Tag3">
                </div>

                <div class="mb-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="published" value="1" class="w-4 h-4 text-primary rounded">
                        <span class="ml-2 text-gray-700">Veröffentlichen</span>
                    </label>
                </div>

                <div class="flex gap-4">
                    <button type="submit" class="bg-primary text-white px-6 py-2 rounded hover:bg-blue-600 transition">Speichern</button>
                    <a href="/admin/news" class="px-6 py-2 border rounded hover:bg-gray-100 transition">Abbrechen</a>
                </div>
            </form>
        </div>

        <script>
            const editor = new SimpleMDE({
                element: document.getElementById('editor'),
                spellChecker: false,
                autoDownloadFontAwesome: false
            });
        </script>
        <?php
        $content = ob_get_clean();
        require __DIR__ . '/../../templates/layouts/admin.php';
    }

    public function store($params = [], $post = [], $get = [])
    {
        $this->checkAdmin();

        try {
            $data = [
                'title' => $post['title'] ?? '',
                'content' => $post['content'] ?? '',
                'type' => 'news',
                'published' => isset($post['published']),
                'imageUrl' => $post['imageUrl'] ?? '',
                'tags' => array_filter(array_map('trim', explode(',', $post['tags'] ?? ''))),
                'authorId' => $_SESSION['email'] ?? 'admin'
            ];

            $newsId = Firebase::createPost($data);

            if ($newsId) {
                header('Location: /admin/news');
                exit;
            } else {
                App::showNotification('Fehler beim Speichern', 'error');
            }
        } catch (\Exception $e) {
            error_log('News create error: ' . $e->getMessage());
        }
    }

    public function edit($params = [], $post = [], $get = [])
    {
        $this->checkAdmin();

        $newsId = $params['id'] ?? null;
        $article = Firebase::getPostById($newsId);

        if (!$article) {
            http_response_code(404);
            return;
        }

        $title = 'News bearbeiten';
        $pageTitle = 'News bearbeiten';

        ob_start();
        ?>
        <div class="max-w-3xl">
            <h1 class="text-3xl font-bold mb-8"><?= htmlspecialchars($pageTitle) ?></h1>

            <form method="POST" action="/admin/news/<?= htmlspecialchars($newsId) ?>/update" class="bg-white rounded shadow p-8">
                <div class="mb-6">
                    <label class="block text-gray-700 font-semibold mb-2">Titel *</label>
                    <input type="text" name="title" required value="<?= htmlspecialchars($article['title']) ?>" class="w-full px-4 py-2 border rounded">
                </div>

                <div class="mb-6">
                    <label class="block text-gray-700 font-semibold mb-2">Bild-URL</label>
                    <input type="url" name="imageUrl" value="<?= htmlspecialchars($article['imageUrl'] ?? '') ?>" class="w-full px-4 py-2 border rounded">
                </div>

                <div class="mb-6">
                    <label class="block text-gray-700 font-semibold mb-2">Inhalt *</label>
                    <textarea id="editor" name="content" required class="w-full px-4 py-2 border rounded" rows="10"><?= htmlspecialchars($article['content']) ?></textarea>
                </div>

                <div class="mb-6">
                    <label class="block text-gray-700 font-semibold mb-2">Tags (komma-getrennt)</label>
                    <input type="text" name="tags" value="<?= htmlspecialchars(implode(', ', $article['tags'] ?? [])) ?>" class="w-full px-4 py-2 border rounded">
                </div>

                <div class="mb-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="published" value="1" <?= $article['published'] ? 'checked' : '' ?> class="w-4 h-4 text-primary rounded">
                        <span class="ml-2 text-gray-700">Veröffentlichen</span>
                    </label>
                </div>

                <div class="flex gap-4">
                    <button type="submit" class="bg-primary text-white px-6 py-2 rounded hover:bg-blue-600 transition">Speichern</button>
                    <a href="/admin/news" class="px-6 py-2 border rounded hover:bg-gray-100 transition">Abbrechen</a>
                </div>
            </form>
        </div>

        <script>
            const editor = new SimpleMDE({
                element: document.getElementById('editor'),
                spellChecker: false,
                autoDownloadFontAwesome: false
            });
        </script>
        <?php
        $content = ob_get_clean();
        require __DIR__ . '/../../templates/layouts/admin.php';
    }

    public function update($params = [], $post = [], $get = [])
    {
        $this->checkAdmin();

        $newsId = $params['id'] ?? null;

        try {
            $data = [
                'title' => $post['title'] ?? '',
                'content' => $post['content'] ?? '',
                'published' => isset($post['published']),
                'imageUrl' => $post['imageUrl'] ?? '',
                'tags' => array_filter(array_map('trim', explode(',', $post['tags'] ?? '')))
            ];

            if (Firebase::updatePost($newsId, $data)) {
                header('Location: /admin/news');
                exit;
            }
        } catch (\Exception $e) {
            error_log('News update error: ' . $e->getMessage());
        }
    }

    public function delete($params = [], $post = [], $get = [])
    {
        $this->checkAdmin();

        $newsId = $params['id'] ?? null;

        try {
            if (Firebase::deletePost($newsId)) {
                header('Location: /admin/news');
                exit;
            }
        } catch (\Exception $e) {
            error_log('News delete error: ' . $e->getMessage());
        }
    }
}
