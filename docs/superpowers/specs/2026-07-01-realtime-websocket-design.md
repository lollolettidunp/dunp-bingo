# Realtime WebSocket Design

## Goal

Rendere l'app quasi istantanea per i flussi gia' esistenti: scheda personale, schede colleghi, feed "cosa potresti esserti perso", coda review admin e aggiornamenti causati dalle modifiche alle celle.

La soluzione usa Laravel Reverb, Laravel Echo e i listener Echo di Livewire. Non introduce una SPA, presence channels, notifiche push o sincronizzazione granulare cella-per-cella.

## Architecture

Laravel continua a servire Blade e componenti Livewire. Reverb aggiunge il server WebSocket usato dal sistema di broadcasting di Laravel. Echo espone gli eventi al browser; Livewire ascolta quegli eventi e richiama il render del componente interessato.

Dipendenze previste:

- `laravel/reverb`
- `laravel-echo`
- `pusher-js`

Processi di sviluppo:

- `php artisan serve`
- `php artisan queue:listen --tries=1 --timeout=0`
- `php artisan reverb:start`
- `npm run dev`

## Channels

`game.today` e' un canale privato per utenti autenticati. Aggiorna le viste condivise della giornata: colleghi, missed feed, classifica e coda review admin.

`boards.{userId}` e' un canale privato per il proprietario della scheda. Aggiorna la scheda personale quando cambia per effetto di approvazioni, rifiuti o sostituzioni celle.

L'admin usa gli stessi canali piu' le autorizzazioni gia' presenti. Non serve un canale admin separato finche' gli eventi condivisi non trasportano dati sensibili.

## Events

`BoardChanged` viene emesso quando una scheda cambia stato o marcature:

- mark/unmark di una casella;
- submit in review;
- approvazione;
- rifiuto.

Broadcast su `game.today` e `boards.{userId}`.

`CellCatalogChanged` viene emesso dopo il salvataggio admin di una cella. Broadcast su `game.today`.

Se il salvataggio cella sostituisce caselle nelle schede gia' aperte, l'azione ritorna gli user id toccati e il chiamante emette `BoardChanged` anche su quei `boards.{userId}`.

## Component Behavior

`DailyBoard` ascolta `boards.{auth()->id()}` e ricarica la propria scheda.

`ColleagueBoards`, `MissedFeed`, `Leaderboard` e `Admin\Reviews` ascoltano `game.today` e si aggiornano quando arriva un evento.

Il polling viene rimosso dai componenti coperti da Echo. Se Reverb non e' configurato, l'app resta funzionante dopo refresh manuale, ma non realtime.

## Error Handling

Le autorizzazioni dei canali sono server-side in `routes/channels.php`.

Gli eventi non contengono testo celle, note review o dati personali non necessari: trasportano solo id, tipo evento e timestamp. La verita' resta nel database e Livewire ricarica i dati con le query esistenti.

Se il WebSocket cade, Echo prova a riconnettersi tramite il client Pusher. Non viene aggiunto polling fallback per evitare due sistemi realtime paralleli.

## Testing

Test PHP minimi:

- autorizzazione canale `game.today` per utenti autenticati;
- autorizzazione canale `boards.{userId}` solo per il proprietario;
- eventi broadcast sui canali corretti;
- `ReplaceInvalidBoardCells` ritorna gli utenti delle schede modificate.

Build asset per verificare che Echo/Reverb siano compilati correttamente.

## Skipped

Presence channels, stato online, eventi per singola cella, dashboard connessioni e fallback polling. Si aggiungono solo quando c'e' un bisogno reale misurabile.
