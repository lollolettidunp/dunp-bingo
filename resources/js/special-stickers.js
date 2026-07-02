export function specialDragClasses(state) {
  return {
    board: state === 'start' || state === 'enter' ? 'is-receiving-sticker' : '',
    sticker: state === 'start' ? 'is-dragging' : '',
    target: state === 'enter' ? 'is-sticker-target' : state === 'drop' ? 'is-sticker-applied' : '',
  };
}

export function installSpecialStickers() {
  let draggedId = null;

  document.addEventListener('dragstart', (event) => {
    const sticker = event.target.closest('.special-sticker[data-special-cell-id]');
    if (!sticker) return;

    draggedId = sticker.dataset.specialCellId;
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', draggedId);
    event.dataTransfer.setDragImage(sticker, sticker.offsetWidth / 2, sticker.offsetHeight / 2);
    sticker.classList.add(specialDragClasses('start').sticker);
    document.querySelector('.board')?.classList.add(specialDragClasses('start').board);
  });

  document.addEventListener('dragend', (event) => {
    event.target.closest('.special-sticker')?.classList.remove('is-dragging');
    clearStickerDragState();
    draggedId = null;
  });

  document.addEventListener('dragover', (event) => {
    const cell = stickerTarget(event.target);
    if (!cell || !draggedId) return;
    event.preventDefault();
    event.dataTransfer.dropEffect = 'move';
  });

  document.addEventListener('dragenter', (event) => {
    const cell = stickerTarget(event.target);
    if (!cell || !draggedId) return;
    clearStickerTargets();
    cell.classList.add(specialDragClasses('enter').target);
  });

  document.addEventListener('dragleave', (event) => {
    const cell = stickerTarget(event.target);
    if (!cell || cell.contains(event.relatedTarget)) return;
    cell.classList.remove('is-sticker-target');
  });

  document.addEventListener('drop', (event) => {
    const cell = stickerTarget(event.target);
    if (!cell) return;

    const id = Number(event.dataTransfer.getData('text/plain') || draggedId);
    const position = Number(cell.dataset.position);
    if (!id || Number.isNaN(position)) return;

    event.preventDefault();
    cell.classList.remove('is-sticker-target');
    cell.classList.add(specialDragClasses('drop').target);
    window.setTimeout(() => cell.classList.remove('is-sticker-applied'), 700);
    clearStickerDragState();
    draggedId = null;
  });

  document.addEventListener('special-cell-applied', (event) => {
    const cell = document.querySelector(`[data-position="${event.detail?.position}"]`);
    cell?.classList.add('is-sticker-applied');
    if (cell) window.setTimeout(() => cell.classList.remove('is-sticker-applied'), 700);
    clearStickerDragState();
  });
}

function stickerTarget(target) {
  const cell = target.closest?.('.cell[data-position]:not(.bonus):not(:disabled)');
  return cell?.closest('.readonly') ? null : cell;
}

function clearStickerDragState() {
  document.querySelector('.board')?.classList.remove('is-receiving-sticker');
  document.querySelectorAll('.special-sticker.is-dragging').forEach((sticker) => sticker.classList.remove('is-dragging'));
  clearStickerTargets();
}

function clearStickerTargets() {
  document.querySelectorAll('.cell.is-sticker-target').forEach((cell) => cell.classList.remove('is-sticker-target'));
}
