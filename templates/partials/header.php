<header class="bg-white shadow sticky top-0 z-50">
    <nav class="container mx-auto px-4 py-4 flex justify-between items-center">
        <a href="/" class="text-2xl font-bold text-primary">ZeroWeb</a>

        <div class="hidden md:flex gap-6">
            <a href="/news" class="text-gray-700 hover:text-primary transition">News</a>
            <a href="/blog" class="text-gray-700 hover:text-primary transition">Blog</a>
            <a href="/shop" class="text-gray-700 hover:text-primary transition">Shop</a>
            <?php if (isset($_SESSION['userId'])): ?>
                <a href="/profile" class="text-gray-700 hover:text-primary transition">Profil</a>
                <a href="/logout" class="text-gray-700 hover:text-primary transition">Logout</a>
            <?php else: ?>
                <a href="/login" class="text-gray-700 hover:text-primary transition">Login</a>
                <a href="/register" class="text-gray-700 hover:text-primary transition">Registrieren</a>
            <?php endif; ?>
            <a href="/cart" class="bg-primary text-white px-4 py-2 rounded hover:bg-blue-600 transition">
                Warenkorb <span id="cart-count" class="ml-1">0</span>
            </a>
            <?php if (isset($_SESSION['isAdmin']) && $_SESSION['isAdmin']): ?>
                <a href="/admin" class="bg-secondary text-white px-4 py-2 rounded hover:bg-green-600 transition">Admin</a>
            <?php endif; ?>
        </div>

        <!-- Mobile menu toggle -->
        <button id="menu-toggle" class="md:hidden text-2xl">☰</button>
    </nav>

    <!-- Mobile menu -->
    <div id="mobile-menu" class="hidden md:hidden bg-gray-100 border-t">
        <div class="container mx-auto px-4 py-4 flex flex-col gap-2">
            <a href="/news" class="text-gray-700 hover:text-primary transition py-2">News</a>
            <a href="/blog" class="text-gray-700 hover:text-primary transition py-2">Blog</a>
            <a href="/shop" class="text-gray-700 hover:text-primary transition py-2">Shop</a>
            <?php if (isset($_SESSION['userId'])): ?>
                <a href="/profile" class="text-gray-700 hover:text-primary transition py-2">Profil</a>
                <a href="/logout" class="text-gray-700 hover:text-primary transition py-2">Logout</a>
            <?php else: ?>
                <a href="/login" class="text-gray-700 hover:text-primary transition py-2">Login</a>
                <a href="/register" class="text-gray-700 hover:text-primary transition py-2">Registrieren</a>
            <?php endif; ?>
            <a href="/cart" class="bg-primary text-white px-4 py-2 rounded py-2">Warenkorb <span id="cart-count-mobile" class="ml-1">0</span></a>
            <?php if (isset($_SESSION['isAdmin']) && $_SESSION['isAdmin']): ?>
                <a href="/admin" class="bg-secondary text-white px-4 py-2 rounded py-2">Admin</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<script>
    document.getElementById('menu-toggle')?.addEventListener('click', function() {
        document.getElementById('mobile-menu').classList.toggle('hidden');
    });
</script>
