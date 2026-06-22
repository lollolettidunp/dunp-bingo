# Dunp Bingo Laravel/Livewire Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Sostituire il bingo statico con un monolite Laravel/Livewire autenticato, persistente e amministrabile, mantenendo l'esperienza visiva corrente.

**Architecture:** Laravel serve pagine Blade e componenti Livewire; MySQL conserva celle, schede e revisioni. Socialite gestisce Google OAuth. La sola logica isolata dal framework è il generatore della scheda, perché contiene l'euristica non banale da testare.

**Tech Stack:** PHP, Laravel, Livewire, Laravel Socialite, MySQL, Vite, PHPUnit/Pest fornito da Laravel.

**Design reference:** `docs/superpowers/specs/2026-06-22-laravel-livewire-bingo-design.md`

---

## File map

- `app/Actions/CreateDailyBoard.php`: selezione, bilanciamento e persistenza atomica della scheda.
- `app/Http/Controllers/GoogleAuthController.php`: redirect e callback OAuth.
- `app/Http/Middleware/EnsureAdmin.php`: confronto dell'email autenticata con `ADMIN_EMAIL`.
- `app/Livewire/DailyBoard.php`: gioco, marcature e invio in revisione.
- `app/Livewire/ColleagueBoards.php`: elenco e dettaglio read-only con polling.
- `app/Livewire/Leaderboard.php`: classifica derivata.
- `app/Livewire/Admin/Cells.php`: CRUD minimo delle celle.
- `app/Livewire/Admin/Users.php`: allowlist, abilitazione e punteggio iniziale.
- `app/Livewire/Admin/Reviews.php`: approvazione e rifiuto atomici.
- `app/Models/{User,Cell,Board,BoardCell}.php`: relazioni, cast e regole locali del dominio.
- `resources/views/livewire/**`: markup dei componenti, senza component library.
- `resources/views/components/layouts/app.blade.php`: layout unico autenticato.
- `resources/css/app.css`: stile attuale adattato.
- `resources/js/app.js`: solo bootstrap Livewire e confetti.
- `database/data/{bingo,leaderboard}.json`: sorgenti legacy conservate.
- `database/seeders/DatabaseSeeder.php`: import idempotente delle celle legacy.
- `tests/Feature/**`: autenticazione, componenti e flussi HTTP.
- `tests/Unit/CreateDailyBoardTest.php`: euristica e vincoli della scheda.

---

### Task 1: Installare lo scheletro Laravel senza perdere i dati legacy

**Files:**
- Create: struttura Laravel standard alla root
- Create: `database/data/bingo.json`
- Create: `database/data/leaderboard.json`
- Modify: `.env.example`
- Preserve for later migration: `index.html`, `main.js`, `style.css`, `faces/`

- [ ] **Step 1: Verificare il punto di partenza**

Run:

```powershell
git status --short
node test/exclusions.test.cjs
```

Expected: `bingo.json` risulta modificato e il test legacy termina con exit code `0`. Non ripristinare quella modifica.

- [ ] **Step 2: Creare Laravel in una directory temporanea e installare solo le dipendenze richieste**

Run:

```powershell
composer create-project laravel/laravel C:\tmp\dunp-bingo-laravel
Set-Location C:\tmp\dunp-bingo-laravel
composer require livewire/livewire laravel/socialite
```

Expected: il progetto temporaneo esiste e Composer installa Livewire e Socialite senza errori.

- [ ] **Step 3: Copiare lo scheletro nella repository e spostare i JSON**

Run dalla root della repository:

```powershell
Get-ChildItem C:\tmp\dunp-bingo-laravel -Force | Where-Object Name -NotIn '.git','.env','README.md' | Copy-Item -Destination . -Recurse -Force
New-Item -ItemType Directory -Force database\data
git mv bingo.json database/data/bingo.json
git mv leaderboard.json database/data/leaderboard.json
```

Expected: Laravel è alla root; i contenuti UTF-8 e la modifica locale di `bingo.json` sono presenti in `database/data/bingo.json`.

- [ ] **Step 4: Aggiungere la configurazione applicativa minima**

Appendere a `.env.example`:

```dotenv
APP_TIMEZONE=Europe/Rome
ADMIN_EMAIL=
GOOGLE_WORKSPACE_DOMAIN=
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=
```

