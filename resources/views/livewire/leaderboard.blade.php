<section class="panel">
    <h2>Classifica</h2>
    <ol class="leaderboard-list">
        @forelse ($users as $user)
            <li><span>{{ $user->name }}</span><strong>{{ $user->score }}</strong><small>{{ $user->last_approved_at ? 'Ultimo: '.$user->last_approved_at : '' }}</small></li>
        @empty
            <li>Nessun risultato.</li>
        @endforelse
    </ol>
</section>
