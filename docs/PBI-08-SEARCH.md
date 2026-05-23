# PBI-08: Global Search for Blog Posts and Products

Global keyword search across all published blog posts, news, and active products — delivered as a keyboard-accessible modal overlay.

## Quick Start

### 1. Open Search Modal

- Click the **search icon** (🔍) in the header (desktop or mobile)
- **OR** press **Ctrl+K** (Cmd+K on Mac)

### 2. Type Your Query

- Minimum 2 characters
- Search is applied with 300ms debounce (searches as you type)
- Results update automatically

### 3. View Results

Results are grouped into two sections:
- **Artikel** — Blog posts and news matching your query
- **Produkte** — Products matching your query

Each result shows:
- Thumbnail image
- Title
- Brief excerpt
- For products: price

### 4. Navigate to Result

Click any result card to go to its detail page. Modal closes automatically.

## Keyboard Shortcuts

| Shortcut | Action |
|----------|--------|
| Ctrl+K | Open/close search modal (Cmd+K on Mac) |
| Escape | Close modal |
| Click backdrop | Close modal |

## API Endpoint

### GET /api/search?q=query

**Parameters:**
- `q` (required): Search query, minimum 2 characters

**Response (Success - 200):**
```json
{
  "success": true,
  "query": "test",
  "results": {
    "posts": [
      {
        "id": "blog-4",
        "title": "Blog Title",
        "type": "blog",
        "excerpt": "Brief excerpt from content...",
        "imageUrl": "https://...",
        "url": "/blog/blog-4"
      }
    ],
    "products": [
      {
        "id": "product-1",
        "name": "Product Name",
        "description": "Product description...",
        "price": 99.99,
        "imageUrl": "https://...",
        "url": "/shop/product-1"
      }
    ]
  }
}
```

**Response (Too Short - 400):**
```json
{
  "error": "Mindestens 2 Zeichen erforderlich",
  "code": "TOO_SHORT"
}
```

## Search Behavior

### What Gets Searched

**Blog Posts & News:**
- Title
- Content/body text
- Tags

**Products:**
- Name
- Description
- Category

### What's Filtered

- Only **published** blog posts & news are returned
- Only **active** products are returned
- Results sorted by creation date (newest first for posts)

### Result Limits

- Maximum **6 posts** (blog + news combined)
- Maximum **6 products**

### Case-Insensitive

All searches are case-insensitive (e.g., "TEST", "test", "Test" all match).

## Technical Details

### Performance Notes

- Search uses full collection scan with PHP-side `stripos()` filtering
- No external search service dependency
- Firestore handles result fetching, PHP filters in-memory
- Results cached by browser for repeated identical searches
- Debounce prevents excessive API calls (300ms delay)

### Files Involved

| File | Purpose |
|------|---------|
| `src/Controllers/SearchController.php` | API endpoint handler |
| `src/Firebase.php::searchPosts()` | Blog/news search logic |
| `src/Firebase.php::searchProducts()` | Product search logic |
| `public/js/search.js` | Modal UI + AJAX |
| `templates/partials/header.phtml` | Search icon buttons |
| `templates/layouts/base.phtml` | Modal HTML structure |

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Search icon not visible | Check header.phtml includes `search-open-btn` elements |
| Modal doesn't open | Check search.js is loaded (no console errors) |
| No results appear | Verify Firebase is connected; try searching for "test" |
| Results take long time | Normal for large collections; Firebase latency affects first load |
| Search doesn't work with special chars | Some special characters may be treated as word boundaries |

## Future Enhancements

- [ ] Elasticsearch or Algolia integration for full-text search
- [ ] Search history / suggestions
- [ ] Filters by category, date range, price
- [ ] Advanced search syntax (AND, OR, NOT)
- [ ] Faceted search results
- [ ] Search analytics (popular queries)

## Related

- [[PBI-04]](PBI-04-Warenkorb.md) — Shopping cart
- [[PBI-05]](PBI-05-Checkout.md) — Checkout & payments
- [[PBI-06]](PBI-06-NEWSLETTER.md) — Newsletter signup
- [Firebase Firestore REST API](https://firebase.google.com/docs/firestore/use-rest-api)
