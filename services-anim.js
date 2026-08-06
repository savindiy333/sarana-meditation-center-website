/**
 * Saraṇa Meditation Center — Smooth Frame Sequence Animation Player
 * Renders 300 sequential JPG frames (images/medi/ezgif-frame-001.jpg .. ezgif-frame-300.jpg)
 * on HTML5 Canvas using requestAnimationFrame for smooth, continuous playback.
 */

(function () {
  const canvas = document.getElementById('mediAnimCanvas');
  if (!canvas) return;

  const ctx = canvas.getContext('2d');
  const totalFrames = 300;
  const frames = [];
  let loadedCount = 0;
  let currentFrameIndex = 0;
  let lastFrameTime = 0;
  const fps = 30; // 30 FPS for smooth video sequence playback
  const frameInterval = 1000 / fps;
  let isPlaying = true;

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
    const parent = canvas.parentElement;
    if (!parent) return;
    const rect = parent.getBoundingClientRect();
    const dpr = Math.min(window.devicePixelRatio || 1, 2);

    canvas.width = rect.width * dpr;
    canvas.height = rect.height * dpr;

    canvas.style.width = '100%';
    canvas.style.height = '100%';

    drawFrame(currentFrameIndex);
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
    const img = frames[idx];
    if (img && img.complete && img.naturalWidth !== 0) {
      drawCover(img, canvas.width, canvas.height);
    }
  }

  function animLoop(timestamp) {
    if (isPlaying) {
      if (!lastFrameTime) lastFrameTime = timestamp;
      const delta = timestamp - lastFrameTime;

      if (delta >= frameInterval) {
        lastFrameTime = timestamp - (delta % frameInterval);
        currentFrameIndex = (currentFrameIndex + 1) % totalFrames;
        drawFrame(currentFrameIndex);
      }
    }
    requestAnimationFrame(animLoop);
  }

  window.addEventListener('resize', resizeCanvas, { passive: true });

  document.addEventListener('visibilitychange', () => {
    isPlaying = !document.hidden;
  });

  resizeCanvas();
  requestAnimationFrame(animLoop);
})();
