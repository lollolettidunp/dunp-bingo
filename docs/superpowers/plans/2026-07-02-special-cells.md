# Celle Speciali Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add daily special-cell stickers that admins create separately and players apply once per day to replace board cells.

**Architecture:** Reuse `cells.special_date`; no new table. Generate boards from ordinary cells only, expose unused specials through `DailyBoard`, and replace a `board_cells` row in one Livewire method.

**Tech Stack:** Laravel, Livewire, Blade, PHP tests, tiny vanilla JS for drag/drop fallback glue.

---

### Task 1: Board generation semantics

**Files:**
- Modify: `app/Actions/CreateDailyBoard.php`
- Modify: `tests/Unit/CreateDailyBoardTest.php`

- [ ] Change the special-cell test to assert special cells are not generated into normal boards.
- [ ] Run `php artisan test tests/Unit/CreateDailyBoardTest.php` and verify the changed test fails.
- [ ] Restrict board generation to `whereNull('special_date')` and remove forced special insertion.
- [ ] Run `php artisan test tests/Unit/CreateDailyBoardTest.php`.

### Task 2: Admin special panel

**Files:**
- Create: `app/Livewire/Admin/SpecialCells.php`
- Create: `resources/views/livewire/admin/special-cells.blade.php`
- Modify: `resources/views/admin.blade.php`
- Modify: `app/Livewire/Admin/Cells.php`
- Modify: `resources/views/livewire/admin/cells.blade.php`
- Modify: `tests/Feature/AdminCellsTest.php`

- [ ] Add a failing feature test that an admin creates an "Adesivo del giorno" and the generic cell panel no longer exposes `specialDate`.
- [ ] Run `php artisan test tests/Feature/AdminCellsTest.php` and verify it fails.
- [ ] Add `Admin\SpecialCells` by copying the existing admin validation style and always saving `special_date`.
- [ ] Remove the date field from the generic cell form and save ordinary cells with `special_date = null`.
- [ ] Render `<livewire:admin.special-cells />` before `<livewire:admin.cells />`.
- [ ] Run `php artisan test tests/Feature/AdminCellsTest.php`.

### Task 3: Player sticker application

**Files:**
- Modify: `app/Livewire/DailyBoard.php`
- Modify: `resources/views/livewire/daily-board.blade.php`
- Modify: `resources/css/app.css`
- Modify: `resources/js/app.js`
- Modify: `tests/Feature/DailyBoardTest.php`

- [ ] Add a failing Livewire test that an unused special appears, `applySpecialCell()` replaces a normal position, clears `marked_at`, and hides the used special.
- [ ] Run `php artisan test tests/Feature/DailyBoardTest.php` and verify it fails.
- [ ] Add `specialCells()` and `applySpecialCell(int $cellId, int $position)` to `DailyBoard`.
- [ ] Render a drawer button/list when `specialCells` is not empty, with click fallback and drag/drop attributes.
- [ ] Add minimal JS that forwards drop events to Livewire.
- [ ] Add CSS by reusing missed-drawer and existing sticker styles.
- [ ] Run `php artisan test tests/Feature/DailyBoardTest.php`.

### Task 4: Verification

**Files:**
- Verify all changed behavior.

- [ ] Run `php artisan test`.
- [ ] Run `cmd /c npm run build`.
