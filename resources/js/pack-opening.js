// Beats of the reveal: the pack charges up, bursts, and the stickers snap into the grid.
const CHARGE_MS = 420; // shortest build-up before the pack may burst
const BURST_MS = 360; // stickers flying out to where they hang
const HANG_MS = 110; // the beat they stay in the air
const STAGGER_MS = 14; // gap between two stickers leaving for the grid
const HOME_MS = 360; // from the air to the slot
const SETTLE_MS = 150; // the slap
const LEAVE_MS = 360;
const GIVE_UP_MS = 10000;
const BONUS_POSITION = 12;
const BLAST = 'cubic-bezier(.08, .82, .17, 1)';
const SNAP = 'cubic-bezier(.7, 0, .25, 1)';
const EASE_OUT = 'cubic-bezier(.2, .9, .2, 1)';
const CHROME = '.board-status, .instructions, .board-note, .board-progress, .special-rail, .hint';
const SHARD_COLORS = ['#fffdf8', '#d9f99d', '#ffd166', '#7dd3fc'];

/** Scrambled, so the grid fills in from everywhere at once; the free centre square lands last. */
export function dealOrder(count, bonus = BONUS_POSITION) {
  const positions = Array.from({ length: count }, (_, position) => position).filter((position) => position !== bonus);
  let step = 7;
  while (positions.length > 1 && gcd(step, positions.length) !== 1) step += 1;

  const scrambled = positions.map((_, index) => positions[(index * step) % positions.length]);
  return bonus < count ? [...scrambled, bonus] : scrambled;
}

/** Where a sticker hangs after the burst: a wide fan above the pack, kept `pad` inside the viewport. */
export function scatterPoint(mouth, index, viewport, pad = 40) {
  // Two unrelated low-discrepancy steps: the cloud fills evenly, with no clumps and no rings.
  const turn = (0.5 + index * 0.7548776662) % 1;
  const reach = 0.35 + 0.65 * Math.sqrt((0.5 + index * 0.569840291) % 1);
  const angle = ((-90 + (turn - 0.5) * 240) * Math.PI) / 180;
  // Sized to the room actually left around the mouth, so nothing piles up against an edge.
  const radiusX = Math.min(viewport.width * 0.44, 520, Math.max(120, Math.min(mouth.x, viewport.width - mouth.x) - pad));
  const radiusY = Math.min(viewport.height * 0.36, 340, Math.max(120, mouth.y - pad));

  return {
    x: clamp(mouth.x + Math.cos(angle) * radiusX * reach, pad, viewport.width - pad),
    y: clamp(mouth.y + Math.sin(angle) * radiusY * reach, pad, viewport.height - pad),
  };
}

/** How a sticker leans while it hangs in the air: alternating, and small enough to stay readable. */
export function flightTilt(index) {
  return (index % 2 ? 1 : -1) * (8 + ((index * 37) % 27));
}

export function installPackOpening() {
  let fx = null;
  let chargedAt = 0;
  let giveUp = null;

  // Capture phase: the pack must be lifted before Livewire swaps the page under it.
  document.addEventListener('click', (event) => {
    const opener = event.target.closest?.('[data-pack-opener]');
    const slot = opener?.querySelector('.pack-slot');
    if (!slot || opener.disabled || fx || prefersReducedMotion()) return;

    fx = liftPack(slot);
    chargedAt = performance.now();
    document.documentElement.dataset.packOpening = '';
    giveUp = window.setTimeout(fizzle, GIVE_UP_MS);
  }, true);

  document.addEventListener('board-unpacked', () => {
    const board = document.querySelector('.board:not(.readonly)');
    if (!board) return fizzle();

    if (prefersReducedMotion()) {
      board.closest('section')?.animate([{ opacity: 0 }, { opacity: 1 }], { duration: 200 });
      return fizzle();
    }

    // The pack keeps charging until it has built up enough; the stage it came from is already gone.
    const stage = fx ?? { root: fxRoot(), ghost: null };
    const wait = fx ? Math.max(0, CHARGE_MS - (performance.now() - chargedAt)) : 0;
    window.clearTimeout(giveUp);
    delete document.documentElement.dataset.packOpening;
    fx = null;

    const done = explode(board, stage, wait);
    window.setTimeout(() => stage.root.remove(), done);
  });

  document.addEventListener('board-pack-failed', fizzle);

  /** No board came out: the pack drops away and the page shows whatever it has. */
  function fizzle() {
    window.clearTimeout(giveUp);
    delete document.documentElement.dataset.packOpening;

    const failed = fx;
    fx = null;
    if (!failed) return;

    failed.ghost.classList.add('is-leaving');
    window.setTimeout(() => failed.root.remove(), LEAVE_MS);
  }
}

