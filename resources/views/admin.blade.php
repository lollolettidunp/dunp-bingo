<x-layouts.app>
    <div
        x-data="{ show: false, message: '', timer: null }"
        x-on:admin-saved.window="message = $event.detail.message || 'Salvato'; show = true; clearTimeout(timer); timer = setTimeout(() => show = false, 2400)"
    >
        <section class="stack">
            <livewire:admin.reviews />
            <livewire:admin.users />
            <livewire:admin.cells />
        </section>

        <div
            class="toast"
            x-bind:class="{ 'is-visible': show }"
            x-text="message"
            role="status"
            aria-live="polite"
            x-cloak
        ></div>
    </div>
</x-layouts.app>
