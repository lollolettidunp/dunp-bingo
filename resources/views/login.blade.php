<!doctype html>
<html lang="it">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Dunp-ingo</title>@unless (app()->environment('testing'))@vite(['resources/css/app.css'])@endunless</head>
<body><main class="page login"><h1>Dunp-ingo</h1><a class="button" href="{{ route('login.google') }}">Entra con Google</a></main></body>
</html>
