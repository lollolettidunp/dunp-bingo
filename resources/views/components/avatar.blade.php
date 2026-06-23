@props(['user', 'size' => 40])
@php
    $name = trim($user->name ?? '');
    $initials = collect(preg_split('/\s+/', $name))
        ->filter()
        ->take(2)
        ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
        ->implode('');
    $palette = ['#7dd3fc', '#d9f99d', '#ffd166', '#ff5c8a', '#34d399'];
    $tint = $palette[abs(crc32($name)) % count($palette)];
@endphp
<span {{ $attributes->merge(['class' => 'avatar']) }} style="--avatar-size: {{ $size }}px;" aria-hidden="true">
    @if (!empty($user->avatar_url))
        <img src="{{ $user->avatar_url }}" alt="" loading="lazy" referrerpolicy="no-referrer">
    @else
        <span class="avatar__initials" style="background: {{ $tint }}">{{ $initials ?: '?' }}</span>
    @endif
</span>
