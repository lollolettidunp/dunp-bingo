<a href="{{ route('admin') }}" class="admin-link" wire:poll.10s title="Gestisci il bingo">
    Admin
    @if ($pending > 0)
        <span class="admin-link__badge" aria-label="{{ $pending }} schede in revisione">{{ $pending > 99 ? '99+' : $pending }}</span>
    @endif
</a>