Impostare MySQL nelle variabili `DB_*` già fornite dallo scheletro. In `config/app.php` usare:

```php
'timezone' => env('APP_TIMEZONE', 'Europe/Rome'),
```

In `config/services.php` aggiungere:

```php
'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect' => env('GOOGLE_REDIRECT_URI'),
],
```

- [ ] **Step 5: Eseguire lo smoke test e committare**

Run:

```powershell
Copy-Item .env.example .env
php artisan key:generate
php artisan test
npm install
npm run build
git add -- . ':!index.html' ':!main.js' ':!style.css' ':!faces'
git commit -m "chore: bootstrap Laravel Livewire app"
```

Expected: test e build passano; il commit non contiene ancora la rimozione della UI legacy.

---

### Task 2: Creare schema, modelli e import legacy

**Files:**
- Modify: `database/migrations/0001_01_01_000000_create_users_table.php`
- Create: `database/migrations/2026_06_22_000001_create_bingo_tables.php`
- Create: `app/Models/Cell.php`
- Create: `app/Models/Board.php`
- Create: `app/Models/BoardCell.php`
- Modify: `app/Models/User.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Test: `tests/Feature/DatabaseSeederTest.php`

- [ ] **Step 1: Scrivere il test fallente per schema e import**

Creare `tests/Feature/DatabaseSeederTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Cell;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_legacy_cells_idempotently(): void
    {
        $this->seed();
        $count = Cell::count();
        $this->seed();

        $this->assertGreaterThanOrEqual(24, $count);
        $this->assertSame($count, Cell::count());
        $this->assertDatabaseHas('cells', ['text' => 'Giuseppe viene in ufficio infortunato']);
    }
}
```

- [ ] **Step 2: Eseguire il test e verificarne il fallimento**

Run: `php artisan test tests/Feature/DatabaseSeederTest.php`

Expected: FAIL perché `cells` e il modello `Cell` non esistono.

- [ ] **Step 3: Implementare lo schema minimo**

Rendere nullable la colonna `password` già presente e aggiungere i campi OAuth:

```php
$table->string('password')->nullable();
$table->string('google_id')->nullable()->unique();
$table->string('avatar_url')->nullable();
$table->boolean('is_enabled')->default(true);
$table->unsignedInteger('starting_score')->default(0);
```

La nuova migrazione deve creare:

```php
Schema::create('cells', function (Blueprint $table) {
    $table->id();
    $table->text('text')->unique();
    $table->unsignedTinyInteger('difficulty')->default(2);
    $table->boolean('is_active')->default(true);
    $table->date('special_date')->nullable()->index();
    $table->json('excluded_weekdays')->nullable();
    $table->timestamps();
});

Schema::create('cell_user', function (Blueprint $table) {
    $table->foreignId('cell_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->primary(['cell_id', 'user_id']);
});

Schema::create('boards', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->date('played_on');
    $table->string('status')->default('playing');
    $table->timestamp('submitted_at')->nullable();
    $table->timestamp('reviewed_at')->nullable();
    $table->text('review_note')->nullable();
    $table->timestamps();
    $table->unique(['user_id', 'played_on']);
});

Schema::create('board_cells', function (Blueprint $table) {
    $table->id();
    $table->foreignId('board_id')->constrained()->cascadeOnDelete();
    $table->foreignId('cell_id')->nullable()->constrained()->nullOnDelete();
    $table->unsignedTinyInteger('position');
    $table->text('text');
    $table->unsignedTinyInteger('difficulty');
    $table->timestamp('marked_at')->nullable();
    $table->unique(['board_id', 'position']);
});
```

Aggiungere check applicativi `difficulty 1..3` nei form; il bonus viene inserito solo in `board_cells` con difficoltà `0`.

- [ ] **Step 4: Implementare modelli e seeder**

Usare cast espliciti e relazioni dirette. Il seeder deve fare:

```php
$data = json_decode(file_get_contents(database_path('data/bingo.json')), true, flags: JSON_THROW_ON_ERROR);

foreach ($data['squares'] as $square) {
    $square = is_string($square) ? ['text' => $square] : $square;
    Cell::firstOrCreate(
        ['text' => trim($square['text'])],
        [
            'difficulty' => 2,
            'is_active' => true,
            'excluded_weekdays' => array_values(array_map(
                fn (string $day) => Str::ascii(strtolower($day)),
                array_filter((array) ($square['except'] ?? [])),
            )),
        ],
    );
}
```

Definire le relazioni usate dai task successivi:

```php
// User
public function boards(): HasMany { return $this->hasMany(Board::class); }
public function excludedCells(): BelongsToMany { return $this->belongsToMany(Cell::class); }

