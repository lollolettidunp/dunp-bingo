import assert from 'node:assert/strict';
import {
  markMissedEntriesSeen,
  newMissedEntries,
  notifyMissedEntries,
  requestMissedNotificationPermission,
} from '../../resources/js/missed-notifications.js';

const seen = new Set();
markMissedEntriesSeen([{ id: 'old', user: 'Ada', text: 'Caffe' }], seen);
assert.deepEqual(
  newMissedEntries([
    { id: 'old', user: 'Ada', text: 'Caffe' },
    { id: 'new', user: 'Bruno', text: 'Riunione' },
  ], seen),
  [{ id: 'new', user: 'Bruno', text: 'Riunione' }],
  'initial entries are not notified, later entries are',
);

class PromptNotification {
  static permission = 'default';
  static requested = 0;
  static requestPermission() { this.requested += 1; }
}
requestMissedNotificationPermission(PromptNotification);
assert.equal(PromptNotification.requested, 1, 'permission is requested on page open when possible');

const sent = [];
class GrantedNotification {
  static permission = 'granted';
  constructor(title, options) {
    sent.push({ title, body: options.body });
  }
}
assert.equal(notifyMissedEntries([{ id: 'new', user: 'Bruno', text: 'Riunione' }], GrantedNotification), 1);
assert.deepEqual(sent, [{ title: 'Cosa potresti esserti perso', body: 'Bruno ha checkato Riunione' }]);

console.log('missed-notifications ok');
