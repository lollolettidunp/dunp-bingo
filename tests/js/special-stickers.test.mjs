import assert from 'node:assert/strict';
import { specialDragClasses } from '../../resources/js/special-stickers.js';

assert.deepEqual(specialDragClasses('start'), {
  board: 'is-receiving-sticker',
  sticker: 'is-dragging',
  target: '',
});
assert.equal(specialDragClasses('enter').target, 'is-sticker-target');
assert.equal(specialDragClasses('drop').target, 'is-sticker-applied');
assert.equal(specialDragClasses('end').board, '');

console.log('special-stickers ok');