<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dunp-ingo</title>
    @unless (app()->environment('testing'))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endunless
    @livewireStyles
    <script defer src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js"></script>
</head>
<body>
    <main class="page">
        <header class="hero">  
            <div>
                <img src="{{ asset('logo.png') }}" width="200px" alt="Dunp-ingo" class="logo" />
            </div>
            <nav class="nav">
                <a href="{{ route('board') }}" title="La tua scheda bingo di oggi">Scheda</a>
                <a href="{{ route('colleagues') }}" title="Guarda le schede dei colleghi">Colleghi</a>
                <a href="{{ route('leaderboard') }}" title="Vedi i punti approvati">Classifica</a>
                @if (strtolower(auth()->user()?->email ?? '') === strtolower(config('services.google.admin_email')))
                    <a href="{{ route('admin') }}">Admin</a>
                @endif
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit">Esci</button>
                </form>
            </nav>
        </header>

        {{ $slot }}
    </main>

    <div id="bingoBanner" class="bingo-banner">BINGO!</div>
    @livewireScripts
</body>
</html>
