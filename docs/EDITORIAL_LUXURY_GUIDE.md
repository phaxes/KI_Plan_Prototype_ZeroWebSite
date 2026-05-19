# Editorial Luxury Landing Page — User Guide

**Version**: 1.0 (PBI-01 Complete)  
**Last Updated**: 2026-05-19

## What You'll See

### 1. **Hero Section**
- **Word-by-word headline animation**: Watch "Professionelle Webpräsenz zum Nulltarif" appear word by word with a dynamic reveal effect
- **Copper accent line**: Geometric separator between headline and subtitle
- **Gradient dark background**: Deep charcoal gradient for editorial sophistication
- **Mobile responsive**: Full-viewport layout on all devices

### 2. **Features Section**
- **SVG icons**: Clean vector icons for CMS, Shop, and Newsletter (no emoji)
- **Copper-accented cards**: Cards have a copper top border with lift effect on hover
- **Editorial eyebrow**: "Was wir bieten" label above section title
- **Scroll reveals**: Cards slide up and fade in as you scroll

### 3. **NEW Stats Section**
- **Three key metrics**: 
  - 5 Milestones
  - 0€ per month
  - ∞ Possibilities
- **Copper dividers**: On mobile (horizontal), on desktop (vertical)
- **Dark gradient background**: Matches hero aesthetic

### 4. **News & Blog Sections**
- **Scroll animations**: Articles fade in and slide up as they enter viewport
- **Staggered reveal**: Each article appears with a slight delay for visual rhythm
- **Editorial labels**: "Aktuell" and "Tipps & Insights" set context

### 5. **Products Section**
- **Dark gradient background**: Consistent with hero for visual anchoring
- **Scroll-reveal cards**: Products animate in as you scroll

### 6. **Navigation**
- **Glass-blur header**: Scroll down 50px and the header goes dark with a blur effect
- **Active link highlighting**: Current page link shows in copper
- **SVG hamburger**: Mobile menu toggle with sharp lines (no emoji)

---

## Experience

### Desktop
1. Hero words animate in on page load (watch the title!)
2. Scroll down — each section slides up as it appears
3. Scroll to top — header is clean white
4. Scroll down 50px — header darkens with blur effect
5. Click on a nav link — see it highlight in copper

### Mobile
1. All animations work on mobile
2. Stats stack vertically with dividers
3. CTA buttons stack on top of each other
4. Hamburger menu is an SVG icon

---

## Technical Details

**No new dependencies**: All animations use vanilla JavaScript + Tailwind CSS.

**CSS Changes**:
- 3 new keyframe animations
- 10 new CSS classes
- 2 new colors (`charcoal`, `copper-lt`)

**JavaScript Changes**:
- New `animations.js` file with 4 functions
- IntersectionObserver for scroll-reveal
- Word-split logic for hero headline
- Header scroll detection
- Active nav link detection

**Browser Support**: All modern browsers (Chrome, Firefox, Safari, Edge, mobile)

---

## Performance

- **CSS**: +12KB minified
- **JS**: +3.2KB minified
- **Animations**: GPU-accelerated (opacity + transform only)
- **No layout thrashing**: Compositing-only animations

---

## Customization

To adjust animation timings, edit `/public/css/input.css`:

```css
/* Faster reveals (change 0.6s to 0.3s) */
.reveal {
  transition: opacity 0.3s ..., transform 0.3s ...;
}

/* Slower hero reveals (change 0.7s to 1s) */
.hero-word span {
  transition: opacity 1s ..., transform 1s ...;
}

/* Adjust scroll trigger (change 40px to 100px) */
/* Edit initNavScroll() in /public/js/animations.js */
```

---

## Troubleshooting

**No animations appearing?**
- Ensure `npm run build` was run after changes
- Check browser console for JS errors
- Verify `animations.js` is loaded (DevTools > Network tab)

**Hero headline looks weird?**
- The JavaScript wraps words in divs at runtime — this is normal
- To revert, remove `initHeroReveal()` call in `animations.js`

**Header not blurring?**
- Check CSS property `-webkit-backdrop-filter` for Safari
- Some older phones may not support blur (but site still works)

**Animations too slow/fast?**
- Edit `transition-delay` or `transition-duration` in CSS
- Each section uses `reveal-delay-1/2/3/4` for stagger

---

## Support

For issues or enhancements, refer to:
- `/docs/PBI-01_IMPLEMENTATION.md` — Technical implementation details
- `/public/js/animations.js` — Animation source code
- `/public/css/input.css` — All CSS definitions
