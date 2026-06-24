<div
    x-data="{ open: false }"
    x-on:keydown.escape.window="open = false"
    wire:poll.30s
>
    <button type="button" class="missed-trigger" x-on:click="open = true"
        aria-haspopup="dialog" :aria-expanded="open.toString()">
        <span aria-hidden="true">👀</span>
        <span class="missed-trigger__label">Cosa potresti esserti perso</span>
        @if ($entries->isNotEmpty())
            <span class="missed-trigger__badge">{{ $entries->count() }}</span>
        @endif
    </button>

    <div class="missed-scrim" x-show="open" x-transition.opacity
        x-on:click="open = false" x-cloak></div>

    <aside class="missed-drawer" role="dialog" aria-modal="true" aria-labelledby="missed-title"
        x-show="open" x-cloak
        x-transition:enter="missed-drawer--enter"
        x-transition:enter-start="missed-drawer--from"
        x-transition:enter-end="missed-drawer--to"
        x-transition:leave="missed-drawer--enter"
        x-transition:leave-start="missed-drawer--to"
        x-transition:leave-end="missed-drawer--from"
        x-trap.noscroll="open"
    >
        <header class="missed-drawer__head">
            <h2 id="missed-title" class="missed-drawer__title">
                <span aria-hidden="true">👀</span> Cosa potresti esserti perso
            </h2>
            <button type="button" class="missed-drawer__close" x-on:click="open = false" aria-label="Chiudi">×</button>
        </header>

        <div class="missed-drawer__body">
            @forelse ($entries as $entry)
                <div class="missed-item" wire:key="missed-{{ $entry->id }}">
                    <x-avatar :user="$entry->board->user" :size="36" />
                    <div class="missed-item__text">
                        <p class="missed-item__line">
                            <strong>{{ $entry->board->user->name }}</strong> ha checkato
                            <span class="missed-item__cell">{{ $entry->text }}</span>
                        </p>
                        <time class="missed-item__time" datetime="{{ $entry->marked_at->toIso8601String() }}">
                            {{ $entry->marked_at->timezone(config('app.timezone'))->format('H:i') }}
                        </time>
                    </div>
                </div>
            @empty
                <div class="missed-empty">
                    <span class="missed-empty__emoji" aria-hidden="true">🌱</span>
                    <p>Per ora non ti sei perso niente.</p>
                    <small>Quando un collega segna una casella prima di te, comparirà qui.</small>
                </div>
            @endforelse
        </div>
    </aside>
</div>
