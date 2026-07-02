<section class="panel">
    <div class="panel-head">
        <h2>Adesivi del giorno</h2>
        <span class="spacer"></span>
        @if ($cellId)
            <span class="mode-badge">Modifica adesivo</span>
        @else
            <span class="mode-badge is-new">Nuovo adesivo</span>
        @endif
    </div>

    <form wire:submit="save" class="form-grid">
        <div class="field-row">
            <div class="field">
                <label for="special-text">Testo adesivo</label>
                <textarea id="special-text" wire:model="text" placeholder="Es. Pausa caffe improvvisata" @error('text') aria-invalid="true" @enderror></textarea>
                @error('text') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label for="special-date">Giornata</label>
                <input id="special-date" type="date" wire:model="specialDate" @error('specialDate') aria-invalid="true" @enderror>
                @error('specialDate') <p class="form-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="field">
            <span class="field-label">Difficoltà</span>
            <div class="check-grid" role="radiogroup" aria-label="Difficoltà adesivo">
                @foreach (['1' => 'Facile', '2' => 'Media', '3' => 'Difficile'] as $value => $label)
                    <label class="check"><input type="radio" wire:model="difficulty" value="{{ $value }}"> <span class="badge-diff d{{ $value }}">D{{ $value }}</span> {{ $label }}</label>
                @endforeach
            </div>
            @error('difficulty') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <label class="toggle"><input type="checkbox" wire:model="isActive"> Adesivo attivo</label>

        <fieldset class="fieldset">
            <legend>Disponibile solo per <span style="font-weight:700;">(vuoto = tutti)</span></legend>
            <div class="check-grid">
                @forelse ($users as $user)
                    <label class="check"><input type="checkbox" wire:model="userIds" value="{{ $user->id }}"> {{ $user->name }}</label>
                @empty
                    <small style="color:#6f6a62;font-weight:700;">Nessun utente disponibile.</small>
                @endforelse
            </div>
        </fieldset>

        <div class="review-actions">
            <button class="btn is-sky" type="submit">{{ $cellId ? 'Salva modifiche' : 'Crea adesivo' }}</button>
            @if ($cellId)
                <button class="btn is-ghost" type="button" wire:click="cancel">Annulla</button>
            @endif
        </div>
    </form>

    <div class="record-list">
        @forelse ($cells as $cell)
            <button class="record special-record" type="button" wire:click="edit({{ $cell->id }})" wire:key="special-cell-{{ $cell->id }}">
                <span class="record-aside">
                    <span class="badge-diff d{{ $cell->difficulty }}">D{{ $cell->difficulty }}</span>
                </span>
                <span class="record-main">
                    <strong style="white-space:normal;">{{ $cell->text }}</strong>
                    <span>{{ $cell->special_date->format('d/m/Y') }} - {{ $cell->users->isNotEmpty() ? $cell->users->count().' utenti' : 'Tutti' }}</span>
                </span>
                <span class="record-aside">
                    <span class="pill {{ $cell->is_active ? 'on' : 'off' }}">{{ $cell->is_active ? 'Attivo' : 'Off' }}</span>
                </span>
            </button>
        @empty
            <div class="empty">
                <span class="empty-emoji" aria-hidden="true">🏷️</span>
                <p>Nessun adesivo programmato</p>
                <small>Creane uno per farlo comparire oggi nella sidebar della scheda.</small>
            </div>
        @endforelse
    </div>
</section>
