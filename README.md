# Dunp Bingo

Laravel + Livewire, MySQL, Google login. Niente queue worker, Redis o WebSocket.

## Setup

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
npm install
npm run build
php artisan migrate --seed
php artisan optimize
```

## Config

Imposta nel `.env`:

- `APP_URL=https://bingo.tuodominio.it`
- `APP_TIMEZONE=Europe/Rome`
- `ADMIN_EMAIL=admin@azienda.it`
- `GOOGLE_WORKSPACE_DOMAIN=azienda.it`
- `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`
- `GOOGLE_REDIRECT_URI=https://bingo.tuodominio.it/login/google/callback`
- variabili `DB_*` MySQL dell'hosting

Nel primo avvio entra come admin, precrea gli utenti esterni se servono e copia da `database/data/leaderboard.json` il vecchio punteggio in `starting_score`.

## Deploy

Punta il sottodominio alla cartella `public/`. Dopo ogni deploy:

```powershell
composer install --no-dev --optimize-autoloader
npm install
npm run build
php artisan migrate --force
php artisan optimize
```
