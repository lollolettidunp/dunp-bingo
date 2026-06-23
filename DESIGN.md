---
name: Dunp-ingo
description: A loud, tactile neo-brutalist office bingo — the UI as a sheet of glossy stickers.
colors:
  ink: "#1d1d1f"
  surface: "#fffdf8"
  panel-tint: "#f8f2e9"
  bg-base: "#f5f0e8"
  bg-warm: "#f8f2e9"
  bg-mint: "#e8f3ef"
  bg-lilac: "#f2e8f8"
  glow-amber: "#ffc457"
  sky: "#7dd3fc"
  lime: "#d9f99d"
  mint-check: "#34d399"
  marigold: "#ffd166"
  hot-pink: "#ff5c8a"
  eyebrow-brown: "#6d5b2f"
  muted-ink: "#6f6a62"
  error-ink: "#7a271a"
  error-border: "#b42318"
  error-bg: "#fff1f0"
typography:
  display:
    fontFamily: "Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: "clamp(2.25rem, 8vw, 5.25rem)"
    fontWeight: 800
    lineHeight: 0.95
    letterSpacing: "-0.01em"
  title:
    fontFamily: "Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1.55rem"
    fontWeight: 900
    lineHeight: 1.1
  body:
    fontFamily: "Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1rem"
    fontWeight: 800
    lineHeight: 1.18
  label:
    fontFamily: "Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.78rem"
    fontWeight: 800
    lineHeight: 1.2
    letterSpacing: "0.02em"
rounded:
  md: "8px"
  pill: "999px"
  full: "50%"
spacing:
  xs: "6px"
  sm: "10px"
  md: "14px"
  lg: "22px"
  xl: "32px"
components:
  button-primary:
    backgroundColor: "{colors.sky}"
    textColor: "{colors.ink}"
    rounded: "{rounded.md}"
    padding: "9px 13px"
  button-list:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    rounded: "{rounded.md}"
    padding: "9px 13px"
  cell:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    rounded: "{rounded.md}"
    padding: "12px"
    height: "116px"
  cell-marked:
    backgroundColor: "{colors.lime}"
    textColor: "{colors.ink}"
    rounded: "{rounded.md}"
    padding: "12px"
  cell-bonus:
    backgroundColor: "{colors.marigold}"
    textColor: "{colors.ink}"
    rounded: "{rounded.md}"
    padding: "12px"
  panel:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    rounded: "{rounded.md}"
    padding: "14px"
  input:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    rounded: "{rounded.md}"
    padding: "8px"
---

# Design System: Dunp-ingo

## 1. Overview

**Creative North Star: "The Sticker-Sheet Toy"**

Dunp-ingo looks like a sheet of glossy stickers you want to peel off and press onto your
laptop. Every surface has the same vocabulary: a thick 2px black outline (`#1d1d1f`), a
gently rounded 8px corner, and a hard, blur-free drop shadow offset down and to the right.
Nothing recedes softly; everything sits up off the page like a die-cut object you could
flick with a finger. The palette is candy — sky blue, lime, marigold, hot pink — laid over
a warm-to-mint-to-lilac gradient backdrop with an amber glow in the top-left corner, the
way light catches a vinyl sticker.

This is a game for colleagues, not an analytics tool, and the system rejects everything
that would make it feel like work. It explicitly refuses the SaaS-dashboard register:
no hairline borders, no soft ambient drop shadows, no muted-gray body text, no
hero-metric template, no "engagement platform" blandness. Refinement here means tighter
alignment and honest contrast — never sanding off the joy. If a change makes Dunp-ingo
feel more professional at the cost of fun, it is the wrong change.

The type is heavy and confident: a single family (Inter) run almost entirely at 800–900
weight, so headings shout and even body copy has presence. Marking a square and hitting
BINGO are the emotional peaks — celebrated loudly with confetti, a rotating pink BINGO
banner, and a colleague's face popping out of the marked cell. The whole thing is
phone-first: colleagues play in short bursts on small screens, so the 5×5 board, the
leaderboard, and the act of marking must be flawless before anything else.

