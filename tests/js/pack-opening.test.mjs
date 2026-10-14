import assert from 'node:assert/strict';
import { dealOrder, flightTilt, scatterPoint } from '../../resources/js/pack-opening.js';

const order = dealOrder(25);
assert.equal(order.length, 25);
assert.equal(new Set(order).size, 25, 'every square is dealt exactly once');
assert.equal(order.at(-1), 12, 'the bonus centre lands last');
assert.notDeepEqual(order.slice(0, 5), [0, 1, 2, 3, 4], 'the grid fills in scrambled, not row by row');
assert.deepEqual([...dealOrder(3)].sort(), [0, 1, 2], 'a board without a centre still deals every square');
assert.deepEqual(dealOrder(1), [0]);
assert.deepEqual(dealOrder(0), []);

// The blast scatters stickers around the mouth but never outside the padded viewport.
const phone = { width: 390, height: 844 };
const mouth = { x: 195, y: 330 };
const points = [...Array(25).keys()].map((index) => scatterPoint(mouth, index, phone, 48));
assert.ok(points.every(({ x, y }) => x >= 48 && x <= 390 - 48 && y >= 48 && y <= 844 - 48));
assert.ok(new Set(points.map(({ x, y }) => `${Math.round(x)},${Math.round(y)}`)).size >= 20, 'stickers spread out instead of piling up');
assert.ok(points.filter(({ y }) => y < mouth.y).length > 15, 'most of them go up');
assert.deepEqual(scatterPoint(mouth, 3, phone, 48), scatterPoint(mouth, 3, phone, 48), 'same sticker, same spot');

// Distance must not follow from direction, or the cloud collapses into a ring around the pack.
const distances = points.map(({ x, y }) => Math.hypot(x - mouth.x, y - mouth.y));
assert.ok(Math.max(...distances) > Math.min(...distances) * 2, 'near and far stickers, not one ring');

assert.notEqual(Math.sign(flightTilt(0)), Math.sign(flightTilt(1)), 'neighbours lean opposite ways');
assert.ok([...Array(25).keys()].every((index) => Math.abs(flightTilt(index)) >= 8 && Math.abs(flightTilt(index)) < 35), 'readable while hanging');

console.log('pack-opening ok');
