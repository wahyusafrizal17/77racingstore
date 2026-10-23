(() => {
  'use strict';

  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  requestAnimationFrame(() => {
    document.body.classList.add('is-loaded');
  });

  /* ── Nav scroll ── */
  const nav = document.getElementById('site-nav');
  const onScrollNav = () => {
    if (!nav) return;
    nav.classList.toggle('is-scrolled', window.scrollY > 40);
  };
  window.addEventListener('scroll', onScrollNav, { passive: true });
  onScrollNav();

  /* ── Smooth anchor scroll ── */
  document.querySelectorAll('a[href^="#"]').forEach((link) => {
    link.addEventListener('click', (e) => {
      const id = link.getAttribute('href');
      if (!id || id === '#') return;
      const target = document.querySelector(id);
      if (!target) return;
      e.preventDefault();
      target.scrollIntoView({ behavior: prefersReducedMotion ? 'auto' : 'smooth', block: 'start' });
    });
  });

  /* ── Reveal on scroll ── */
  const revealEls = document.querySelectorAll('[data-reveal]');
  if (revealEls.length && !prefersReducedMotion) {
    const revealObserver = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
            revealObserver.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.12, rootMargin: '0px 0px -40px 0px' },
    );
    revealEls.forEach((el) => revealObserver.observe(el));
  } else {
    revealEls.forEach((el) => el.classList.add('is-visible'));
  }

  /* ── Counter animation ── */
  const counters = document.querySelectorAll('[data-counter]');
  const formatCounterValue = (value, decimals) => {
    const fixed = Number(value).toFixed(decimals);
    const [intPart, decPart] = fixed.split('.');
    const formattedInt = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    return decimals > 0 ? `${formattedInt},${decPart}` : formattedInt;
  };

  const animateCounter = (el) => {
    const target = parseFloat(el.dataset.counter || '0');
    const suffix = el.dataset.suffix || '';
    const prefix = el.dataset.prefix || '';
    const decimals = parseInt(el.dataset.decimals || '0', 10);
    const duration = 1800;
    const start = performance.now();

    const tick = (now) => {
      const progress = Math.min((now - start) / duration, 1);
      const eased = 1 - Math.pow(1 - progress, 3);
      const value = target * eased;
      el.textContent = prefix + formatCounterValue(value, decimals) + suffix;
      if (progress < 1) requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);
  };

  if (counters.length) {
    const counterObserver = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            animateCounter(entry.target);
            counterObserver.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.4 },
    );
    counters.forEach((el) => counterObserver.observe(el));
  }

  /* ── Hero slider ── */
  const heroSection = document.getElementById('hero');
  const heroSlides = document.querySelectorAll('.hero-slide');
  const heroContents = document.querySelectorAll('.hero-slide-content');
  const heroDots = document.querySelectorAll('#hero-dots .hero-dot');
  const heroPrev = document.getElementById('hero-prev');
  const heroNext = document.getElementById('hero-next');
  const heroCounter = document.getElementById('hero-slide-counter');

  const restartHeroStagger = (content) => {
    if (!content || prefersReducedMotion) return;
    content.querySelectorAll('.hero-stagger').forEach((el) => {
      el.style.animation = 'none';
      void el.offsetWidth;
      el.style.animation = '';
    });
  };

  if (heroSlides.length > 1) {
    let currentSlide = 0;
    let autoplayTimer = null;
    const autoplayMs = parseInt(heroSection?.dataset.heroAutoplay || '6000', 10);

    const padSlide = (n) => String(n).padStart(2, '0');

    const goToSlide = (index) => {
      const total = heroSlides.length;
      currentSlide = ((index % total) + total) % total;
      const activeSlide = heroSlides[currentSlide];
      const isBanner = activeSlide?.dataset.hideCopy === '1';

      heroSection?.classList.toggle('hero--banner-active', isBanner);

      heroSlides.forEach((slide, i) => {
        slide.classList.toggle('is-active', i === currentSlide);
      });
      heroContents.forEach((content) => {
        const contentIndex = parseInt(content.dataset.slideContent || '0', 10);
        const isActive = contentIndex === currentSlide;
        content.classList.toggle('is-active', isActive);
        if (isActive) restartHeroStagger(content);
      });
      heroDots.forEach((dot, i) => {
        dot.classList.toggle('is-active', i === currentSlide);
        dot.setAttribute('aria-selected', i === currentSlide ? 'true' : 'false');
      });

      if (heroCounter) {
        heroCounter.textContent = `${padSlide(currentSlide + 1)} / ${padSlide(total)}`;
      }
    };

    const nextSlide = () => goToSlide(currentSlide + 1);
    const prevSlide = () => goToSlide(currentSlide - 1);

    const startAutoplay = () => {
      if (prefersReducedMotion) return;
      stopAutoplay();
      autoplayTimer = window.setInterval(nextSlide, autoplayMs);
    };

    const stopAutoplay = () => {
      if (autoplayTimer) {
        window.clearInterval(autoplayTimer);
        autoplayTimer = null;
      }
    };

    heroNext?.addEventListener('click', () => {
      nextSlide();
      startAutoplay();
    });
    heroPrev?.addEventListener('click', () => {
      prevSlide();
      startAutoplay();
    });
    heroDots.forEach((dot) => {
      dot.addEventListener('click', () => {
        goToSlide(parseInt(dot.dataset.slideTo || '0', 10));
        startAutoplay();
      });
    });

    heroSection?.addEventListener('mouseenter', stopAutoplay);
    heroSection?.addEventListener('mouseleave', startAutoplay);

    startAutoplay();
  } else if (heroContents[0]) {
    restartHeroStagger(heroContents[0]);
  }

  /* ── Hero parallax ── */
  const heroSlider = document.getElementById('hero-slider');
  if (heroSlider && !prefersReducedMotion) {
    window.addEventListener(
      'scroll',
      () => {
        const y = window.scrollY;
        if (y >= window.innerHeight) return;
        const activeSlide = heroSlider.querySelector('.hero-slide.is-active');
        if (activeSlide) {
          activeSlide.style.transform = `translateY(${y * 0.28}px)`;
        }
      },
      { passive: true },
    );
  }

  /* ── Mobile menu ── */
  const mobileMenuBtn = document.getElementById('mobile-menu-btn');
  const mobileMenu = document.getElementById('mobile-menu');

  if (mobileMenuBtn && mobileMenu) {
    mobileMenuBtn.addEventListener('click', () => {
      const isOpen = mobileMenu.classList.toggle('is-open');
      mobileMenu.classList.toggle('hidden', !isOpen);
    });

    mobileMenu.querySelectorAll('.mobile-nav-link').forEach((link) => {
      link.addEventListener('click', () => {
        mobileMenu.classList.remove('is-open');
        mobileMenu.classList.add('hidden');
      });
    });
  }
})();
