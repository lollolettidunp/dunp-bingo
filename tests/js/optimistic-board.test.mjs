import assert from 'node:assert/strict';
import { completedLineIds, newLineIds, pendingMarkedState, shouldHaveWinClass } from '../../resources/js/optimistic-board.js';

assert.deepEqual(completedLineIds(new Set([0, 1, 2, 3, 4, 12])), ['row-0']);
assert.deepEqual(completedLineIds(new Set([0, 6, 12, 18, 24])), ['diag-0']);
assert.deepEqual(newLineIds(['row-0'], ['row-0', 'col-0']), ['col-0']);
assert.equal(shouldHaveWinClass(0, ['row-0']), true, 'winning cells keep the win class');
assert.equal(shouldHaveWinClass(6, ['row-0']), false, 'non-winning cells do not get the win class');
assert.equal(pendingMarkedState(false, { marked: true, until: 100 }, 50), true, 'server morph must not undo a fresh optimistic mark');
assert.equal(pendingMarkedState(false, { marked: true, until: 100 }, 150), false, 'expired pending state falls back to server markup');

console.log('optimistic-board ok');
