// =========================================================
// SARAṆA — 3D hero: layered mandala rings + rising particles
// Built with three.js (loaded globally as THREE)
// =========================================================

(function () {
  const canvas = document.getElementById('hero-canvas');
  if (!canvas || typeof THREE === 'undefined') return;

  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  let width = canvas.clientWidth || window.innerWidth;
  let height = canvas.clientHeight || window.innerHeight;

  const scene = new THREE.Scene();

  const camera = new THREE.PerspectiveCamera(48, width / height, 0.1, 100);
  camera.position.set(0, 0.4, 9.2);

  const renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: true });
  renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
  renderer.setSize(width, height);

  // ---- lights ----
  scene.add(new THREE.AmbientLight(0xfff3d6, 0.9));
  const key = new THREE.PointLight(0xffe3a3, 1.4, 30);
  key.position.set(4, 3, 6);
  scene.add(key);
  const rim = new THREE.PointLight(0x8ca07c, 1, 30);
  rim.position.set(-5, -2, -4);
  scene.add(rim);

  // ---- mandala group: concentric wireframe rings, like an orbiting mandala ----
  const mandala = new THREE.Group();
  scene.add(mandala);

  const ringColors = [0xd9a441, 0x8ca07c, 0xe8c778, 0xb9822a];
  const ringCount = 6;
  const rings = [];

  for (let i = 0; i < ringCount; i++) {
    const radius = 1.6 + i * 0.62;
    const tube = 0.008 + (i % 2) * 0.004;
    const geo = new THREE.TorusGeometry(radius, tube, 10, 90);
    const mat = new THREE.MeshStandardMaterial({
      color: ringColors[i % ringColors.length],
      metalness: 0.35,
      roughness: 0.4,
      transparent: true,
      opacity: 0.55 - i * 0.04,
    });
    const ring = new THREE.Mesh(geo, mat);
    ring.rotation.x = Math.PI / 2 + (i * 0.18);
    ring.rotation.y = i * 0.35;
    ring.userData = {
      baseRotX: ring.rotation.x,
      baseRotY: ring.rotation.y,
      speed: 0.02 + i * 0.008,
      dir: i % 2 === 0 ? 1 : -1,
    };
    rings.push(ring);
    mandala.add(ring);
  }

  // central glowing core (subtle icosahedron, echoes the breathing circle overlay)
  const coreGeo = new THREE.IcosahedronGeometry(0.62, 1);
  const coreMat = new THREE.MeshStandardMaterial({
    color: 0xfff6df,
    emissive: 0xd9a441,
    emissiveIntensity: 0.35,
    metalness: 0.2,
    roughness: 0.55,
    transparent: true,
    opacity: 0.85,
    wireframe: true,
  });
  const core = new THREE.Mesh(coreGeo, coreMat);
  mandala.add(core);

  // ---- particle field: slow rising motes (like incense smoke / dust in temple light) ----
  const particleCount = 240;
  const positions = new Float32Array(particleCount * 3);
  const speeds = new Float32Array(particleCount);
  for (let i = 0; i < particleCount; i++) {
    const radius = 3 + Math.random() * 6;
    const angle = Math.random() * Math.PI * 2;
    positions[i * 3] = Math.cos(angle) * radius * (0.6 + Math.random() * 0.6);
    positions[i * 3 + 1] = -4 + Math.random() * 8;
    positions[i * 3 + 2] = Math.sin(angle) * radius * 0.6 - 1;
    speeds[i] = 0.004 + Math.random() * 0.01;
  }
  const particleGeo = new THREE.BufferGeometry();
  particleGeo.setAttribute('position', new THREE.BufferAttribute(positions, 3));
  const particleMat = new THREE.PointsMaterial({
    color: 0xe8c778,
    size: 0.045,
    transparent: true,
    opacity: 0.55,
    depthWrite: false,
  });
  const particles = new THREE.Points(particleGeo, particleMat);
  scene.add(particles);

  // ---- pointer parallax ----
  let targetX = 0, targetY = 0;
  let curX = 0, curY = 0;
  window.addEventListener('pointermove', (e) => {
    targetX = (e.clientX / window.innerWidth - 0.5) * 2;
    targetY = (e.clientY / window.innerHeight - 0.5) * 2;
  }, { passive: true });

  // ---- resize ----
  function onResize() {
    width = canvas.clientWidth || window.innerWidth;
    height = canvas.clientHeight || window.innerHeight;
    camera.aspect = width / height;
    camera.updateProjectionMatrix();
    renderer.setSize(width, height);
  }
  window.addEventListener('resize', onResize);

  // ---- animation loop ----
  const clock = new THREE.Clock();

  function tick() {
    const dt = clock.getDelta();
    const t = clock.elapsedTime;

    if (!reduceMotion) {
      rings.forEach(ring => {
        const d = ring.userData;
        ring.rotation.z += d.speed * d.dir * dt * 6;
        ring.rotation.x = d.baseRotX + Math.sin(t * 0.15 + d.speed * 10) * 0.06;
      });
      core.rotation.y += dt * 0.15;
      core.rotation.x += dt * 0.08;
      mandala.rotation.y += dt * 0.03;

      const posAttr = particleGeo.attributes.position;
      for (let i = 0; i < particleCount; i++) {
        let y = posAttr.getY(i) + speeds[i];
        if (y > 4.4) y = -4.4;
        posAttr.setY(i, y);
      }
      posAttr.needsUpdate = true;

      curX += (targetX - curX) * 0.03;
      curY += (targetY - curY) * 0.03;
      camera.position.x = curX * 0.6;
      camera.position.y = 0.4 - curY * 0.35;
      camera.lookAt(0, 0, 0);
    }

    renderer.render(scene, camera);
    requestAnimationFrame(tick);
  }

  tick();
})();
