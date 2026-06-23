const FACE_FILES = [
  'alessandro-moscetta.jpg','angelo-surano.jpg','chiara-zamponi.jpg','claudio-polito.jpg',
  'desiree-iafrate.jpg','edoardo-andreoni.jpg','emanuele-longhi.jpg','francesca-cutini.jpg',
  'gabriele-simonetti.jpg','giulia-bernardini.jpg','giuseppe-pugliese.jpg','laura-di-sante.jpg',
  'lorenzo-lettieri.jpg','martina-marabitti.jpg','sonia-mariucci.jpg','tiziana-evangelisti.jpg'
];

document.addEventListener('bingo-completed', () => {
  window.confetti?.({ particleCount: 120, spread: 80, origin: { y: 0.7 } });
  document.querySelector('#bingoBanner')?.classList.add('show');
});

document.addEventListener('cell-marked', (event) => {
  const file = FACE_FILES[Math.floor(Math.random() * FACE_FILES.length)];
  showFaceBubble(event.detail.position, `/faces/${file}`);
});

function showFaceBubble(position, src) {
  const cell = document.querySelector(`[data-position="${position}"]`);
  if (!cell) return;
  const bubble = document.createElement('div');
  const image = document.createElement('img');
  bubble.className = 'face-bubble';
  bubble.setAttribute('aria-hidden', 'true');
  image.src = src;
  image.alt = '';
  bubble.appendChild(image);
  document.body.appendChild(bubble);
  positionFaceBubble(bubble, cell);
  window.setTimeout(() => bubble.remove(), 2600);
}

function positionFaceBubble(bubble, cell) {
  const cellRect = cell.getBoundingClientRect();
  const bubbleRect = bubble.getBoundingClientRect();
  const margin = 12;
  bubble.style.left = `${clamp(cellRect.left + cellRect.width / 2 - bubbleRect.width / 2, margin, window.innerWidth - bubbleRect.width - margin)}px`;
  bubble.style.top = `${clamp(cellRect.top - bubbleRect.height * 0.55, margin, window.innerHeight - bubbleRect.height - margin)}px`;
}

function clamp(value, min, max) {
  return Math.min(Math.max(value, min), max);
}
