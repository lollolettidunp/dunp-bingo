# Celle Speciali Design

## Goal
Gli admin creano adesivi giornalieri. Gli utenti li vedono nella pagina della scheda e ne applicano uno sopra una casella normale per sostituirla; l'adesivo usato sparisce per quell'utente fino al giorno dopo.

## Design
- `cells.special_date` identifica un adesivo valido solo in quella data.
- Le schede giornaliere vengono generate solo da celle ordinarie (`special_date = null`).
- Un adesivo e' consumato per utente/giorno quando il relativo `cell_id` compare gia' nella sua `board_cells`.
- Non serve una tabella di uso separata: la scheda e' gia' lo snapshot giornaliero.
- Il bonus centrale non puo' essere coperto. Le schede `pending` e `approved` restano bloccate.

## Admin
Il pannello `Celle` resta per il catalogo ordinario. Un nuovo pannello `Adesivi del giorno` crea e modifica solo celle con `special_date`, con campi testo, difficolta', data, attivo e destinatari.

## Player
La scheda mostra un drawer stile notifiche quando ci sono adesivi disponibili oggi. L'utente puo' trascinare un adesivo su una cella, con fallback click/tap: seleziona adesivo e poi tocca la cella.

## Testing
Testare generazione senza speciali, creazione admin dell'adesivo e sostituzione Livewire della cella con consumo per utente.
