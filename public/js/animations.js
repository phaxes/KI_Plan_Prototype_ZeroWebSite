function initHeroReveal() {
  const el = document.querySelector('#hero-headline');
  if (!el) return;

  const words = el.textContent.trim().split(/\s+/);
  const fragment = document.createDocumentFragment();

  words.forEach((word) => {
    const wordWrapper = document.createElement('div');
    wordWrapper.className = 'hero-word';

    const span = document.createElement('span');
    span.textContent = word + ' ';

    wordWrapper.appendChild(span);
    fragment.appendChild(wordWrapper);
  });

  el.innerHTML = '';
  el.appendChild(fragment);

  setTimeout(() => {
    const spans = el.querySelectorAll('.hero-word span');
    spans.forEach((span, index) => {
      span.style.transitionDelay = (index * 80) + 'ms';
      span.classList.add('is-visible');
    });
  }, 300);
}

function initScrollReveal() {
  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    },
    {
      threshold: 0.12,
      rootMargin: '0px 0px -60px 0px'
    }
  );

  const reveals = document.querySelectorAll('.reveal');
  reveals.forEach((el) => observer.observe(el));
}

function initNavScroll() {
  const header = document.querySelector('#site-header');
  if (!header) return;

  window.addEventListener(
    'scroll',
    () => {
      if (window.scrollY > 40) {
        header.classList.add('nav-scrolled');
      } else {
        header.classList.remove('nav-scrolled');
      }
    },
    { passive: true }
  );
}

function initActiveNav() {
  const currentPath = window.location.pathname;
  const navLinks = document.querySelectorAll('[data-nav-link]');

  navLinks.forEach((link) => {
    const href = link.getAttribute('href');
    if (href === currentPath) {
      link.classList.add('text-primary');
      link.classList.remove('text-ink');
    }
  });
}

document.addEventListener('DOMContentLoaded', () => {
  initHeroReveal();
  initScrollReveal();
  initNavScroll();
  initActiveNav();
});
