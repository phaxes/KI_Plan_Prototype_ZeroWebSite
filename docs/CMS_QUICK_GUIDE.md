# CMS Quick Reference Guide

**For Admin Users**

---

## Accessing the Admin Panel

1. Go to `http://localhost:8000` (or your production URL)
2. Click **Login** in the top-right
3. Sign in with your Google account
4. You'll be redirected to the admin dashboard

## Managing News

### Create a News Article

1. Click **Admin** in the top nav (or go to `/admin`)
2. In the sidebar, click **News**
3. Click **+ Neue News** (New News)
4. Fill in:
   - **Titel** (Title) — The headline
   - **Bild-URL** (Image URL) — Optional image link
   - **Inhalt** (Content) — The article body. Use the markdown editor buttons or type markdown syntax
   - **Tags** — Separate with commas: `tag1, tag2, tag3`
   - **Veröffentlichen** (Publish) — Check to make it visible immediately

5. Click **Speichern** (Save)

### Edit an Existing Article

1. Go to **Admin** → **News**
2. Find the article in the list
3. Click **Bearbeiten** (Edit)
4. Make your changes
5. Click **Speichern** to update

### Delete an Article

1. Go to **Admin** → **News**
2. Find the article
3. Click **Löschen** (Delete)
4. Confirm when prompted

## Managing Blog Posts

**Identical to News** — use the **Blog** section in the admin sidebar instead.

---

## Content Tips

### Markdown Support

Content is stored as plain text/markdown. You can write:

```markdown
# Heading 1
## Heading 2
### Heading 3

**Bold text**
*Italic text*

- List item 1
- List item 2

1. Numbered item 1
2. Numbered item 2

[Link text](https://example.com)

`inline code`

```

### Images

Use the **Bild-URL** field to add an image at the top of the article. You can:
- Link to images hosted elsewhere: `https://example.com/image.jpg`
- Upload to Firebase Storage first, then paste the URL here

### Tags

Separate tags with commas. Examples:
- `Berlin, Tech, Review`
- `Update, Important, 2026`

Tags appear on the listing and detail pages.

### Publishing

- **Check "Veröffentlichen"** → Article is immediately visible to the public
- **Leave unchecked** → Article is saved as a draft (admin-only, not visible publicly)

---

## Viewing Your Content

### Live on the Website

**Home Page** (`/`)
- Your latest 3 published news articles appear here
- Your latest 3 published blog posts appear here

**News Listing** (`/news`)
- All published news articles in a grid
- Paginated by 9 per page

**Blog Listing** (`/blog`)
- All published blog posts in a grid
- Paginated by 9 per page

**Individual Article** (`/news/{id}` or `/blog/{id}`)
- Full article view with image, content, author, date, tags

---

## Dashboard

The admin dashboard (`/admin`) shows:
- **Stats**: Total count of news, blog posts, and products
- **Recent News**: Latest 5 news articles
- **Recent Blog**: Latest 5 blog posts

---

## Troubleshooting

**"You don't have permission to access this page"**
- You're not logged in as an admin
- Ask the site owner to set `isAdmin: true` in Firestore for your user account

**"Fehler beim Speichern" (Save failed)**
- Firebase connection error — check your internet connection
- Firestore rules may be blocking your write — contact the admin

**Images not showing**
- The image URL is broken or inaccessible
- Double-check the **Bild-URL** is a direct link to an image file
- Try opening the URL in a new tab to verify it works

**Markdown not rendering**
- Content is stored as plain text with markdown syntax visible
- This is intentional — readers see the raw markdown
- For rich HTML rendering, use Firebase Storage + a markdown renderer (future enhancement)

---

## Keyboard Shortcuts

- **Tab** — Move between form fields
- **Shift+Tab** — Move to previous field
- **Ctrl+S** (or **Cmd+S** on Mac) → Submit the form (in most browsers)

---

## Need Help?

- Check `/docs/PBI-02_NEWS_BLOG_CMS.md` for technical details
- Contact your site administrator with questions about permissions or configuration
