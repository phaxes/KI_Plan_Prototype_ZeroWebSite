<?php

namespace App\Controllers;

use App\Middleware\AuthMiddleware;

class ProfileController
{
    public function index($params = [], $post = [], $get = [])
    {
        // Require authentication
        AuthMiddleware::require();

        $title = 'Profil';
        $pageTitle = 'Mein Profil';

        ob_start();
        ?>
        <section class="py-12">
            <div class="container mx-auto px-4 max-w-2xl">
                <h1 class="text-4xl font-bold mb-8"><?= htmlspecialchars($pageTitle) ?></h1>

                <div class="bg-white rounded shadow p-8">
                    <div class="mb-8">
                        <h2 class="text-2xl font-bold mb-4">Persönliche Daten</h2>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">Name</label>
                                <input type="text" value="<?= htmlspecialchars($_SESSION['displayName'] ?? '') ?>" class="w-full px-4 py-2 border rounded" disabled>
                            </div>
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">E-Mail</label>
                                <input type="email" value="<?= htmlspecialchars($_SESSION['email'] ?? '') ?>" class="w-full px-4 py-2 border rounded" disabled>
                            </div>
                        </div>
                    </div>

                    <div class="border-t pt-8">
                        <h2 class="text-2xl font-bold mb-4">Account-Einstellungen</h2>
                        <div class="space-y-2">
                            <button class="w-full text-left px-4 py-3 border rounded hover:bg-gray-50 transition">
                                Passwort ändern (M3)
                            </button>
                            <button class="w-full text-left px-4 py-3 border rounded hover:bg-gray-50 transition">
                                Newsletter-Einstellungen (M5)
                            </button>
                        </div>
                    </div>

                    <div class="border-t pt-8">
                        <a href="/logout" class="text-red-600 hover:text-red-700 transition font-semibold">Logout</a>
                    </div>
                </div>
            </div>
        </section>
        <?php
        $content = ob_get_clean();
        require __DIR__ . '/../../templates/layouts/base.php';
    }
}
