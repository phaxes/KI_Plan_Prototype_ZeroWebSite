const Search = (function () {
    'use strict';

    let modal, backdrop, input, results, closeBtn;
    let debounceTimer = null;
    let isOpen = false;
    let lastQuery = '';

    function open() {
        if (isOpen) return;
        isOpen = true;
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        requestAnimationFrame(() => input.focus());
    }

    function close() {
        if (!isOpen) return;
        isOpen = false;
        modal.style.display = 'none';
        document.body.style.overflow = '';
        results.innerHTML = '';
        input.value = '';
        lastQuery = '';
        clearTimeout(debounceTimer);
    }

    function showState(text) {
        results.innerHTML = '';
        const el = document.createElement('div');
        el.className = 'flex items-center justify-center py-12 text-muted font-mono text-xs uppercase tracking-widest';
        el.textContent = text;
        results.appendChild(el);
    }

    function createSectionHeader(label) {
        const h = document.createElement('div');
        h.className = 'px-3 py-2 bg-void';
        const s = document.createElement('span');
        s.className = 'font-mono text-xs uppercase tracking-widest text-primary';
        s.textContent = label;
        h.appendChild(s);
        return h;
    }

    function createThumb(imageUrl, fallbackLetter, darkBg) {
        const thumb = document.createElement('div');
        thumb.className = 'flex-shrink-0 w-16 h-16 border-2 border-ink overflow-hidden';
        if (imageUrl) {
            const img = document.createElement('img');
            img.src = imageUrl;
            img.alt = '';
            img.className = 'w-full h-full object-cover';
            img.loading = 'lazy';
            thumb.appendChild(img);
        } else {
            thumb.className += darkBg ? ' bg-void flex items-center justify-center' : ' bg-paper flex items-center justify-center';
            const pl = document.createElement('span');
            pl.className = 'text-primary font-mono text-xs uppercase';
            pl.textContent = fallbackLetter;
            thumb.appendChild(pl);
        }
        return thumb;
    }

    function createResultRow(url) {
        const a = document.createElement('a');
        a.href = url;
        a.className = 'flex gap-4 items-start p-3 border-b border-ink hover:bg-void hover:text-paper transition cursor-pointer group';
        a.addEventListener('click', close);
        return a;
    }

    function createPostCard(post) {
        const a = createResultRow(post.url);
        a.appendChild(createThumb(post.imageUrl, post.type === 'news' ? 'N' : 'B', true));

        const text = document.createElement('div');
        text.className = 'flex-1 min-w-0';

        const badge = document.createElement('span');
        badge.className = 'font-mono text-xs uppercase tracking-widest text-primary';
        badge.textContent = post.type === 'news' ? 'News' : 'Blog';
        text.appendChild(badge);

        const title = document.createElement('p');
        title.className = 'font-serif text-sm text-ink leading-snug mt-1 line-clamp-2 group-hover:text-paper';
        title.textContent = post.title;
        text.appendChild(title);

        if (post.excerpt) {
            const ex = document.createElement('p');
            ex.className = 'font-mono text-xs text-muted mt-1 line-clamp-2';
            ex.textContent = post.excerpt;
            text.appendChild(ex);
        }

        a.appendChild(text);
        return a;
    }

    function createProductCard(product) {
        const a = createResultRow(product.url);
        a.appendChild(createThumb(product.imageUrl, 'P', false));

        const text = document.createElement('div');
        text.className = 'flex-1 min-w-0';

        const price = document.createElement('span');
        price.className = 'font-mono text-xs text-primary uppercase tracking-widest';
        price.textContent = typeof App !== 'undefined' ? App.formatPrice(product.price) : product.price.toFixed(2) + ' €';
        text.appendChild(price);

        const name = document.createElement('p');
        name.className = 'font-serif text-sm text-ink leading-snug mt-1 line-clamp-2 group-hover:text-paper';
        name.textContent = product.name;
        text.appendChild(name);

        if (product.description) {
            const desc = document.createElement('p');
            desc.className = 'font-mono text-xs text-muted mt-1 line-clamp-2';
            desc.textContent = product.description;
            text.appendChild(desc);
        }

        a.appendChild(text);
        return a;
    }

    function renderResults(data, query) {
        results.innerHTML = '';

        const hasPosts    = data.results.posts.length > 0;
        const hasProducts = data.results.products.length > 0;

        if (!hasPosts && !hasProducts) {
            const wrap = document.createElement('div');
            wrap.className = 'py-12 text-center';

            const msg = document.createElement('p');
            msg.className = 'font-mono text-xs uppercase tracking-widest text-muted mb-2';
            msg.textContent = 'Keine Ergebnisse für';

            const term = document.createElement('p');
            term.className = 'font-serif text-xl text-ink';
            term.textContent = '"' + query + '"';

            wrap.appendChild(msg);
            wrap.appendChild(term);
            results.appendChild(wrap);
            return;
        }

        if (hasPosts) {
            results.appendChild(createSectionHeader('Artikel'));
            data.results.posts.forEach(p => results.appendChild(createPostCard(p)));
        }

        if (hasProducts) {
            results.appendChild(createSectionHeader('Produkte'));
            data.results.products.forEach(p => results.appendChild(createProductCard(p)));
        }
    }

    async function fetchResults(query) {
        showState('Suche läuft…');

        try {
            const res = await fetch('/api/search?q=' + encodeURIComponent(query), {
                headers: { Accept: 'application/json' }
            });

            const data = await res.json();

            if (!res.ok) {
                results.innerHTML = '';
                return;
            }

            renderResults(data, query);

        } catch (err) {
            showState('Verbindungsfehler – bitte versuche es erneut');
        }
    }

    function onInput() {
        const q = input.value.trim();

        if (q === lastQuery) return;
        lastQuery = q;

        clearTimeout(debounceTimer);

        if (q.length === 0) {
            results.innerHTML = '';
            return;
        }

        if (q.length < 2) {
            showState('Mindestens 2 Zeichen eingeben');
            return;
        }

        debounceTimer = setTimeout(() => {
            fetchResults(q);
        }, 300);
    }

    function onKeydown(e) {
        // Ctrl+K or Cmd+K
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            if (isOpen) {
                close();
            } else {
                open();
            }
            return;
        }

        // Escape
        if (e.key === 'Escape' && isOpen) {
            e.preventDefault();
            close();
        }
    }

    function init() {
        modal    = document.getElementById('search-modal');
        backdrop = document.getElementById('search-backdrop');
        input    = document.getElementById('search-input');
        results  = document.getElementById('search-results');
        closeBtn = document.getElementById('search-close-btn');

        if (!modal || !input) return;

        // Open buttons
        document.querySelectorAll('#search-open-btn, #search-open-btn-mobile').forEach(btn => {
            btn.addEventListener('click', open);
        });

        // Close button
        closeBtn.addEventListener('click', close);

        // Backdrop click
        backdrop.addEventListener('click', close);

        // Input
        input.addEventListener('input', onInput);

        // Keyboard
        document.addEventListener('keydown', onKeydown);
    }

    document.addEventListener('DOMContentLoaded', init);

    return { open, close };
})();
