<section class="panel">
    <h2>Utenti</h2>
    <form wire:submit="save" class="form-grid">
        <input type="email" wire:model="email" placeholder="email">
        <input type="text" wire:model="name" placeholder="nome">
        <input type="number" min="0" wire:model="startingScore" placeholder="punti iniziali">
        <label><input type="checkbox" wire:model="isEnabled"> abilitato</label>
        <button class="button" type="submit">Salva utente</button>
    </form>
    @foreach ($errors->all() as $error)<p class="error small">{{ $error }}</p>@endforeach
    <div class="list">
        @foreach ($users as $user)
            <button class="list-button" type="button" wire:click="edit({{ $user->id }})">{{ $user->name }} — {{ $user->email }} — {{ $user->is_enabled ? 'on' : 'off' }} — {{ $user->starting_score }}</button>
        @endforeach
    </div>
</section>
