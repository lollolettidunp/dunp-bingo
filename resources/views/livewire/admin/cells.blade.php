<section class="panel">
    <h2>Celle</h2>
    <form wire:submit="save" class="form-grid">
        <textarea wire:model="text" placeholder="testo"></textarea>
        <input type="number" min="1" max="3" wire:model="difficulty">
        <input type="date" wire:model="specialDate">
        <label><input type="checkbox" wire:model="isActive"> attiva</label>
        <div>
            <strong>Giorni esclusi</strong>
            @foreach ($weekdays as $day)<label><input type="checkbox" wire:model="excludedWeekdays" value="{{ $day }}"> {{ $day }}</label>@endforeach
        </div>
        <div>
            <strong>Riguarda</strong>
            @foreach ($users as $user)<label><input type="checkbox" wire:model="userIds" value="{{ $user->id }}"> {{ $user->name }}</label>@endforeach
        </div>
        <button class="button" type="submit">Salva cella</button>
    </form>
    <div class="list">
        @foreach ($cells as $cell)
            <button class="list-button" type="button" wire:click="edit({{ $cell->id }})">{{ $cell->text }} — D{{ $cell->difficulty }} {{ $cell->special_date ? '— '.$cell->special_date->toDateString() : '' }}</button>
        @endforeach
    </div>
</section>