// Cell
public function users(): BelongsToMany { return $this->belongsToMany(User::class); }

// Board
public function user(): BelongsTo { return $this->belongsTo(User::class); }
public function cells(): HasMany { return $this->hasMany(BoardCell::class)->orderBy('position'); }

// BoardCell
public function board(): BelongsTo { return $this->belongsTo(Board::class); }
```

`Board` deve dichiarare le costanti `PLAYING`, `PENDING`, `APPROVED`, `REJECTED` e il metodo:

```php
public function hasBingo(): bool
{
    $marked = $this->cells->whereNotNull('marked_at')->pluck('position')->all();
    $lines = [
        [0,1,2,3,4], [5,6,7,8,9], [10,11,12,13,14], [15,16,17,18,19], [20,21,22,23,24],
        [0,5,10,15,20], [1,6,11,16,21], [2,7,12,17,22], [3,8,13,18,23], [4,9,14,19,24],
        [0,6,12,18,24], [4,8,12,16,20],
    ];

    return collect($lines)->contains(fn (array $line) => count(array_intersect($line, $marked)) === 5);
}
```

- [ ] **Step 5: Verificare e committare**

Run:

```powershell
php artisan test tests/Feature/DatabaseSeederTest.php
php artisan migrate:fresh --seed
git add app/Models database/migrations database/seeders database/data tests/Feature/DatabaseSeederTest.php
git commit -m "feat: add bingo data model"
```

Expected: PASS e almeno 24 celle importate una sola volta.

---

### Task 3: Aggiungere login Google e admin singolo

**Files:**
- Create: `app/Http/Controllers/GoogleAuthController.php`
- Create: `app/Http/Middleware/EnsureAdmin.php`
- Modify: `bootstrap/app.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/GoogleAuthTest.php`
- Test: `tests/Feature/AdminAccessTest.php`

- [ ] **Step 1: Scrivere i test fallenti dei confini di fiducia**

I test devono coprire esattamente:

```php
public function test_workspace_user_is_created_and_logged_in(): void;
public function test_preauthorized_external_user_can_log_in(): void;
public function test_unknown_external_user_is_rejected(): void;
public function test_disabled_workspace_user_is_rejected(): void;
public function test_only_configured_admin_reaches_admin_routes(): void;
```

Mockare il contratto Socialite affinché restituisca un utente Google con `getId()`, `getEmail()`, `getName()` e `getAvatar()`. Configurare nei test:

```php
config([
    'services.google.workspace_domain' => 'azienda.it',
    'services.google.admin_email' => 'admin@azienda.it',
]);
```

- [ ] **Step 2: Verificare che i test falliscano**

Run: `php artisan test tests/Feature/GoogleAuthTest.php tests/Feature/AdminAccessTest.php`

Expected: FAIL per rotte, controller e middleware mancanti.

- [ ] **Step 3: Implementare callback e middleware**

Il callback deve seguire questa sequenza, senza service layer:

```php
$google = Socialite::driver('google')->user();
$email = strtolower((string) $google->getEmail());
abort_unless(filter_var($email, FILTER_VALIDATE_EMAIL), 403);
$user = User::where('email', $email)->first();

abort_if($user && ! $user->is_enabled, 403);

$isWorkspace = Str::afterLast($email, '@') === strtolower((string) config('services.google.workspace_domain'));
abort_unless($isWorkspace || $user?->is_enabled, 403);

$user ??= User::create(['email' => $email, 'name' => $google->getName(), 'is_enabled' => true]);
$user->update([
    'google_id' => $google->getId(),
    'name' => $google->getName(),
    'avatar_url' => $google->getAvatar(),
]);
Auth::login($user, remember: true);

