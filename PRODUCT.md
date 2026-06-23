# Product

## Register

product

## Users

Employees and external collaborators of the company (the "dunp" team). They sign in
with Google Workspace and play a daily, lighthearted office bingo together. Context of
use is short, frequent, social bursts — checking the day's board, marking a square,
glancing at the leaderboard between tasks — most often on a phone. They are not power
users learning a tool; they are colleagues having fun, so the experience must be
instantly legible and reward-driven with zero ceremony.

## Product Purpose

Dunp Bingo turns shared office moments into a daily game. Each day generates a board of
playful prompts; players mark squares as things happen, race for BINGO, and climb a
running leaderboard. It exists to spark camaraderie and a little friendly competition.
Success looks like: people open it daily, marking a square is effortless, hitting BINGO
feels celebratory, and the leaderboard gives an honest, motivating standing. Admins can
curate cells and review submissions without friction.

## Brand Personality

Loud, playful, joyful. Three words: **bold, game-like, warm**. The voice is informal and
Italian, like a colleague nudging you to play — never corporate. Emotionally it should
feel like a party favor on your desk: bright, tactile, a little cheeky. Winning should
feel earned and celebrated (confetti-energy, the BINGO banner, face bubbles). The
neo-brutalist styling — thick black borders, hard offset shadows, candy colors — is
intentional and on-brand: it reads as a physical, sticker-like toy, not a SaaS dashboard.

## Anti-references

- Generic SaaS dashboards: muted grays, thin hairline borders, soft drop shadows, the
  hero-metric template. This is a game, not an analytics tool.
- Corporate HR-portal blandness. No stock "engagement platform" energy.
- Over-refinement that sands off the fun. Polishing must not turn it quiet or sterile —
  keep the hard shadows and saturated color; refine alignment and contrast, not the joy.

## Design Principles

- **Joy is the job.** Every interaction should feel like play. If a change makes it more
  "professional" at the cost of fun, it's the wrong change.
- **Tactile and physical.** Hard shadows, solid borders, and press-y feedback make the UI
  feel like real objects you can poke. Lean into that material.
- **Reward the moment.** Marking a square and hitting BINGO are the emotional peaks —
  celebrate them loudly and make them effortless.
- **Phone-first legibility.** Colleagues play on phones in short bursts. The board, the
  leaderboard, and the act of marking must be flawless on small screens before anything else.
- **Honest, motivating standing.** The leaderboard must be clear and fair so competition
  stays friendly and the numbers feel trustworthy.

## Accessibility & Inclusion

- **Mobile-first** is the primary requirement: the 5×5 board, leaderboard, and marking
  flow must work flawlessly on small touch screens (large enough tap targets, no clipped
  text, no horizontal scroll).
- Maintain WCAG AA contrast as a sensible default — bright candy backgrounds must keep
  text on the ink end of the ramp (the near-black `#1d1d1f`), never muted gray on tint.
- Celebration animations (BINGO banner, face bubbles) should honor
  `prefers-reduced-motion` with a calmer fallback.
