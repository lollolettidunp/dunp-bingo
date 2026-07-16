<section
    x-data="{ show: false, timer: null }"
    x-on:cell-suggested.window="show = true; clearTimeout(timer); timer = setTimeout(() => show = false, 2800)"
>
    <div class="suggest-cta">
        <button class="btn is-ghost" type="button" wire:click="openModal">💡 Consiglia una cella</button>
    </div>

    @if ($open)
        <div class="modal-backdrop" wire:click="close">
            <section class="modal" wire:click.stop role="dialog" aria-modal="true" aria-label="Consiglia una cella">
                <button class="btn is-ghost dialog-close" type="button" wire:click="close" aria-label="Chiudi">×</button>
                <div class="panel-head">
                    <h3 style="margin:0;">Consiglia una cella</h3>
                    <span class="spacer"></span>
                    <span class="mode-badge is-new">Nuovo consiglio</span>
                </div>

                <form wire:submit="submit" class="form-grid">
                    <div class="field">
                        <label for="suggest-text">Testo della cella</label>
                        <textarea id="suggest-text" wire:model="text" placeholder="Es. Qualcuno dimentica di silenziare il microfono" @error('text') aria-invalid="true" @enderror></textarea>
                        @error('text') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="field">
                        <span class="field-label">Difficoltà</span>
                        <div class="check-grid" role="radiogroup" aria-label="Difficoltà">
                            @foreach (['1' => 'Facile', '2' => 'Media', '3' => 'Difficile'] as $value => $label)
                                <label class="check"><input type="radio" wire:model="difficulty" value="{{ $value }}"> <span class="badge-diff d{{ $value }}">D{{ $value }}</span> {{ $label }}</label>
                            @endforeach
                        </div>
                        @error('difficulty') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="review-actions">
                        <button class="btn is-sky" type="submit">Invia consiglio</button>
                        <button class="btn is-ghost" type="button" wire:click="close">Annulla</button>
                    </div>
                </form>
            </section>
        </div>
    @endif

    <div class="toast" x-bind:class="{ 'is-visible': show }" role="status" aria-live="polite" x-cloak>
        Grazie! Il tuo consiglio è stato inviato 💡
    </div>
</section>
