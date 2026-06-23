<section class="panel">
    <div class="panel-head">
        <h2>Celle</h2>
        <span class="spacer"></span>
        @if ($cellId)
            <span class="mode-badge">Modifica cella</span>
        @else
            <span class="mode-badge is-new">Nuova cella</span>
        @endif
    </div>

    <form wire:submit="save" class="form-grid">
        <div class="field">
            <label for="cell-text">Testo della cella</label>
            <textarea id="cell-text" wire:model="text" placeholder="Es. Qualcuno arriva in ritardo alla riunione" @error('text') aria-invalid="true" @enderror></textarea>
            @error('text') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div class="field-row">
            <div class="field">
                <span class="field-label">Difficoltà</span>
                <div class="check-grid" role="radiogroup" aria-label="Difficoltà">
                    @foreach (['1' => 'Facile', '2' => 'Media', '3' => 'Difficile'] as $value => $label)
                        <label class="check"><input type="radio" wire:model="difficulty" value="{{ $value }}"> <span class="badge-diff d{{ $value }}">D{{ $value }}</span> {{ $label }}</label>
                    @endforeach
                </div>
                @error('difficulty') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label for="cell-date">Data speciale <span style="font-weight:700;">(opzionale)</span></label>
                <input id="cell-date" type="date" wire:model="specialDate" @error('specialDate') aria-invalid="true" @enderror>
                @error('specialDate') <p class="form-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <label class="toggle"><input type="checkbox" wire:model="isActive"> Cella attiva</label>

        <fieldset class="fieldset">
            <legend>Giorni esclusi</legend>
            <div class="check-grid">
                @foreach ($weekdays as $day)
                    <label class="check"><input type="checkbox" wire:model="excludedWeekdays" value="{{ $day }}"> {{ $day }}</label>
                @endforeach
            </div>
        </fieldset>

        <fieldset class="fieldset">
            <legend>Riguarda solo <span style="font-weight:700;">(vuoto = tutti)</span></legend>
            <div class="check-grid">
                @forelse ($users as $user)
                    <label class="check"><input type="checkbox" wire:model="userIds" value="{{ $user->id }}"> {{ $user->name }}</label>
                @empty
                    <small style="color:#6f6a62;font-weight:700;">Nessun utente disponibile.</small>
                @endforelse
            </div>
        </fieldset>

        <div class="review-actions">
            <button class="btn is-sky" type="submit">{{ $cellId ? 'Salva modifiche' : 'Crea cella' }}</button>
            @if ($cellId)
                <button class="btn is-ghost" type="button" wire:click="cancel">Annulla</button>
            @endif
        </div>
    </form>

    <div class="record-list">
        @forelse ($cells as $cell)
            <button class="record" type="button" wire:click="edit({{ $cell->id }})" wire:key="cell-{{ $cell->id }}">
                <span class="record-aside">
                    <span class="badge-diff d{{ $cell->difficulty }}">D{{ $cell->difficulty }}</span>
                </span>
                <span class="record-main">
                    <strong style="white-space:normal;">{{ $cell->text }}</strong>
                    <span>
                        {{ $cell->users->isNotEmpty() ? $cell->users->count().' utenti' : 'Tutti' }}
                        @if ($cell->special_date) · 📅 {{ $cell->special_date->format('d/m/Y') }} @endif
                    </span>
                </span>
                <span class="record-aside">
                    <span class="pill {{ $cell->is_active ? 'on' : 'off' }}">{{ $cell->is_active ? 'Attiva' : 'Off' }}</span>
                </span>
            </button>
        @empty
            <div class="empty">
                <span class="empty-emoji" aria-hidden="true">🎲</span>
                <p>Ancora nessuna cella</p>
                <small>Aggiungi le prime celle per generare le schede giornaliere.</small>
            </div>
        @endforelse
    </div>
</section>