**Key Characteristics:**
- Thick 2px ink outlines on every interactive surface — no exceptions, no hairlines.
- Hard offset shadows with zero blur (`Npx Npx 0 #1d1d1f`) — the signature material.
- One 8px corner radius across the whole system; full stadium (999px) for status pills and badges; circles only for check stamps and avatars.
- Candy color used as *function*, not decoration: lime = marked, marigold = bonus, pink = BINGO.
- Heavy single-family type (Inter 800–900) on a warm gradient backdrop.
- Phone-first: every surface tested at ≤560px before desktop.

## 2. Colors

A candy palette where saturated hues carry meaning and a single near-black ink holds
every outline, shadow, and word together.

### Primary
- **Sticker Sky** (`#7dd3fc`): The default action color. Navigation links, the primary
  button, the "Esci" logout button. It's the color of "tap me".

### Secondary
- **Marker Lime** (`#d9f99d`): A square that has been marked. Paired with the mint check
  badge. This is the reward color — the most-seen state-change on the board.
- **Bonus Marigold** (`#ffd166`): Bonus / free squares. Warmer and richer than lime so a
  bonus reads as "worth more" at a glance.
- **BINGO Hot Pink** (`#ff5c8a`): Reserved almost entirely for the win. The fixed BINGO
  banner only. Its rarity is what makes it land.

### Tertiary
- **Mint Check** (`#34d399`): The circular ✓ badge stamped on a marked square. Never used
  as a surface — only as the confirmation stamp.
- **Amber Glow** (`#ffc457`): Not a fill. The radial light in the top-left of the body
  gradient, the "sheen on the sticker sheet".

### Neutral
- **Ink** (`#1d1d1f`): The single structural color. Every border, every hard shadow, and
  all primary text. The system has exactly one near-black; it is everywhere.
- **Sticker Surface** (`#fffdf8`): The off-white face of cells, panels, inputs, and the
  modal. Warm enough to feel like paper stock, never clinical white.
- **Panel Tint** (`#f8f2e9`): A warmer off-white for leaderboard rows and review cards, to
  separate listed items from the brighter `#fffdf8` surfaces.
- **Page Wash** (`#f5f0e8` base → `#f8f2e9` warm → `#e8f3ef` mint → `#f2e8f8` lilac): The
  body gradient. The toy sits on a soft, shifting pastel sheet.
- **Eyebrow Brown** (`#6d5b2f`): The single kicker label ("Bingo aziendale"). Dark enough
  on the warm wash to stay legible.
- **Muted Ink** (`#6f6a62`): Secondary metadata only — leaderboard sub-scores, status
  captions. Never body copy.

### Named Rules
**The One-Ink Rule.** There is exactly one structural color: `#1d1d1f`. Every border,
every shadow, and all primary text use it. Never introduce a second "dark" — no charcoal
shadows, no gray borders. The unity of the ink is what makes the stickers feel die-cut
from one sheet.

**The Color-Means-Something Rule.** Saturated fills are functional, not decorative. Lime
means marked, marigold means bonus, pink means BINGO. Never use a candy color just to
"add color" to a neutral surface — that dilutes the signal that makes the board readable.

## 3. Typography

**Display Font:** Inter (with `ui-sans-serif, system-ui, sans-serif` fallback)
**Body Font:** Inter (same stack — one family throughout)
**Label Font:** Inter

**Character:** One family, run heavy. The system gets its voice from weight, not from a
second typeface — almost everything sits at 800 or 900, so the interface feels bold and
toy-like rather than literary. The contrast axis is weight-and-size, never family.

### Hierarchy
- **Display** (800, `clamp(2.25rem, 8vw, 5.25rem)`, line-height 0.95): The page title
  ("Dunp-ingo") in the hero. Tight leading so the big word reads as one solid block.
