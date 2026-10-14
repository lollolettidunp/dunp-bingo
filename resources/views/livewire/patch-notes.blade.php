<div>
    @if ($due)
        <div class="modal-backdrop patch-notes" x-data x-on:keydown.escape.window="$wire.dismiss()">
            <section class="modal patch-notes__sheet" role="dialog" aria-modal="true" aria-labelledby="patch-notes-title" x-trap.noscroll="true">
                <button class="btn is-ghost dialog-close" type="button" wire:click="dismiss" aria-label="Chiudi">×</button>

                <header class="patch-notes__head">
                    <h2 id="patch-notes-title" class="patch-notes__title">Da oggi si gioca così</h2>
                    <p class="patch-notes__lead">Abbiamo cambiato qualche regola. Prendi appunti sennò poi piangi.</p>
                </header>

                <ul class="patch-notes__list">
                    <li class="patch-notes__item">
                        <span class="patch-notes__stamp" aria-hidden="true">🎁</span>
                        <div>
                            <strong>La scheda arriva in bustina</strong>
                            <p>UOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOO!!!!!</p>
                        </div>
                    </li>
                    <li class="patch-notes__item">
                        <span class="patch-notes__stamp" aria-hidden="true">🎯</span>
                        <div>
                            <strong>Un bingo non basta più</strong>
                            <p>Dopo il primo continua a giocare: ogni linea in più vale 0,5 punti e si invia a parte, più ne fai meglio è!!</p>
                        </div>
                    </li>
                    <li class="patch-notes__item">
                        <span class="patch-notes__stamp" aria-hidden="true">⚡</span>
                        <div>
                            <strong>Chi prima arriva, più vince</strong>
                            <p>Il primo bingo vale 1 punto, ma raddoppia se lo invii entro le 11:00 e vale 1,5 entro le 15:00.</p>
                        </div>
                    </li>
                    <li class="patch-notes__item">
                        <span class="patch-notes__stamp" aria-hidden="true">🏆</span>
                        <div>
                            <strong>Classifica per periodo</strong>
                            <p>Scopri chi comanda questa settimana, questo mese o da sempre.</p>
                        </div>
                    </li>
                </ul>

                <div class="patch-notes__foot">
                    <button class="btn is-lime patch-notes__ok" type="button" wire:click="dismiss" wire:loading.attr="disabled" autofocus>Ho capito, famme gioca!</button>
                </div>
            </section>
        </div>
    @endif
</div>