/** A clone of the pack pinned over the original, outside Livewire's reach. */
function liftPack(slot) {
  const rect = slot.getBoundingClientRect();
  const root = fxRoot();
  const ghost = document.createElement('div');
  ghost.className = 'pack-ghost';
  ghost.style.left = `${rect.left}px`;
  ghost.style.top = `${rect.top}px`;
  ghost.style.width = `${rect.width}px`;
  ghost.appendChild(slot.querySelector('.pack').cloneNode(true));
  root.appendChild(ghost);

  return { root, ghost };
}

function fxRoot() {
  const root = document.createElement('div');
  root.className = 'pack-fx';
  root.setAttribute('aria-hidden', 'true');
  document.body.appendChild(root);

  return root;
}

/** Blows the pack open and sends every cell to its slot; returns the ms until it is all over. */
function explode(board, { root, ghost }, wait) {
  const cells = new Map([...board.querySelectorAll('.cell[data-position]')].map((cell) => [Number(cell.dataset.position), cell]));
  const viewport = { width: window.innerWidth, height: window.innerHeight };
  const mouth = mouthOf(ghost, board);

  // Measure everything before the first animation shifts a rect.
  const flights = dealOrder(cells.size)
    .filter((position) => cells.has(position))
    .map((position, index) => {
      const cell = cells.get(position);
      const rect = cell.getBoundingClientRect();
      const centre = { x: rect.left + rect.width / 2, y: rect.top + rect.height / 2 };
      // Half the diagonal: a tumbling sticker must never poke out and cause a scrollbar.
      const air = scatterPoint(mouth, index, viewport, Math.hypot(rect.width, rect.height) * 0.4 + 8);

      return { cell, index, from: { x: mouth.x - centre.x, y: mouth.y - centre.y }, air: { x: air.x - centre.x, y: air.y - centre.y } };
    });

  ghost?.style.setProperty('--burst-wait', `${wait}ms`);
  ghost?.classList.add('is-bursting');
  boom(root, mouth, (ghost?.offsetWidth ?? 240) * 1.5, wait);
  shards(root, mouth, viewport, wait);

  board.closest('section')?.querySelectorAll(CHROME).forEach((element) => {
    element.animate(
      [{ opacity: 0, transform: 'translateY(10px)' }, { opacity: 1, transform: 'none' }],
      { duration: 280, delay: wait + 120, easing: EASE_OUT, fill: 'backwards' },
    );
  });
  board.closest('.board-wrap')?.animate([{ opacity: 0 }, { opacity: 1 }], { duration: 160, delay: wait, fill: 'backwards' });

  flights.forEach(({ cell, index, from, air }) => {
    // One full turn on the way out, hanging almost upright, and one back on the way in.
    const tilt = flightTilt(index);
    const spin = Math.sign(tilt) * 360 + tilt;
    const last = index === flights.length - 1;
    const hang = HANG_MS + index * STAGGER_MS;
    const total = BURST_MS + hang + HOME_MS + SETTLE_MS;

    cell.animate([
      { transform: `translate(${from.x}px, ${from.y}px) scale(.14) rotate(0deg)`, opacity: 0, easing: BLAST },
      { transform: `translate(${air.x}px, ${air.y}px) scale(.74) rotate(${spin}deg)`, opacity: 1, offset: BURST_MS / total, easing: 'ease-in-out' },
      { transform: `translate(${air.x}px, ${air.y + 14}px) scale(.78) rotate(${spin + tilt / 4}deg)`, opacity: 1, offset: (BURST_MS + hang) / total, easing: SNAP },
      { transform: `translate(0px, 0px) scale(${last ? 1.24 : 1.1}) rotate(${-tilt / 4}deg)`, opacity: 1, offset: 1 - SETTLE_MS / total, easing: 'ease-out' },
      { transform: 'translate(0px, 0px) scale(1) rotate(0deg)', opacity: 1 },
    ], { duration: total, delay: wait, fill: 'backwards' });
  });

  return wait + BURST_MS + HANG_MS + flights.length * STAGGER_MS + HOME_MS + SETTLE_MS + 400;
}