- **Title** (900, `1.55rem`, line-height 1.1): Status figures (current score, squares
  left) and section headings inside panels.
- **Body** (800, `1rem`, line-height 1.18): Cell labels, hints, list buttons, form text.
  Heavy by default — there is no light body weight in this system.
- **Label** (800, `0.78rem`, `letter-spacing 0.02em`, uppercase): The single hero eyebrow
  and status captions. This is the *only* sanctioned uppercase kicker.

### Named Rules
**The Weight-Is-Hierarchy Rule.** Hierarchy comes from weight and size within Inter, never
from a second font. Don't pair Inter with another sans — if you need contrast, go heavier
(900) or bigger, not different.

**The One-Eyebrow Rule.** The uppercase tracked label appears exactly once — the hero
kicker. Never stack a small all-caps eyebrow above every panel; that is the SaaS scaffold
this system rejects.

## 4. Elevation

Depth is structural and physical, never atmospheric. The system uses **hard offset
shadows with zero blur** — a solid ink rectangle pushed down-and-right behind each
surface, exactly like the drop shadow on a printed sticker. There is no soft ambient
shadow anywhere; the absence of blur is the whole identity. Elevation scales with
importance: buttons sit 4px off the page, panels and the board sit a dramatic 8px off.

### Shadow Vocabulary
- **Button lift** (`box-shadow: 4px 4px 0 #1d1d1f`): Nav links, primary buttons. Small,
  flick-able objects.
- **Panel lift** (`box-shadow: 8px 8px 0 #1d1d1f`): Panels, status block, board wrapper,
  modal. The hero surfaces, lifted high off the sheet.
- **Compact lift** (`box-shadow: 5px 5px 0 #1d1d1f`): The BINGO banner, face bubbles, and
  the board wrapper on mobile — a middle step for floating/celebratory elements.
- **Cell hover** (`transform: translateY(-2px)` + `box-shadow: 0 6px 0 rgba(29,29,31,.2)`):
  The one exception to the hard-shadow rule — a cell lifts and casts a soft straight-down
  shadow on hover, the "about to peel it off" gesture. 150ms ease.

### Named Rules
**The Zero-Blur Rule.** Shadows are solid ink offsets, never blurred. `4px 4px 0`,
`8px 8px 0` — the second-to-last value is always `0`. A blurred shadow instantly breaks
the sticker metaphor and drags the design back to 2014-app territory. If you see a blur
radius on a structural surface, it's wrong.

## 5. Components

### Buttons
- **Shape:** 8px corners, 2px ink border (`rounded.md`, `#1d1d1f`).
- **Primary:** Sticker Sky fill (`#7dd3fc`), ink text, weight 900, padding `9px 13px`,
  `4px 4px 0 #1d1d1f` shadow. Used for nav and main actions.
- **List button:** Full-width, left-aligned, Sticker Surface fill (`#fffdf8`), **no
  shadow** — these sit flush inside panels as menu rows, not as lifted objects.
- **Hover / Focus:** No built-in hover today; the press feel comes from the hard shadow.
  When adding interaction, prefer a shadow-shrink + `translate` "press-down", never a
  color fade.

### Cards / Containers
- **Corner Style:** 8px radius everywhere (`rounded.md`).
- **Background:** Sticker Surface `#fffdf8`, or its translucent form `rgba(255,253,248,.78)`
  for the status block and side panels so the gradient glows through faintly.
- **Shadow Strategy:** Panel lift (`8px 8px 0 #1d1d1f`). See Elevation.
- **Border:** Always 2px ink. No borderless cards.
- **Internal Padding:** `14px` (`spacing.md`) for panels; `12px` for cells.
- **Note:** Never nest a lifted card inside another lifted card — list rows go flush
  (no shadow) inside panels.

### Inputs / Fields
- **Style:** 2px ink stroke, 8px radius, Sticker Surface fill, `8px` padding. Same
  vocabulary as everything else — inputs are stickers too.
