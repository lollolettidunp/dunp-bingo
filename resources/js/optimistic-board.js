export const LINES = [
  ['row-0', [0, 1, 2, 3, 4]], ['row-1', [5, 6, 7, 8, 9]], ['row-2', [10, 11, 12, 13, 14]], ['row-3', [15, 16, 17, 18, 19]], ['row-4', [20, 21, 22, 23, 24]],
  ['col-0', [0, 5, 10, 15, 20]], ['col-1', [1, 6, 11, 16, 21]], ['col-2', [2, 7, 12, 17, 22]], ['col-3', [3, 8, 13, 18, 23]], ['col-4', [4, 9, 14, 19, 24]],
  ['diag-0', [0, 6, 12, 18, 24]], ['diag-1', [4, 8, 12, 16, 20]],
];

const pending = new Map();
let observerStarted = false;
let reapplyQueued = false;
const PENDING_MS = 4000;

export function completedLineIds(marked) {
  return LINES.filter(([, positions]) => positions.every((position) => marked.has(position))).map(([id]) => id);
}

export function newLineIds(previous, next) {
  const seen = new Set(previous);
  return next.filter((id) => !seen.has(id));
}

export function shouldHaveWinClass(position, lineIds) {
  return LINES.some(([id, positions]) => lineIds.includes(id) && positions.includes(position));
}

export function pendingMarkedState(current, entry, now) {
  return entry && entry.until > now ? entry.marked : current;
}

export function installOptimisticBoard({ showFace, showBingo }) {
  startPendingReapply();

  document.addEventListener('click', (event) => {
    const cell = event.target.closest('.cell[data-position]');
    if (!cell || cell.disabled || cell.classList.contains('bonus') || cell.closest('.readonly')) return;

    const board = cell.closest('.board');
    if (!board) return;

    const position = Number(cell.dataset.position);
    const marked = !cell.classList.contains('marked');
    pending.set(position, { marked, until: Date.now() + PENDING_MS });
    window.setTimeout(() => pending.delete(position), PENDING_MS);

    applyPending(board);

    if (marked) {
      window.__optimisticCellMarkedAt = Date.now();
      showFace(position);
      if (paintWinningLines(board).length > 0) {
        window.__optimisticBingoAt = Date.now();
        showBingo();
      }
    }
  }, true);
}

function startPendingReapply() {
  if (observerStarted || typeof MutationObserver === 'undefined') return;
  observerStarted = true;

  new MutationObserver(() => {
    if (reapplyQueued) return;
    reapplyQueued = true;
    queueMicrotask(() => {
      reapplyQueued = false;
      document.querySelectorAll('.board').forEach(applyPending);
    });
  }).observe(document.body, { attributes: true, attributeFilter: ['class', 'disabled'], childList: true, subtree: true });
}

function applyPending(board) {
  const now = Date.now();

  for (const cell of board.querySelectorAll('.cell[data-position]')) {
    const entry = pending.get(Number(cell.dataset.position));
    const marked = pendingMarkedState(cell.classList.contains('marked'), entry, now);
    cell.classList.toggle('marked', marked);
  }

  paintWinningLines(board);
}

function paintWinningLines(board) {
  const marked = new Set([...board.querySelectorAll('.cell.marked[data-position]')].map((cell) => Number(cell.dataset.position)));
  const previous = JSON.parse(board.dataset.optimisticLines || '[]');
  const next = completedLineIds(marked);

  board.dataset.optimisticLines = JSON.stringify(next);

  for (const cell of board.querySelectorAll('.cell[data-position]')) {
    cell.classList.toggle('win', shouldHaveWinClass(Number(cell.dataset.position), next));
  }

  return newLineIds(previous, next);
}