return redirect()->intended(route('board'));
```

`EnsureAdmin` confronta in minuscolo `auth()->user()->email` e `config('services.google.admin_email')`; altrimenti `abort(403)`.

- [ ] **Step 4: Registrare configurazione e rotte**

In `config/services.php` includere anche:

```php
'workspace_domain' => env('GOOGLE_WORKSPACE_DOMAIN'),
'admin_email' => env('ADMIN_EMAIL'),
```

Registrare alias middleware `admin` in `bootstrap/app.php`. Le rotte minime sono:

```php
Route::get('/login/google', [GoogleAuthController::class, 'redirect'])->name('login.google');
Route::get('/login/google/callback', [GoogleAuthController::class, 'callback']);
Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/');
})->middleware('auth');
Route::view('/', 'board')->middleware('auth')->name('board');
Route::view('/admin', 'admin')->middleware(['auth', 'admin'])->name('admin');
```

- [ ] **Step 5: Verificare e committare**

Run:

```powershell
php artisan test tests/Feature/GoogleAuthTest.php tests/Feature/AdminAccessTest.php
git add app/Http bootstrap/app.php config/services.php routes/web.php tests/Feature/GoogleAuthTest.php tests/Feature/AdminAccessTest.php
git commit -m "feat: add Google workspace login"
```

Expected: tutti i casi di dominio, allowlist, disabilitazione e admin passano.

---

### Task 4: Generare una scheda personale bilanciata e stabile

**Files:**
- Create: `app/Actions/CreateDailyBoard.php`
- Test: `tests/Unit/CreateDailyBoardTest.php`

- [ ] **Step 1: Scrivere i test fallenti del generatore**

Creare casi separati che verifichino:

```php
public function test_it_creates_one_board_with_bonus_and_24_unique_cells(): void;
public function test_it_excludes_cells_about_the_user_and_excluded_weekdays(): void;
public function test_it_always_includes_eligible_special_cells(): void;
public function test_it_returns_the_existing_board_without_regenerating_it(): void;
public function test_it_fails_when_fewer_than_24_cells_are_eligible(): void;
public function test_it_keeps_the_lowest_row_and_column_spread_found(): void;
```

Nel test di stabilità, modificare il testo della `Cell` dopo la generazione e verificare che `BoardCell::text` non cambi.

- [ ] **Step 2: Verificare che il test fallisca**

Run: `php artisan test tests/Unit/CreateDailyBoardTest.php`

Expected: FAIL perché `CreateDailyBoard` non esiste.

- [ ] **Step 3: Implementare selezione e concorrenza**

La firma pubblica è:

```php
public function __invoke(User $user, CarbonImmutable $date): Board
```

Dentro `DB::transaction`, bloccare la riga utente con `User::whereKey($user->id)->lockForUpdate()->firstOrFail()`, restituire l'eventuale board esistente, quindi caricare il piccolo pool in memoria:

```php
$weekday = ['domenica','lunedi','martedi','mercoledi','giovedi','venerdi','sabato'][$date->dayOfWeek];
$excludedIds = $user->excludedCells()->pluck('cells.id');
$eligible = Cell::query()
    ->where('is_active', true)
    ->whereNotIn('id', $excludedIds)
    ->where(fn ($query) => $query->whereNull('special_date')->orWhereDate('special_date', $date))
    ->get()
    ->reject(fn (Cell $cell) => in_array($weekday, $cell->excluded_weekdays ?? [], true));

$special = $eligible->whereNotNull('special_date');
$ordinary = $eligible->whereNull('special_date');

