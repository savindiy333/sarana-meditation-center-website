/**
 * Saraṇa Meditation Center — Full-Page 3D Scroll Sequence Animation Player
 * Scrubs 300 sequential JPG frames (images/medi/ezgif-frame-001.jpg .. ezgif-frame-300.jpg)
 * smoothly based on scroll position across retreats.html with LERP interpolation.
 */

(function () {
  const canvas = document.getElementById('scrollAnimCanvas');
  if (!canvas) return;

  const ctx = canvas.getContext('2d');
  const totalFrames = 300;
  const frames = [];
  let loadedCount = 0;

  let targetFrame = 0;
  let currentFrame = 0;
  let isTicking = false;

  const progressBar = document.getElementById('scrollProgress');

  // Preload all 300 images into memory
  for (let i = 1; i <= totalFrames; i++) {
    const img = new Image();
    const pad = String(i).padStart(3, '0');
    img.src = `images/medi/ezgif-frame-${pad}.jpg`;

    img.onload = () => {
      loadedCount++;
      if (loadedCount === 1) {
        resizeCanvas();
        drawFrame(0);
      }
    };
    frames.push(img);
  }

  function resizeCanvas() {
    const dpr = Math.min(window.devicePixelRatio || 1, 2);
    canvas.width = window.innerWidth * dpr;
    canvas.height = window.innerHeight * dpr;

    canvas.style.width = '100vw';
    canvas.style.height = '100vh';

    drawFrame(Math.round(currentFrame));
  }

  function drawCover(img, cw, ch) {
    if (!img || !img.complete || img.naturalWidth === 0) return;

    const iw = img.naturalWidth;
    const ih = img.naturalHeight;
    const imgAspect = iw / ih;
    const canvasAspect = cw / ch;

    let drawW, drawH, offX, offY;

    if (canvasAspect > imgAspect) {
      drawW = cw;
      drawH = cw / imgAspect;
      offX = 0;
      offY = (ch - drawH) / 2;
    } else {
      drawW = ch * imgAspect;
      drawH = ch;
      offX = (cw - drawW) / 2;
      offY = 0;
    }

    ctx.clearRect(0, 0, cw, ch);
    ctx.drawImage(img, offX, offY, drawW, drawH);
  }

  function drawFrame(idx) {
    const frameIndex = Math.max(0, Math.min(totalFrames - 1, idx));
    const img = frames[frameIndex];
    if (img && img.complete && img.naturalWidth !== 0) {
      drawCover(img, canvas.width, canvas.height);
    }
  }

  function updateScrollTarget() {
    const docHeight = document.documentElement.scrollHeight - window.innerHeight;
    if (docHeight <= 0) return;
    const scrollTop = window.scrollY || window.pageYOffset || 0;
    const scrollFraction = Math.max(0, Math.min(1, scrollTop / docHeight));
    targetFrame = scrollFraction * (totalFrames - 1);

    if (progressBar) {
      progressBar.style.width = (scrollFraction * 100) + '%';
    }
  }

  function animLoop() {
    const diff = targetFrame - currentFrame;
    if (Math.abs(diff) > 0.01) {
      currentFrame += diff * 0.14;
      drawFrame(Math.round(currentFrame));
      requestAnimationFrame(animLoop);
    } else {
      currentFrame = targetFrame;
      drawFrame(Math.round(currentFrame));
      isTicking = false;
    }
  }

  function onScroll() {
    updateScrollTarget();
    if (!isTicking) {
      isTicking = true;
      requestAnimationFrame(animLoop);
    }
  }

  window.addEventListener('scroll', onScroll, { passive: true });
  window.addEventListener('resize', () => {
    resizeCanvas();
    updateScrollTarget();
  }, { passive: true });

  resizeCanvas();
  updateScrollTarget();
  onScroll();
})();
