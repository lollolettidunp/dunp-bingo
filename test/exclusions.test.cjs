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
  fetch: async () => ({
    ok: true,
    json: async () => ({ squares: Array.from({ length: 24 }, (_, index) => ({ text: `base ${index}` })) })
  }),
  localStorage: {
    getItem() { return null; },
    setItem() {}
  },
  window: {
    innerHeight: 1000,
    innerWidth: 1000,
    setTimeout(callback) { callback(); }
  }
};

vm.runInNewContext(fs.readFileSync("main.js", "utf8"), context);

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