throw_if($special->count() > 24, DomainException::class, 'Troppe celle speciali per questa data.');
throw_if($ordinary->count() + $special->count() < 24, DomainException::class, 'Servono almeno 24 celle eleggibili.');
```

- [ ] **Step 4: Implementare l'euristica minima e la fotografia**

Per 100 tentativi, scegliere le ordinarie mancanti, mescolarle con le speciali, inserire il bonus in posizione `12` e calcolare:

```php
private function spread(Collection $cells): int
{
    $weights = $cells->pluck('difficulty')->all();
    $lines = [
        [0,1,2,3,4], [5,6,7,8,9], [10,11,12,13,14], [15,16,17,18,19], [20,21,22,23,24],
        [0,5,10,15,20], [1,6,11,16,21], [2,7,12,17,22], [3,8,13,18,23], [4,9,14,19,24],
    ];
    $totals = array_map(fn ($line) => array_sum(array_map(fn ($i) => $weights[$i], $line)), $lines);

    return max($totals) - min($totals);
}
```

Conservare il candidato col punteggio più basso e interrompere a `<= 1`. Persistire 25 `board_cells` con testo e difficoltà copiati; il bonus ha `cell_id = null`, `difficulty = 0`, `marked_at = now()`.

Inserire il commento:

```php
// ponytail: 100 tentativi bastano per 24 celle; usare un solver solo se lo sbilanciamento diventa misurabile.
```

- [ ] **Step 5: Verificare e committare**

Run:

```powershell
php artisan test tests/Unit/CreateDailyBoardTest.php
git add app/Actions/CreateDailyBoard.php tests/Unit/CreateDailyBoardTest.php
git commit -m "feat: generate balanced daily boards"
```

Expected: tutti i vincoli del generatore passano senza dipendenze matematiche.

---

### Task 5: Implementare gioco, persistenza e invio in revisione

**Files:**
- Create: `app/Livewire/DailyBoard.php`
- Create: `resources/views/livewire/daily-board.blade.php`
- Create: `resources/views/board.blade.php`
- Test: `tests/Feature/DailyBoardTest.php`

- [ ] **Step 1: Scrivere i test Livewire fallenti**

Usare `Livewire::actingAs($user)->test(DailyBoard::class)` per verificare:

```php
public function test_owner_can_toggle_a_cell_while_playing(): void;
public function test_marking_dispatches_the_face_event_and_a_new_line_dispatches_bingo(): void;
public function test_bonus_cannot_be_toggled(): void;
public function test_pending_and_approved_boards_are_locked(): void;
public function test_submit_requires_a_complete_line(): void;
public function test_valid_bingo_becomes_pending_and_cannot_be_edited(): void;
public function test_generation_error_is_shown_instead_of_a_partial_board(): void;
```

Il test di invio marca le posizioni `[0,1,2,3,4]`, chiama `submit()` e verifica `status = pending` e `submitted_at` valorizzato.

- [ ] **Step 2: Eseguire e verificare il fallimento**

Run: `php artisan test tests/Feature/DailyBoardTest.php`

Expected: FAIL perché componente e view non esistono.

- [ ] **Step 3: Implementare il componente con controlli server-side**

Metodi pubblici minimi:

```php
public function mount(CreateDailyBoard $create): void
{
    try {
        $this->board = $create(auth()->user(), CarbonImmutable::today(config('app.timezone')));
    } catch (DomainException $exception) {
        report($exception);
        $this->generationError = $exception->getMessage();
    }
}

public function toggle(int $position): void
{
    abort_unless($this->board->user_id === auth()->id(), 403);
    abort_unless(in_array($this->board->status, [Board::PLAYING, Board::REJECTED], true), 409);
    abort_if($position === 12, 422);

    $cell = $this->board->cells()->where('position', $position)->firstOrFail();
    $cell->update(['marked_at' => $cell->marked_at ? null : now()]);
    $this->board->refresh()->load('cells');
}

public function submit(): void
{
    $this->board->refresh()->load('cells');
    abort_unless($this->board->hasBingo(), 422);
    $this->board->update(['status' => Board::PENDING, 'submitted_at' => now()]);
}
```

- [ ] **Step 4: Implementare markup e festeggiamenti**

La view mostra `generationError` al posto della griglia quando valorizzato. Altrimenti rende 25 `<button>` con `wire:dblclick="toggle({{ $position }})"` e un normale pulsante accessibile "Segna/Togli" per touch e tastiera. Mostrare "Invia in revisione" solo quando `hasBingo()` è vero e lo stato è modificabile. Disabilitare tutte le azioni in `pending` e `approved`.

Emettere `cell-marked` quando una cella viene segnata e `bingo-completed` quando appare una nuova linea; `resources/js/app.js` conserverà la comparsa casuale dei volti e i confetti già presenti.

- [ ] **Step 5: Verificare e committare**

Run:

```powershell
php artisan test tests/Feature/DailyBoardTest.php
git add app/Livewire/DailyBoard.php resources/views/livewire/daily-board.blade.php resources/views/board.blade.php tests/Feature/DailyBoardTest.php
git commit -m "feat: persist daily bingo gameplay"
```

Expected: toggle, lock e submission passano.

---

### Task 6: Rendere osservabili le schede dei colleghi

**Files:**
- Create: `app/Livewire/ColleagueBoards.php`
- Create: `resources/views/livewire/colleague-boards.blade.php`
- Create: `resources/views/colleagues.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/ColleagueBoardsTest.php`

- [ ] **Step 1: Scrivere i test read-only fallenti**

Verificare che il componente:

```php
public function test_it_lists_only_boards_created_today(): void;
public function test_it_shows_marks_progress_and_status(): void;
public function test_it_exposes_no_toggle_or_submit_action(): void;
```

Il terzo test chiama `toggle` sul componente e deve fallire perché il metodo non esiste; verificare inoltre che il markup non contenga `wire:click="toggle`.