- **Focus:** No custom focus ring today. When added, use a thicker ink border or an inset
  ink outline, not a colored glow — keep it blur-free.
- **Error:** Error border `#b42318`, error bg `#fff1f0`, error text `#7a271a`, weight 800,
  centered. The one place a second dark (oxblood red) is allowed, because it signals "stop".

### Navigation
- **Style:** Horizontal flex-wrap row of Sticker Sky buttons in the hero. Each is a primary
  button (border, 8px radius, `4px 4px 0` shadow). Weight 900.
- **Mobile:** The hero stacks vertically below 720px; nav wraps and stretches full-width.

### The Bingo Cell *(signature)*
- The core interactive object. 116px min-height (78px on mobile), 2px ink border, 8px
  radius, Sticker Surface fill, centered bold label.
- **Marked:** fills Marker Lime (`#d9f99d`) and stamps a circular Mint Check badge
  (`#34d399`, 24px, 2px ink border, ✓) in the top-right corner.
- **Bonus:** fills Bonus Marigold (`#ffd166`) with a large ✓ above the label.
- **Hover (interactive boards only):** lifts 2px with a soft straight-down shadow. The
  `.readonly` board (colleagues' view) suppresses the pointer and lift.

### The BINGO Banner & Face Bubbles *(signature celebration)*
- **Banner:** Fixed, top-center, Hot Pink (`#ff5c8a`) fill, white text, 1.65rem/900,
  rotated ~-3°, `5px 5px 0` shadow. Pops in via `bannerPop` (1500ms) on win, alongside a
  confetti burst.
- **Face bubble:** On every mark, a random colleague's circular avatar pops out of the
  marked cell (`faceBubblePop`, 1800ms, `cubic-bezier(.2,.9,.2,1)`), then floats up and
  fades. Pure delight; `aria-hidden`, decorative only.

## 6. Do's and Don'ts

### Do:
- **Do** outline every interactive surface with a 2px `#1d1d1f` border and an 8px radius.
  Consistency of the outline is the system.
- **Do** use hard, zero-blur offset shadows (`4px 4px 0` for buttons, `8px 8px 0` for
  panels, `5px 5px 0` for floating/celebration). The `0` blur value is non-negotiable.
- **Do** keep candy color functional — lime = marked, marigold = bonus, pink = BINGO.
- **Do** keep all text on the ink end of the ramp (`#1d1d1f`) on candy backgrounds, and
  reserve Muted Ink (`#6f6a62`) for true secondary metadata only — never body copy.
- **Do** design and test phone-first: the 5×5 board, leaderboard, and marking flow must be
  flawless at ≤560px (cells drop to 78px min-height, board gap to 6px) before desktop.
- **Do** add a `prefers-reduced-motion: reduce` fallback for the BINGO banner, face
  bubbles, and confetti — a calm crossfade or instant state, no transforms. *(Current gap:
  the celebration animations have no reduced-motion alternative yet.)*

### Don't:
- **Don't** use blurred or soft ambient shadows on any structural surface. A blur radius
  breaks the sticker metaphor and reads as a dated 2014 app.
- **Don't** introduce a second structural dark — no gray borders, no charcoal shadows.
  One ink (`#1d1d1f`) only. (Oxblood `#b42318` is allowed *only* for error states.)
- **Don't** drift toward the SaaS-dashboard register: no hairline borders, no muted-gray
  body text on tint, no hero-metric template, no "engagement platform" blandness.
- **Don't** pair Inter with a second typeface. Get contrast from weight (800/900) and size.
- **Don't** stack a small uppercase tracked eyebrow above every section. The kicker exists
  exactly once, in the hero.
- **Don't** nest a lifted card inside another lifted card. Rows go flush (no shadow) inside
  panels.
- **Don't** refine the fun away. Tighten alignment and contrast; never trade the loud,
  tactile, playful identity for "more professional".
