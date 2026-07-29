// =========================================================
// SARAṆA — shared interactions
// =========================================================

document.addEventListener('DOMContentLoaded', () => {

  /* ---- preloader ---- */
  const preloader = document.getElementById('preloader');
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const dismissPreloader = () => {
    if (!preloader) { document.body.classList.add('is-loaded'); return; }
    setTimeout(() => {
      preloader.classList.add('is-hidden');
      document.body.classList.add('is-loaded');
      preloader.addEventListener('transitionend', () => preloader.remove(), { once: true });
    }, reduceMotion ? 0 : 400);
  };
  if (document.readyState === 'complete') {
    dismissPreloader();
  } else {
    window.addEventListener('load', dismissPreloader);
  }

  /* ---- custom cursor (fine-pointer desktop only) ---- */
  const cursorDot = document.getElementById('cursorDot');
  if (cursorDot && window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
    document.body.classList.add('has-custom-cursor');
    window.addEventListener('mousemove', (e) => {
      cursorDot.style.left = e.clientX + 'px';
      cursorDot.style.top = e.clientY + 'px';
      cursorDot.classList.add('is-active');
    }, { passive: true });
    document.querySelectorAll('a, button, .btn, .card, .gallery-tile, .quote-card, .retreat-card, input, textarea, select').forEach(el => {
      el.addEventListener('mouseenter', () => cursorDot.classList.add('is-hover'));
      el.addEventListener('mouseleave', () => cursorDot.classList.remove('is-hover'));
    });
  } else if (cursorDot) {
    cursorDot.remove();
  }

  /* ---- hero ambient particles (home page only) ---- */
  const particleHost = document.getElementById('heroParticles');
  if (particleHost && !reduceMotion) {
    for (let i = 0; i < 14; i++) {
      const p = document.createElement('span');
      p.className = 'hero-particle';
      p.style.left = Math.random() * 100 + '%';
      p.style.top = 20 + Math.random() * 70 + '%';
      p.style.animationDuration = (5 + Math.random() * 5) + 's';
      p.style.animationDelay = (Math.random() * 6) + 's';
      particleHost.appendChild(p);
    }
  }

  /* ---- sticky header state ---- */
  const header = document.querySelector('.site-header');
  const onScroll = () => {
    if (!header) return;
    header.classList.toggle('is-scrolled', window.scrollY > 30);
  };
  onScroll();
  window.addEventListener('scroll', onScroll, { passive: true });

  /* ---- mobile nav toggle ---- */
  const toggle = document.querySelector('.nav-toggle');
  const navLinks = document.querySelector('.nav-links');
  if (toggle && navLinks) {
    toggle.addEventListener('click', () => {
      navLinks.classList.toggle('is-open');
      document.body.classList.toggle('menu-open');
    });
    navLinks.querySelectorAll('a').forEach(a => {
      a.addEventListener('click', () => {
        navLinks.classList.remove('is-open');
        document.body.classList.remove('menu-open');
      });
    });
  }

  /* ---- mark active nav link ---- */
  const path = window.location.pathname.split('/').pop() || 'index.html';
  document.querySelectorAll('.nav-links a').forEach(a => {
    const href = a.getAttribute('href');
    if (href === path) a.classList.add('active');
  });

  /* ---- scroll reveal ---- */
  const revealEls = document.querySelectorAll('.reveal');
  if ('IntersectionObserver' in window && revealEls.length) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          io.unobserve(entry.target);
        }
      });
    }, { threshold: 0.15, rootMargin: '0px 0px -60px 0px' });
    revealEls.forEach((el, i) => {
      el.style.transitionDelay = (i % 3) * 90 + 'ms';
      io.observe(el);
    });
  } else {
    revealEls.forEach(el => el.classList.add('is-visible'));
  }

  /* ---- one-time gentle fade-in for the hero singing bowl emblem ---- */
  const emberScene = document.querySelector('.hero-monk-emblem');
  if (emberScene && !reduceMotion) {
    const playEntrance = () => {
      requestAnimationFrame(() => {
        emberScene.classList.add('is-entering');
        setTimeout(() => emberScene.classList.remove('is-entering'), 1200);
      });
    };
    if (document.body.classList.contains('is-loaded')) {
      setTimeout(playEntrance, 250);
    } else {
      window.addEventListener('load', () => setTimeout(playEntrance, 550));
    }
  }

  /* ---- singing bowl: tap to hear a synthesized bowl tone ---- */
  const bowl = document.getElementById('singingBowl');
  if (bowl) {
    const bowlScene = bowl.querySelector('.bowl-scene');
    let audioCtx = null;

    const ringBowl = () => {
      // audio: build a real singing-bowl tone from its natural overtone series,
      // since we can't embed a licensed audio file
      if (!reduceMotion) {
        try {
          if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
          if (audioCtx.state === 'suspended') audioCtx.resume();

          const now = audioCtx.currentTime;
          const fundamental = 233; // roughly a small brass bowl's fundamental
          const partials = [1, 1.52, 2.76, 3.94, 5.42]; // inharmonic ratios typical of singing bowls
          const master = audioCtx.createGain();
          master.gain.setValueAtTime(0.0001, now);
          master.gain.exponentialRampToValueAtTime(0.32, now + 0.04);
          master.gain.exponentialRampToValueAtTime(0.0001, now + 5.5);
          master.connect(audioCtx.destination);

          partials.forEach((ratio, i) => {
            // two very slightly detuned oscillators per partial for a natural shimmering beat
            [-0.6, 0.6].forEach(detune => {
              const osc = audioCtx.createOscillator();
              osc.type = 'sine';
              osc.frequency.value = fundamental * ratio;
              osc.detune.value = detune;
              const partialGain = audioCtx.createGain();
              partialGain.gain.value = 0.5 / (i + 1.3);
              osc.connect(partialGain).connect(master);
              osc.start(now);
              osc.stop(now + 5.6);
            });
          });
        } catch (err) {
          // Web Audio unavailable — the visual strike still plays
        }
      }

      // visual: one-off emphasized strike layered on top of the ambient loop
      if (bowlScene) {
        bowlScene.classList.remove('is-struck');
        void bowlScene.offsetWidth; // restart animation
        bowlScene.classList.add('is-struck');
        setTimeout(() => bowlScene.classList.remove('is-struck'), 2500);
      }
    };

    bowl.addEventListener('click', ringBowl);
    bowl.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        ringBowl();
      }
    });
  }

  /* =========================================================
     SCROLLYTELLING 2.0
  ========================================================= */

  /* ---- scroll progress bar (injected once, works on every page) ---- */
  const progressWrap = document.createElement('div');
  progressWrap.className = 'scroll-progress';
  progressWrap.setAttribute('aria-hidden', 'true');
  const progressBar = document.createElement('span');
  progressBar.className = 'scroll-progress-bar';
  progressWrap.appendChild(progressBar);
  document.body.appendChild(progressWrap);

  /* ---- split headings into per-word spans for the line-reveal effect ---- */
  document.querySelectorAll('.reveal-lines[data-split="words"]').forEach((heading) => {
    const words = heading.textContent.trim().split(/\s+/);
    heading.textContent = '';
    words.forEach((word, i) => {
      const wrap = document.createElement('span');
      wrap.className = 'split-word';
      wrap.style.transitionDelay = (i * 45) + 'ms';
      const inner = document.createElement('span');
      inner.textContent = word;
      wrap.appendChild(inner);
      heading.appendChild(wrap);
      heading.appendChild(document.createTextNode(' '));
    });
  });
  const lineRevealEls = document.querySelectorAll('.reveal-lines');
  if ('IntersectionObserver' in window && lineRevealEls.length) {
    const lineIO = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          lineIO.unobserve(entry.target);
        }
      });
    }, { threshold: 0.3 });
    lineRevealEls.forEach((el) => lineIO.observe(el));
  } else {
    lineRevealEls.forEach((el) => el.classList.add('is-visible'));
  }

  /* ---- animated count-up numbers (stat strip, etc.) ---- */
  const counterEls = document.querySelectorAll('[data-count-to]');
  if (counterEls.length) {
    const runCounter = (el) => {
      const target = parseFloat(el.dataset.countTo) || 0;
      const suffix = el.dataset.suffix || '';
      if (reduceMotion) { el.textContent = target + suffix; return; }
      const duration = 1400;
      const start = performance.now();
      const tick = (now) => {
        const p = Math.min(1, (now - start) / duration);
        const eased = 1 - Math.pow(1 - p, 3); // ease-out-cubic
        el.textContent = Math.round(eased * target) + suffix;
        if (p < 1) requestAnimationFrame(tick);
      };
      requestAnimationFrame(tick);
    };
    if ('IntersectionObserver' in window) {
      const counterIO = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            runCounter(entry.target);
            counterIO.unobserve(entry.target);
          }
        });
      }, { threshold: 0.6 });
      counterEls.forEach((el) => counterIO.observe(el));
    } else {
      counterEls.forEach(runCounter);
    }
  }

  /* ---- unified scroll loop: progress bar + parallax, throttled to one rAF ---- */
  const parallaxEls = document.querySelectorAll('[data-parallax]');
  let scrollTicking = false;
  const updateScrollEffects = () => {
    const doc = document.documentElement;
    const scrollTop = window.scrollY || doc.scrollTop;
    const maxScroll = (doc.scrollHeight - doc.clientHeight) || 1;
    progressBar.style.width = Math.min(100, (scrollTop / maxScroll) * 100) + '%';

    if (!reduceMotion && parallaxEls.length) {
      const vh = window.innerHeight;
      parallaxEls.forEach((el) => {
        const speed = parseFloat(el.dataset.parallax) || 0.12;
        const rect = el.getBoundingClientRect();
        // only bother moving things anywhere near the viewport
        if (rect.bottom < -200 || rect.top > vh + 200) return;
        const centerOffset = (rect.top + rect.height / 2) - vh / 2;
        el.style.transform = `translate3d(0, ${(centerOffset * speed).toFixed(1)}px, 0)`;
      });
    }
    scrollTicking = false;
  };
  const onScrollEffects = () => {
    if (!scrollTicking) {
      requestAnimationFrame(updateScrollEffects);
      scrollTicking = true;
    }
  };
  updateScrollEffects();
  window.addEventListener('scroll', onScrollEffects, { passive: true });
  window.addEventListener('resize', onScrollEffects);

  /* ---- pinned "story-scroll" module: advance active step as it crosses the viewport ---- */
  const storySection = document.getElementById('journeyStory');
  if (storySection) {
    const steps = storySection.querySelectorAll('.story-step');
    const images = storySection.querySelectorAll('.story-scroll-img');
    const dots = storySection.querySelectorAll('.story-dot');

    const setActiveStep = (index) => {
      steps.forEach((s) => s.classList.toggle('is-active', s.dataset.step === String(index)));
      images.forEach((img) => img.classList.toggle('is-active', img.dataset.step === String(index)));
      dots.forEach((d) => d.classList.toggle('is-active', d.dataset.step === String(index)));
    };

    if ('IntersectionObserver' in window) {
      const storyIO = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) setActiveStep(entry.target.dataset.step);
        });
      }, { threshold: 0.55, rootMargin: '-15% 0px -15% 0px' });
      steps.forEach((step) => storyIO.observe(step));
    }
  }

  /* ---- footer year ---- */
  const yearEl = document.querySelector('#year');
  if (yearEl) yearEl.textContent = new Date().getFullYear();

  /* ---- contact form: POST to send-booking.php (saves to DB + emails admin) ---- */
  const form = document.querySelector('#contact-form');
  if (form) {
    const statusEl = form.querySelector('#form-status');

    form.addEventListener('submit', (e) => {
      e.preventDefault();
      const btn = form.querySelector('.btn-submit');
      if (btn.classList.contains('is-sending') || btn.classList.contains('is-success')) return;
      const label = btn.querySelector('.btn-submit-label');
      const original = label.textContent;

      btn.classList.add('is-sending');
      label.textContent = 'Sending…';
      if (statusEl) {
        statusEl.textContent = '';
        statusEl.classList.remove('is-success', 'is-error');
      }

      const formData = new FormData(form);

      fetch('send-booking.php', {
        method: 'POST',
        body: formData
      })
        .then((res) => res.json())
        .then((data) => {
          btn.classList.remove('is-sending');
          if (data.success) {
            btn.classList.add('is-success');
            label.textContent = 'Message sent';
            if (statusEl) {
              statusEl.textContent = 'Thank you for submitting! We will contact you soon.';
              statusEl.classList.add('is-success');
            }
            setTimeout(() => {
              btn.classList.remove('is-success');
              label.textContent = original;
              form.reset();
            }, 2200);
          } else {
            label.textContent = original;
            if (statusEl) {
              statusEl.textContent = data.message || 'Something went wrong. Please try again.';
              statusEl.classList.add('is-error');
            }
          }
        })
        .catch(() => {
          btn.classList.remove('is-sending');
          label.textContent = original;
          if (statusEl) {
            statusEl.textContent = 'Something went wrong. Please check your connection and try again.';
            statusEl.classList.add('is-error');
          }
        });
    });
  }

  // ---------------------------------------------------------
  // Admin-editable wording — overlay any text an admin has
  // changed at /admin/content.php. Falls back silently to the
  // page's original text if the request fails.
  // ---------------------------------------------------------
  const editableEls = document.querySelectorAll('[data-edit-key]');
  if (editableEls.length) {
    fetch('content.php')
      .then((res) => res.json())
      .then((map) => {
        editableEls.forEach((el) => {
          const key = el.getAttribute('data-edit-key');
          if (Object.prototype.hasOwnProperty.call(map, key)) {
            el.innerHTML = map[key];
          }
        });
      })
      .catch(() => { /* keep the original wording already on the page */ });
  }

});

// ---------------------------------------------------------
// Hidden admin shortcut — press Ctrl+Z anywhere on the site
// (outside a text field) to jump to the admin login page.
// ---------------------------------------------------------
document.addEventListener('keydown', (e) => {
  if (!e.ctrlKey || e.metaKey || e.altKey || e.key.toLowerCase() !== 'z') return;
  const active = document.activeElement;
  const tag = active ? active.tagName.toLowerCase() : '';
  if (tag === 'input' || tag === 'textarea' || (active && active.isContentEditable)) return;
  e.preventDefault();
  window.location.href = 'admin/login.php';
});