- [ ] **Step 2: Verificare il fallimento**

Run: `php artisan test tests/Feature/ColleagueBoardsTest.php`

Expected: FAIL per componente e rotta mancanti.

- [ ] **Step 3: Implementare query e polling**

Il render usa una sola query eager-loaded:

```php
$boards = Board::query()
    ->with(['user:id,name,avatar_url', 'cells:id,board_id,position,text,marked_at'])
    ->whereDate('played_on', today(config('app.timezone')))
    ->orderBy('user_id')
    ->get();
```

La root della view usa `wire:poll.15s`. Un selettore locale decide quale board mostrare, senza rotta o componente separato.

- [ ] **Step 4: Registrare la pagina autenticata**

In `routes/web.php`:

```php
Route::view('/colleghi', 'colleagues')->middleware('auth')->name('colleagues');
```

Il dettaglio contiene soltanto testo, stato e classi CSS delle marcature.

- [ ] **Step 5: Verificare e committare**

Run:

```powershell
php artisan test tests/Feature/ColleagueBoardsTest.php
git add app/Livewire/ColleagueBoards.php resources/views/livewire/colleague-boards.blade.php resources/views/colleagues.blade.php routes/web.php tests/Feature/ColleagueBoardsTest.php
git commit -m "feat: show colleague bingo progress"
```

Expected: solo schede odierne, aggiornate ogni 15 secondi e non modificabili.

---

### Task 7: Gestire utenti e celle dal pannello admin

**Files:**
- Create: `app/Livewire/Admin/Users.php`
- Create: `app/Livewire/Admin/Cells.php`
- Create: `resources/views/livewire/admin/users.blade.php`
- Create: `resources/views/livewire/admin/cells.blade.php`
- Create: `resources/views/admin.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/AdminUsersTest.php`
- Test: `tests/Feature/AdminCellsTest.php`

- [ ] **Step 1: Scrivere i test fallenti dei form admin**

Copertura minima:

```php
public function test_admin_can_precreate_and_disable_a_user(): void;
public function test_user_email_is_normalized_and_unique(): void;
public function test_starting_score_cannot_be_negative(): void;
public function test_admin_can_create_and_edit_a_cell(): void;
public function test_cell_requires_text_and_difficulty_between_one_and_three(): void;
public function test_cell_can_reference_multiple_users_and_a_special_date(): void;
public function test_non_admin_cannot_mount_admin_components(): void;
```

- [ ] **Step 2: Verificare il fallimento**

Run: `php artisan test tests/Feature/AdminUsersTest.php tests/Feature/AdminCellsTest.php`

Expected: FAIL perché componenti e view non esistono.

- [ ] **Step 3: Implementare form utenti**

Regole e salvataggio:

```php
$data = $this->validate([
    'email' => ['required', 'email', Rule::unique('users')->ignore($this->userId)],
    'name' => ['required', 'string', 'max:255'],
    'startingScore' => ['required', 'integer', 'min:0'],
    'isEnabled' => ['boolean'],
]);

User::updateOrCreate(['id' => $this->userId], [
    'email' => strtolower($data['email']),
    'name' => $data['name'],
    'starting_score' => $data['startingScore'],
    'is_enabled' => $data['isEnabled'],
]);
```

Il componente verifica l'email admin in `mount()`; non introdurre policy generiche.

- [ ] **Step 4: Implementare form celle**

Validare:

```php
$data = $this->validate([
    'text' => ['required', 'string', 'max:1000', Rule::unique('cells')->ignore($this->cellId)],
    'difficulty' => ['required', 'integer', 'between:1,3'],
    'isActive' => ['boolean'],
    'specialDate' => ['nullable', 'date'],
    'excludedWeekdays' => ['array'],
    'excludedWeekdays.*' => [Rule::in(['lunedi','martedi','mercoledi','giovedi','venerdi','sabato','domenica'])],
    'userIds' => ['array'],
    'userIds.*' => ['integer', 'exists:users,id'],
]);
```

