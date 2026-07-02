export function requestMissedNotificationPermission(notification = globalThis.Notification) {
  if (!notification || notification.permission !== 'default' || typeof notification.requestPermission !== 'function') return;
  try {
    notification.requestPermission()?.catch?.(() => {});
  } catch {
    // Browser may require a user gesture; the drawer still works without notifications.
  }
}

export function markMissedEntriesSeen(entries, seen) {
  entries.forEach((entry) => {
    if (entry.id) seen.add(entry.id);
  });
}

export function newMissedEntries(entries, seen) {
  return entries.filter((entry) => {
    if (!entry.id || seen.has(entry.id)) return false;
    seen.add(entry.id);
    return true;
  });
}

export function notifyMissedEntries(entries, notification = globalThis.Notification) {
  if (!notification || notification.permission !== 'granted') return 0;

  let sent = 0;
  for (const entry of entries) {
    new notification('Cosa potresti esserti perso', {
      body: `${entry.user} ha checkato ${entry.text}`,
    });
    sent += 1;
  }
  return sent;
}

export function readMissedEntries(root = document) {
  return [...root.querySelectorAll('[data-missed-entry-id]')].map((item) => ({
    id: item.dataset.missedEntryId,
    user: item.dataset.missedEntryUser || 'Qualcuno',
    text: item.dataset.missedEntryText || 'una casella',
  }));
}

export function installMissedNotifications(root = document, notification = globalThis.Notification) {
  requestMissedNotificationPermission(notification);

  const seen = new Set();
  markMissedEntriesSeen(readMissedEntries(root), seen);

  if (typeof MutationObserver === 'undefined') return;

  let queued = false;
  new MutationObserver(() => {
    if (queued) return;
    queued = true;
    queueMicrotask(() => {
      queued = false;
      notifyMissedEntries(newMissedEntries(readMissedEntries(root), seen), notification);
    });
  }).observe(root.body || root, { childList: true, subtree: true });
}
