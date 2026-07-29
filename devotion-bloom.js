// =========================================================
// SARAṆA — devotion lotus bloom (ported from the blossom
// section's GSAP + ScrollTrigger petal-bloom effect)
// =========================================================
document.addEventListener('DOMContentLoaded', () => {

  if (typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') return;
  gsap.registerPlugin(ScrollTrigger);

  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  const devotionBloom = document.getElementById('devotionBloom');
  const devotionPhoto = devotionBloom ? devotionBloom.querySelector('.devotion-photo') : null;
  const devotionGlow = devotionBloom ? devotionBloom.querySelector('.devotion-glow') : null;

  if (!devotionBloom) return;

  if (reduceMotion) {
    // show the fully bloomed state immediately, no scroll-driven animation
    gsap.set('#devotionBloom .petal-center .petal-scale', { scale: 1, filter: 'grayscale(0%)', opacity: .85 });
    gsap.set('#devotionBloom .petal-mid-l .petal-scale, #devotionBloom .petal-mid-r .petal-scale', { scale: 1, filter: 'grayscale(0%)', opacity: .58 });
    gsap.set('#devotionBloom .petal-outer-l .petal-scale, #devotionBloom .petal-outer-r .petal-scale', { scale: 1, filter: 'grayscale(0%)', opacity: .5 });
    if (devotionPhoto) gsap.set(devotionPhoto, { opacity: 1, y: 0, scale: 1 });
    if (devotionGlow) gsap.set(devotionGlow, { opacity: 1 });
    return;
  }

  // center petal opens first, then the middle pair, then the outer pair fans out last.
  // Each layer blooms to its own translucent opacity (matching the original layered
  // fan design) instead of full opacity — full opacity on every petal merges the
  // overlapping shapes into one solid blob instead of a delicate fan.
  const petalTargets = [
    { sel: '#devotionBloom .petal-center .petal-scale',  opacity: .85 },
    { sel: '#devotionBloom .petal-mid-l .petal-scale',   opacity: .58 },
    { sel: '#devotionBloom .petal-mid-r .petal-scale',   opacity: .58 },
    { sel: '#devotionBloom .petal-outer-l .petal-scale', opacity: .5 },
    { sel: '#devotionBloom .petal-outer-r .petal-scale', opacity: .5 }
  ];

  const bloomTl = gsap.timeline({
    scrollTrigger: {
      trigger: '#devotionBloom',
      start: 'top 70%',
      end: 'bottom 40%',
      toggleActions: 'play reverse play reverse'
    },
    defaults: { ease: 'back.out(1.5)' }
  });

  if (devotionGlow) {
    bloomTl.to(devotionGlow, { opacity: 1, duration: .9, ease: 'power2.out' }, 0);
  }

  // Petals fan open from a closed, pale bud — center first, then outward pairs
  petalTargets.forEach((p, i) => {
    bloomTl.to(p.sel, {
      scale: 1,
      filter: 'grayscale(0%)',
      opacity: p.opacity,
      duration: 1.1,
      ease: 'back.out(1.7)'
    }, i * .12);
  });

  // The statue photo rises into the bloomed center (popup effect)
  if (devotionPhoto) {
    bloomTl.to(devotionPhoto, {
      opacity: 1,
      y: 0,
      scale: 1,
      duration: .85,
      ease: 'back.out(1.6)'
    }, .4);
  }

  // When scrolling back up, the petals and photo return automatically to their
  // closed/hidden state — ScrollTrigger's toggleActions handles the reverse.
});