Salvare `Cell::updateOrCreate(...)` e poi `$cell->users()->sync($data['userIds'])`. Disattivare invece di eliminare fisicamente.

- [ ] **Step 5: Verificare e committare**

Run:

```powershell
php artisan test tests/Feature/AdminUsersTest.php tests/Feature/AdminCellsTest.php
git add app/Livewire/Admin resources/views/livewire/admin resources/views/admin.blade.php routes/web.php tests/Feature/AdminUsersTest.php tests/Feature/AdminCellsTest.php
git commit -m "feat: add minimal bingo administration"
```

Expected: CRUD, validazione e accesso admin passano.

---

### Task 8: Revisionare bingo e calcolare la classifica

**Files:**
- Create: `app/Livewire/Admin/Reviews.php`
- Create: `resources/views/livewire/admin/reviews.blade.php`
- Create: `app/Livewire/Leaderboard.php`
- Create: `resources/views/livewire/leaderboard.blade.php`
- Create: `resources/views/leaderboard.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/ReviewsTest.php`
- Test: `tests/Feature/LeaderboardTest.php`

- [ ] **Step 1: Scrivere i test fallenti di revisione e punteggio**

Copertura:

```php
public function test_admin_can_approve_a_pending_valid_bingo(): void;
public function test_admin_can_reject_with_an_optional_note(): void;
public function test_approval_is_ignored_when_board_is_no_longer_pending(): void;
public function test_leaderboard_adds_starting_score_and_approved_boards(): void;
public function test_pending_rejected_and_duplicate_daily_boards_do_not_add_points(): void;
```

- [ ] **Step 2: Verificare il fallimento**

Run: `php artisan test tests/Feature/ReviewsTest.php tests/Feature/LeaderboardTest.php`

Expected: FAIL perché i componenti non esistono.

- [ ] **Step 3: Implementare revisione atomica**

Approvazione:

```php
$updated = Board::query()
    ->whereKey($boardId)
    ->where('status', Board::PENDING)
    ->whereHas('cells', fn ($query) => $query->whereNotNull('marked_at'))
    ->update([
        'status' => Board::APPROVED,
        'reviewed_at' => now(),
        'review_note' => null,
    ]);

abort_unless($updated === 1, 409);
```

Prima dell'update caricare la board e verificare `hasBingo()` sul server. Il rifiuto usa lo stesso update condizionato, stato `REJECTED` e nota validata `nullable|string|max:1000`.

- [ ] **Step 4: Implementare classifica derivata**

Query senza tabella duplicata:

```php
$users = User::query()
    ->where('is_enabled', true)
    ->withCount(['boards as approved_count' => fn ($query) => $query->where('status', Board::APPROVED)])
    ->withMax(['boards as last_approved_at' => fn ($query) => $query->where('status', Board::APPROVED)], 'reviewed_at')
    ->get()
    ->map(fn (User $user) => tap($user)->setAttribute('score', $user->starting_score + $user->approved_count))
    ->sortBy([['score', 'desc'], ['name', 'asc']]);
```

Registrare `/classifica` per utenti autenticati e mostrare punteggio e ultima approvazione.

- [ ] **Step 5: Verificare e committare**

Run:

```powershell
php artisan test tests/Feature/ReviewsTest.php tests/Feature/LeaderboardTest.php
git add app/Livewire/Admin/Reviews.php app/Livewire/Leaderboard.php resources/views/livewire/admin/reviews.blade.php resources/views/livewire/leaderboard.blade.php resources/views/leaderboard.blade.php routes/web.php tests/Feature/ReviewsTest.php tests/Feature/LeaderboardTest.php
git commit -m "feat: review and rank completed bingos"
```

Expected: una board approvata conta una sola volta; gli altri stati non contano.

---

### Task 9: Migrare l'interfaccia attuale e rimuovere il frontend statico

**Files:**
- Create: `resources/views/components/layouts/app.blade.php`
- Modify: `resources/css/app.css`
- Modify: `resources/js/app.js`
- Move: `faces/` to `public/faces/`
- Delete: `index.html`
- Delete: `main.js`
- Delete: `style.css`
- Delete: `test/exclusions.test.cjs`
- Test: `tests/Feature/UiSmokeTest.php`

- [ ] **Step 1: Scrivere lo smoke test fallente delle pagine**

