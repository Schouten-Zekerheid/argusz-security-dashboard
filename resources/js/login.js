const ec = document.body.dataset.ec;
const isError = document.body.dataset.hasError === '1';

const irisOuter = isError ? 'hsla(0, 60%, 68%, 0.65)' : 'hsla(221, 70%, 68%, 0.65)';
const irisInner = isError ? 'hsl(0, 55%, 58%)' : 'hsl(224, 65%, 62%)';
const glowColor = isError ? 'hsla(0, 70%, 70%, 0.2)' : 'hsla(217, 80%, 70%, 0.2)';

const NS = 'http://www.w3.org/2000/svg';
const container = document.getElementById('eyes-bg');
const eyes = [];

function el(tag, attrs) {
    const e = document.createElementNS(NS, tag);
    for (const [k, v] of Object.entries(attrs)) e.setAttribute(k, v);
    return e;
}

function spawnEye(x, y, size, opacity) {
    const wrapper = document.createElement('div');
    wrapper.style.cssText = `
        position: fixed;
        left: ${x}px;
        top: ${y}px;
        transform: translate(-50%, -50%);
        opacity: ${opacity};
        filter: drop-shadow(0 0 6px ${glowColor});
        transition: opacity 0.2s ease;
    `;

    const svg = el('svg', {
        viewBox: '0 0 200 120',
        width: size,
        height: size * 0.6,
        fill: 'none',
    });

    // Eyelashes top
    svg.appendChild(el('path', {
        d: 'M30 60 Q100 10 170 60',
        stroke: `hsla(${ec}, 0.15)`,
        'stroke-width': '1',
    }));

    // Eye white
    svg.appendChild(el('ellipse', {
        cx: 100, cy: 60, rx: 70, ry: 40,
        fill: 'hsla(222, 59%, 9%, 0.95)',
        stroke: `hsla(${ec}, 0.3)`,
        'stroke-width': '1.5',
    }));

    // Eyelashes bottom
    svg.appendChild(el('path', {
        d: 'M30 60 Q100 110 170 60',
        stroke: `hsla(${ec}, 0.15)`,
        'stroke-width': '1',
    }));

    // Iris mover group
    const mover = el('g', {});

    mover.appendChild(el('circle', { cx: 100, cy: 60, r: 22, fill: irisOuter }));
    mover.appendChild(el('circle', { cx: 100, cy: 60, r: 14, fill: irisInner }));
    mover.appendChild(el('circle', { cx: 100, cy: 60, r: 7,  fill: 'hsl(223, 58%, 7%)' }));
    mover.appendChild(el('ellipse', {
        cx: 107, cy: 55, rx: 3, ry: 2,
        fill: 'hsla(214, 95%, 93%, 0.6)',
        transform: 'rotate(-20 107 55)',
    }));

    svg.appendChild(mover);
    wrapper.appendChild(svg);
    container.appendChild(wrapper);

    eyes.push({ mover, x, y, wrapper, baseOpacity: opacity });
}

function scatter(count) {
    const w = window.innerWidth;
    const h = window.innerHeight;

    // Avoid the center login area
    const cx = w / 2, cy = h / 2;
    const safeRadius = 220;

    let placed = 0;
    let attempts = 0;

    while (placed < count && attempts < count * 10) {
        attempts++;
        const x = Math.random() * w;
        const y = Math.random() * h;
        const dist = Math.hypot(x - cx, y - cy);

        if (dist < safeRadius) continue;

        const size = 28 + Math.random() * 56;
        const opacity = 0.2 + Math.random() * 0.2;
        spawnEye(x, y, size, opacity);
        placed++;
    }
}

scatter(45);

// Random blink: fade out → short pause → fade back in
function blinkRandom() {
    const eye = eyes[Math.floor(Math.random() * eyes.length)];
    eye.wrapper.style.opacity = 0;

    setTimeout(() => {
        eye.wrapper.style.opacity = eye.baseOpacity;
    }, 300 + Math.random() * 400);

    setTimeout(blinkRandom, 600 + Math.random() * 1200);
}

for (let i = 0; i < 6; i++) {
    setTimeout(blinkRandom, Math.random() * 1000);
}

// Single RAF-batched mousemove handler
let mouseX = window.innerWidth / 2;
let mouseY = window.innerHeight / 2;
let ticking = false;

document.addEventListener('mousemove', (e) => {
    mouseX = e.clientX;
    mouseY = e.clientY;

    if (!ticking) {
        requestAnimationFrame(() => {
            eyes.forEach(({ mover, x, y }) => {
                const dx = mouseX - x;
                const dy = mouseY - y;
                const dist = Math.hypot(dx, dy);
                const angle = Math.atan2(dy, dx);
                const factor = Math.min(dist / 180, 1);

                const offsetX = Math.cos(angle) * 18 * factor;
                const offsetY = Math.sin(angle) * 10 * factor;

                mover.setAttribute('transform', `translate(${offsetX}, ${offsetY})`);
            });
            ticking = false;
        });
        ticking = true;
    }
});