/** The comic-book flash behind the pack at the moment it pops. */
function boom(root, mouth, size, wait) {
  const spikes = (inner, outer, low) => Array.from({ length: 32 }, (_, index) => {
    const radius = index % 2 ? inner : index % 4 ? outer : low;
    const angle = (Math.PI * index) / 16;

    return `${(radius * Math.cos(angle)).toFixed(1)},${(radius * Math.sin(angle)).toFixed(1)}`;
  }).join(' ');

  root.insertAdjacentHTML('afterbegin', `<svg class="pack-boom" viewBox="-100 -100 200 200" width="${size}" height="${size}" style="left:${mouth.x - size / 2}px;top:${mouth.y - size / 2}px"><polygon points="${spikes(60, 100, 84)}" fill="#ffd166"/><polygon points="${spikes(34, 62, 50)}" fill="#fffdf8"/></svg>`);

  root.firstElementChild.animate([
    { transform: 'scale(.12) rotate(-14deg)', opacity: 1, easing: BLAST },
    { transform: 'scale(1) rotate(0deg)', opacity: 1, offset: 0.45, easing: 'ease-in' },
    { transform: 'scale(1.22) rotate(10deg)', opacity: 0 },
  ], { duration: 460, delay: wait, fill: 'both' });
}

/** Loose stickers thrown every way by the blast; pure debris, the real cells fly separately. */
function shards(root, mouth, viewport, wait) {
  const count = viewport.width < 560 ? 18 : 28;
  const far = Math.max(viewport.width, viewport.height);

  for (let index = 0; index < count; index += 1) {
    const shard = document.createElement('i');
    const width = 26 + Math.random() * 22;
    const height = width * (1.1 + Math.random() * 0.25);
    const angle = Math.random() * Math.PI * 2;
    const reach = far * (0.3 + Math.random() * 0.45);
    // Thrown out and a little up, then gravity takes over.
    const x = Math.cos(angle) * reach;
    const y = Math.sin(angle) * reach - reach * 0.25;
    const spin = (Math.random() - 0.5) * 900;

    shard.className = 'pack-shard';
    shard.style.width = `${width}px`;
    shard.style.height = `${height}px`;
    shard.style.left = `${mouth.x - width / 2}px`;
    shard.style.top = `${mouth.y - height / 2}px`;
    shard.style.background = SHARD_COLORS[index % SHARD_COLORS.length];
    root.appendChild(shard);

    shard.animate([
      { transform: 'translate(0px, 0px) scale(.2) rotate(0deg)', opacity: 0, easing: BLAST },
      { transform: `translate(${x * 0.82}px, ${y * 0.82}px) scale(1) rotate(${spin * 0.7}deg)`, opacity: 1, offset: 0.45, easing: 'cubic-bezier(.5, 0, .9, .6)' },
      { transform: `translate(${x}px, ${y + 200}px) scale(.8) rotate(${spin}deg)`, opacity: 0 },
    ], { duration: 760 + Math.random() * 420, delay: wait + Math.random() * 60, fill: 'both' });
  }
}

/** The top edge of the pack; with no pack on screen, the blast goes off just above the board. */
function mouthOf(ghost, board) {
  const rect = (ghost ?? board).getBoundingClientRect();

  return { x: rect.left + rect.width / 2, y: ghost ? rect.top + rect.height * 0.14 : rect.top - 40 };
}

function gcd(a, b) {
  return b ? gcd(b, a % b) : a;
}

function clamp(value, min, max) {
  return Math.min(Math.max(value, min), max);
}

function prefersReducedMotion() {
  return window.matchMedia?.('(prefers-reduced-motion: reduce)').matches ?? false;
}
