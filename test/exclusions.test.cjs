const assert = require("node:assert/strict");
const fs = require("node:fs");
const vm = require("node:vm");

function element() {
  return {
    className: "",
    classList: { add() {}, remove() {} },
    dataset: {},
    style: {},
    addEventListener() {},
    appendChild() {},
    querySelector() { return null; },
    remove() {},
    setAttribute() {}
  };
}

const context = {
  document: {
    body: element(),
    createElement: element,
    querySelector: element
  },
  Date: class extends Date {
    constructor(...args) {
      super(...(args.length ? args : ["2026-06-18T12:00:00"]));
    }

    static now() {
      return new Date("2026-06-18T12:00:00").getTime();
    }
  },
  fetch: async () => ({
    ok: true,
    json: async () => ({ squares: Array.from({ length: 24 }, (_, index) => ({ text: `base ${index}` })) })
  }),
  localStorage: {
    store: new Map([
      ["dunp-bingo:2026-06-15", "{}"],
      ["dunp-bingo:2026-06-16", "{}"],
      ["dunp-bingo:2026-06-17", "{}"],
      ["unrelated", "{}"]
    ]),
    get length() { return this.store.size; },
    key(index) { return [...this.store.keys()][index] ?? null; },
    getItem(key) { return this.store.get(key) ?? null; },
    removeItem(key) { this.store.delete(key); },
    setItem(key, value) { this.store.set(key, value); }
  },
  window: {
    innerHeight: 1000,
    innerWidth: 1000,
    setTimeout(callback) { callback(); }
  }
};

vm.runInNewContext(fs.readFileSync("main.js", "utf8"), context);

context.loadTodayState(Array.from({ length: 24 }, (_, index) => `square ${index}`));

assert(!context.localStorage.store.has("dunp-bingo:2026-06-15"));
assert(!context.localStorage.store.has("dunp-bingo:2026-06-16"));
assert(context.localStorage.store.has("dunp-bingo:2026-06-17"));
assert(context.localStorage.store.has("dunp-bingo:2026-06-18"));
assert(context.localStorage.store.has("unrelated"));

const data = {
  squares: [
    ...Array.from({ length: 24 }, (_, index) => ({ text: `base ${index}` })),
    { text: "Ale prende in giro Laura", except: "martedì" },
    { text: "Rientro lunedì", except: ["lunedi"] }
  ]
};

assert(!context.getAvailableSquares(data, "martedi").includes("Ale prende in giro Laura"));
assert(context.getAvailableSquares(data, "martedi").includes("Rientro lunedì"));
assert(context.getAvailableSquares(data, "lunedi").includes("Ale prende in giro Laura"));
assert(!context.getAvailableSquares(data, "lunedi").includes("Rientro lunedì"));