```php
public function test_authenticated_navigation_exposes_board_colleagues_leaderboard_and_logout(): void
{
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('board'))
        ->assertOk()
        ->assertSee('Dunp-ingo')
        ->assertSee('Colleghi')
        ->assertSee('Classifica')
        ->assertSee('Esci');
}
```

- [ ] **Step 2: Verificare il fallimento**

Run: `php artisan test tests/Feature/UiSmokeTest.php`

Expected: FAIL finché layout e navigazione non sono migrati.

- [ ] **Step 3: Portare markup, CSS e immagini**

Il layout include `@vite(['resources/css/app.css', 'resources/js/app.js'])`, `@livewireStyles`, navigazione e slot. Copiare le regole visuali utili da `style.css`, eliminando selettori non più usati. Spostare le immagini:

```powershell
git mv faces public/faces
```

Usare `asset('faces/'.$filename)` per i volti del gioco e l'avatar Google per profilo e liste utenti.

- [ ] **Step 4: Portare i soli effetti necessari e cancellare il legacy**

`resources/js/app.js` mantiene la lista `FACE_FILES` già esistente e i soli listener degli effetti:

```js
document.addEventListener('bingo-completed', () => {
    window.confetti?.({ particleCount: 120, spread: 80, origin: { y: 0.7 } });
});

document.addEventListener('cell-marked', (event) => {
    const file = FACE_FILES[Math.floor(Math.random() * FACE_FILES.length)];
    showFaceBubble(event.detail.position, `/faces/${file}`);
});
```

Portare senza modificarle le piccole funzioni esistenti `showFaceBubble`, `positionFaceBubble` e `clamp`. Caricare `canvas-confetti` tramite l'attuale CDN nel layout, senza aggiungere un pacchetto npm. Poi:

```powershell
git rm index.html main.js style.css test/exclusions.test.cjs
```

- [ ] **Step 5: Verificare build, test e commit**

Run:

```powershell
npm run build
php artisan test tests/Feature/UiSmokeTest.php
php artisan test
git add resources public/faces tests/Feature/UiSmokeTest.php
git commit -m "feat: migrate bingo interface to Livewire"
```

Expected: build e suite passano; non rimangono entrypoint statici duplicati.

---

### Task 10: Verifica completa e istruzioni di deploy

**Files:**
- Create: `README.md`
- Modify: `.env.example`
- Test: intera suite

- [ ] **Step 1: Documentare setup e configurazione senza aggiungere automazione**

Il README deve contenere questi comandi:

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
npm install
npm run build
php artisan migrate --seed
php artisan optimize
```

Documentare `APP_URL`, `APP_TIMEZONE=Europe/Rome`, `ADMIN_EMAIL`, `GOOGLE_WORKSPACE_DOMAIN`, credenziali Google, callback HTTPS e variabili MySQL. Specificare che non servono queue worker, Redis o WebSocket.

Documentare anche il passaggio iniziale: precreare gli utenti e copiare per ciascuno il valore corrente di `database/data/leaderboard.json` in `starting_score` dal pannello admin.

- [ ] **Step 2: Verificare la configurazione di produzione**

Run con un `.env` locale non committato che usa MySQL:

```powershell
php artisan config:clear
php artisan migrate:fresh --seed
php artisan about
```

Expected: timezone `Europe/Rome`, connessione MySQL attiva e seed completato.

- [ ] **Step 3: Eseguire tutte le verifiche automatiche**

Run:

```powershell
php artisan test
npm run build
git diff --check
```

Expected: tutti i test passano, build riuscita, nessun errore whitespace.

- [ ] **Step 4: Eseguire il controllo manuale minimo**

Run: `php artisan serve`

Verificare in browser:

1. redirect Google e rifiuto di un account non autorizzato;
2. creazione stabile della scheda e persistenza dopo refresh;
3. marcatura, festeggiamenti e invio;
4. polling read-only da una seconda sessione;
5. approvazione admin e incremento classifica;
6. layout desktop e mobile senza errori console.

- [ ] **Step 5: Committare documentazione e stato finale**

Run:

```powershell
git add README.md .env.example
git commit -m "docs: add Laravel bingo deployment guide"
git status --short
```

Expected: resta soltanto qualsiasi modifica utente esplicitamente esclusa; nessun file generato o segreto è tracciato.
