<?php

namespace App\Controllers\Admin;

class SubscriberController
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

        $pageTitle = 'Newsletter-Abonnenten';

        ob_start();
        ?>
        <h1 class="text-3xl font-bold mb-8"><?= htmlspecialchars($pageTitle) ?></h1>

        <div class="bg-white rounded shadow overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-100 border-b">
                    <tr>
                        <th class="px-6 py-3 text-left">E-Mail</th>
                        <th class="px-6 py-3 text-left">Name</th>
                        <th class="px-6 py-3 text-left">Quelle</th>
                        <th class="px-6 py-3 text-left">Datum</th>
                        <th class="px-6 py-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-6 py-4">example@email.de</td>
                        <td class="px-6 py-4">Max Mustermann</td>
                        <td class="px-6 py-4 text-sm text-gray-600">website</td>
                        <td class="px-6 py-4 text-sm">2026-05-18</td>
                        <td class="px-6 py-4 text-center text-green-600">✓ Aktiv</td>
                    </tr>
                </tbody>
            </table>

            <div class="p-6 bg-blue-50 border-t text-sm text-gray-700">
                <p><strong>💡 Hinweis:</strong> Abonnenten werden in M5 mit Mailchimp synchronisiert</p>
            </div>
        </div>
        <?php
        $content = ob_get_clean();
        require __DIR__ . '/../../templates/layouts/admin.php';
    }
}
