const STORAGE_PREFIX = "dunp-bingo:";
const BONUS_INDEX = 12;
const BONUS_TEXT = "BONUS";
const FACE_FILES = [
  "alessandro-moscetta.jpg",
  "angelo-surano.jpg",
  "chiara-zamponi.jpg",
  "claudio-polito.jpg",
  "desiree-iafrate.jpg",
  "edoardo-andreoni.jpg",
  "emanuele-longhi.jpg",
  "francesca-cutini.jpg",
  "gabriele-simonetti.jpg",
  "giulia-bernardini.jpg",
  "giuseppe-pugliese.jpg",
  "laura-di-sante.jpg",
  "lorenzo-lettieri.jpg",
  "martina-marabitti.jpg",
  "sonia-mariucci.jpg",
  "tiziana-evangelisti.jpg"
];

const board = document.querySelector("#board");
const todayLabel = document.querySelector("#todayLabel");
const progressLabel = document.querySelector("#progressLabel");
const bingoBanner = document.querySelector("#bingoBanner");
const leaderboardButton = document.querySelector("#leaderboardButton");
const leaderboardDialog = document.querySelector("#leaderboardDialog");
const leaderboardList = document.querySelector("#leaderboardList");

let state = null;

start();
leaderboardButton.addEventListener("click", openLeaderboard);

async function start() {
  todayLabel.textContent = formatDate(new Date());

  try {
    const squares = await loadSquares();
    state = loadTodayState(squares);
    renderBoard();
    updateProgress();
  } catch (error) {
    board.innerHTML = `<div class="error">Impossibile caricare le caselle del bingo.</div>`;
  }
}

async function loadSquares() {
  const response = await fetch("bingo.json", { cache: "no-store" });

  if (!response.ok) {
    throw new Error("bingo.json non trovato.");
  }

  const data = await response.json();
  const squares = getAvailableSquares(data);

  if (squares.length < 24) {
    throw new Error("bingo.json deve contenere almeno 24 caselle.");
  }

  return squares;
}

function getAvailableSquares(data, day = getTodayName()) {
  if (!Array.isArray(data.squares)) {
    return [];
  }

  return data.squares
    .map(normalizeSquare)
    .filter(Boolean)
    .filter((square) => !isExcludedOn(square, day))
    .map((square) => square.text);
}

function normalizeSquare(square) {
  if (typeof square === "string" && square.trim()) {
    return { text: square };
  }

  if (square && typeof square.text === "string" && square.text.trim()) {
    return square;
  }

  return null;
}

function isExcludedOn(square, day) {
  const except = Array.isArray(square.except) ? square.except : [square.except];
  return except.some((value) => normalizeDay(value) === day);
}

function getTodayName() {
  return ["domenica", "lunedi", "martedi", "mercoledi", "giovedi", "venerdi", "sabato"][new Date().getDay()];
}

function normalizeDay(value) {
  return typeof value === "string"
    ? value.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase()
    : "";
}

async function openLeaderboard() {
  leaderboardList.innerHTML = `<li class="leaderboard-note">Caricamento...</li>`;
  leaderboardDialog.showModal();

  try {
    renderLeaderboard(await loadLeaderboard());
  } catch {
    leaderboardList.innerHTML = `<li class="leaderboard-note">Impossibile caricare la classifica.</li>`;
  }
}

async function loadLeaderboard() {
  const response = await fetch("leaderboard.json", { cache: "no-store" });

  if (!response.ok) {
    throw new Error("leaderboard.json non trovato.");
  }

  const data = await response.json();

  if (!Array.isArray(data.results)) {
    throw new Error("leaderboard.json deve contenere results.");
  }

  return data.results
    .filter((entry) => typeof entry.name === "string" && Number.isFinite(entry.score))
    .sort((first, second) => second.score - first.score);
}

function renderLeaderboard(entries) {
  if (entries.length === 0) {
    leaderboardList.innerHTML = `<li class="leaderboard-note">Nessun risultato inserito.</li>`;
    return;
  }

  leaderboardList.innerHTML = entries
    .map((entry) => `
      <li>
        <span>${escapeHtml(entry.name)}</span>
        <strong>${entry.score}</strong>
        ${entry.date ? `<small>Ultima Vittoria: ${escapeHtml(entry.date)}</small>` : ""}
      </li>
    `)
    .join("");
}

function loadTodayState(sourceSquares) {
  cleanupOldStorage();

  const key = getStorageKey();
  const saved = readJson(key);

  if (saved && saved.date === getDateId() && Array.isArray(saved.squares) && saved.squares.length === 25) {
    return saved;
  }

  const squares = buildDailySquares(sourceSquares);
  const freshState = {
    date: getDateId(),
    arrangementHash: hashText(squares.join("|")),
    squares,
    markedIndexes: [BONUS_INDEX],
    completedLines: []
  };

  saveState(freshState);
  return freshState;
}

function cleanupOldStorage() {
  const yesterday = new Date();
  yesterday.setDate(yesterday.getDate() - 1);
  const cutoff = getDateId(yesterday);
  const keys = Array.from({ length: localStorage.length }, (_, index) => localStorage.key(index));

  keys.forEach((key) => {
    if (key && key.startsWith(STORAGE_PREFIX) && key.slice(STORAGE_PREFIX.length) < cutoff) {
      localStorage.removeItem(key);
    }
  });
}

function buildDailySquares(sourceSquares) {
  const shuffled = shuffle(sourceSquares).slice(0, 24);
  shuffled.splice(BONUS_INDEX, 0, BONUS_TEXT);
  return shuffled;
}

function renderBoard() {
  board.innerHTML = "";

  state.squares.forEach((text, index) => {
    const cell = document.createElement("button");
    cell.type = "button";
    cell.className = "cell";
    cell.dataset.index = String(index);
    cell.innerHTML = `<span>${escapeHtml(text)}</span>`;

    if (index === BONUS_INDEX) {
      cell.classList.add("bonus", "marked");
      cell.disabled = true;
    }

    if (state.markedIndexes.includes(index)) {
      cell.classList.add("marked");
    }

    cell.addEventListener("dblclick", () => toggleCell(index));
    board.appendChild(cell);
  });
}

function toggleCell(index) {
  if (index === BONUS_INDEX) {
    return;
  }

  const wasMarked = state.markedIndexes.includes(index);

  if (wasMarked) {
    state.markedIndexes = state.markedIndexes.filter((markedIndex) => markedIndex !== index);
  } else {
    state.markedIndexes.push(index);
  }

  saveState(state);
  renderBoard();
  updateProgress();

  if (!wasMarked) {
    showFaceBubble(index);
  }

  checkCompletedLines();
}

function checkCompletedLines() {
  const completedLines = getCompletedLines();
  const completedLineIds = completedLines.map((line) => line.id);
  const newLines = completedLines.filter((line) => !state.completedLines.includes(line.id));

  state.completedLines = completedLineIds;
  saveState(state);

  if (newLines.length === 0) {
    return;
  }

  newLines.forEach((line) => animateLine(line.indexes));
  showBingo();
}

function getCompletedLines() {
  const lines = [
    { id: "row-0", indexes: [0, 1, 2, 3, 4] },
    { id: "row-1", indexes: [5, 6, 7, 8, 9] },
    { id: "row-2", indexes: [10, 11, 12, 13, 14] },
    { id: "row-3", indexes: [15, 16, 17, 18, 19] },
    { id: "row-4", indexes: [20, 21, 22, 23, 24] },
    { id: "col-0", indexes: [0, 5, 10, 15, 20] },
    { id: "col-1", indexes: [1, 6, 11, 16, 21] },
    { id: "col-2", indexes: [2, 7, 12, 17, 22] },
    { id: "col-3", indexes: [3, 8, 13, 18, 23] },
    { id: "col-4", indexes: [4, 9, 14, 19, 24] },
    { id: "diag-0", indexes: [0, 6, 12, 18, 24] },
    { id: "diag-1", indexes: [4, 8, 12, 16, 20] }
  ];

  return lines.filter((line) => line.indexes.every((index) => state.markedIndexes.includes(index)));
}

function animateLine(indexes) {
  indexes.forEach((index) => {
    const cell = board.querySelector(`[data-index="${index}"]`);
    if (!cell) return;

    cell.classList.remove("line-hit");
    window.setTimeout(() => cell.classList.add("line-hit"), 20);
  });
}

function showBingo() {
  bingoBanner.classList.remove("show");
  window.setTimeout(() => bingoBanner.classList.add("show"), 20);

  if (window.confetti) {
    window.confetti({
      particleCount: 120,
      spread: 80,
      origin: { y: 0.7 }
    });
  }
}

function showFaceBubble(index) {
  if (FACE_FILES.length === 0) {
    return;
  }

  const cell = board.querySelector(`[data-index="${index}"]`);

  if (!cell) {
    return;
  }

  const bubble = document.createElement("div");
  const image = document.createElement("img");

  bubble.className = "face-bubble";
  bubble.setAttribute("aria-hidden", "true");
  image.src = `faces/${getRandomFaceFile()}`;
  image.alt = "";

  bubble.appendChild(image);
  document.body.appendChild(bubble);

  positionFaceBubble(bubble, cell);
  window.setTimeout(() => bubble.remove(), 2600);
}

function positionFaceBubble(bubble, cell) {
  const cellRect = cell.getBoundingClientRect();
  const bubbleRect = bubble.getBoundingClientRect();
  const margin = 12;
  const left = clamp(
    cellRect.left + cellRect.width / 2 - bubbleRect.width / 2,
    margin,
    window.innerWidth - bubbleRect.width - margin
  );
  const top = clamp(
    cellRect.top - bubbleRect.height * 0.55,
    margin,
    window.innerHeight - bubbleRect.height - margin
  );

  bubble.style.left = `${left}px`;
  bubble.style.top = `${top}px`;
}

function getRandomFaceFile() {
  return FACE_FILES[Math.floor(Math.random() * FACE_FILES.length)];
}

function clamp(value, min, max) {
  return Math.min(Math.max(value, min), max);
}

function updateProgress() {
  progressLabel.textContent = `${state.markedIndexes.length}/25`;
}

function saveState(nextState) {
  localStorage.setItem(getStorageKey(), JSON.stringify(nextState));
}

function readJson(key) {
  try {
    return JSON.parse(localStorage.getItem(key));
  } catch {
    return null;
  }
}

function shuffle(items) {
  const copy = [...items];

  for (let index = copy.length - 1; index > 0; index -= 1) {
    const randomIndex = Math.floor(Math.random() * (index + 1));
    [copy[index], copy[randomIndex]] = [copy[randomIndex], copy[index]];
  }

  return copy;
}

function getStorageKey() {
  return `${STORAGE_PREFIX}${getDateId()}`;
}

function getDateId(date = new Date()) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");
  return `${year}-${month}-${day}`;
}

function formatDate(date) {
  return new Intl.DateTimeFormat("it-IT", {
    day: "2-digit",
    month: "long",
    year: "numeric"
  }).format(date);
}

function hashText(text) {
  let hash = 2166136261;

  for (let index = 0; index < text.length; index += 1) {
    hash ^= text.charCodeAt(index);
    hash += (hash << 1) + (hash << 4) + (hash << 7) + (hash << 8) + (hash << 24);
  }

  return (hash >>> 0).toString(16);
}

function escapeHtml(value) {
  return value
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}
