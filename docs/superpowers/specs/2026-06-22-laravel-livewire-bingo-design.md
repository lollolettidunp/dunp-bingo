# Dunp Bingo Laravel/Livewire — Design

## Obiettivo

Trasformare l'attuale bingo statico in un piccolo monolite Laravel con Livewire e MySQL, mantenendo l'esperienza esistente e aggiungendo autenticazione Google, gestione centralizzata delle celle, schede personalizzate, celle speciali, bilanciamento per difficoltà, revisione dei bingo e consultazione delle schede dei colleghi.

Il criterio guida è ponytail: dipendenze minime, un solo applicativo, nessuna astrazione preventiva.

## Perimetro

L'MVP comprende:

- accesso Google Workspace aziendale;
- allowlist manuale per eventuali account Google esterni;
- un solo amministratore configurato tramite email in `.env`;
- gestione di utenti e celle;
- associazione di una cella a zero o più persone coinvolte;
- esclusione dalla scheda delle celle associate all'utente corrente;
- difficoltà `1` facile, `2` media e `3` difficile;
- una scheda 5×5 giornaliera per utente, con centro bonus già marcato;
- celle speciali programmate per una data e garantite nelle schede eleggibili di quel giorno;
- persistenza delle marcature sul server;
- invio del bingo in revisione e approvazione o rifiuto da parte dell'admin;
- classifica basata sul totale delle schede approvate;
- visualizzazione in sola lettura delle schede giornaliere degli altri utenti.

Non comprende API separate, SPA, WebSocket, notifiche, inviti, ruoli configurabili, inserimento di celle speciali da parte degli utenti o ottimizzazione matematica esatta delle schede.

## Architettura

Un'unica applicazione Laravel serve pagine Blade e componenti Livewire. MySQL conserva utenti, celle, schede e marcature. Laravel Socialite gestisce OAuth Google.

Le sole dipendenze applicative aggiuntive previste sono Livewire e Socialite. Il pannello admin è costruito con normali componenti Livewire; Filament e sistemi di autorizzazione a ruoli non sono necessari.

Le schermate sono:

1. login Google;
2. scheda personale del giorno;
3. elenco e dettaglio in sola lettura delle schede odierne dei colleghi;
4. classifica;
5. gestione admin di utenti e celle;
6. coda admin delle revisioni.

## Autenticazione e autorizzazione

Al callback Google l'applicazione normalizza l'email in minuscolo. Un utente esistente e disabilitato viene sempre rifiutato. Negli altri casi l'accesso è consentito quando si verifica almeno una condizione:

- il dominio dell'email coincide con `GOOGLE_WORKSPACE_DOMAIN`;
- esiste già un utente abilitato con quella email, pre-autorizzato manualmente dall'admin.

Il primo accesso aziendale crea l'utente. Un account esterno deve essere inserito prima dall'admin. Il record viene collegato all'identificativo Google al primo accesso riuscito.

L'admin è l'utente la cui email coincide con `ADMIN_EMAIL`. Un middleware protegge tutte le rotte amministrative. Non esistono colonne `role`, policy generiche o interfacce di gestione permessi.

## Modello dati

### `users`

- `id`
- `google_id`, nullable e univoco
- `email`, univoca
- `name`
- `avatar_url`, nullable
- `is_enabled`, booleano
- `starting_score`, intero non negativo, predefinito `0`
- timestamp

`starting_score` permette di riportare manualmente i punteggi già presenti in `leaderboard.json` senza creare risultati storici fittizi.

### `cells`

- `id`
- `text`
- `difficulty`, tiny integer tra `1` e `3`
- `is_active`, booleano
- `special_date`, data nullable
- `excluded_weekdays`, JSON nullable con i giorni in cui la cella non è eleggibile
- timestamp

Una cella con `special_date = null` è ordinaria. Una cella speciale è eleggibile soltanto nella data indicata. La cancellazione dal pannello usa `is_active = false`, così le schede storiche restano leggibili.

### `cell_user`

- `cell_id`
- `user_id`
- chiave primaria composta

Una cella associata a un utente non può comparire nella sua scheda. La stessa cella può essere associata a più utenti.

### `boards`

- `id`
- `user_id`
- `played_on`, data
- `status`: `playing`, `pending`, `approved`, `rejected`
- `submitted_at`, nullable
- `reviewed_at`, nullable
- `review_note`, nullable
- timestamp
- vincolo univoco su `user_id, played_on`

### `board_cells`

- `id`
- `board_id`
- `cell_id`, nullable
- `position`, intero da `0` a `24`
- `text`, fotografia del testo mostrato
- `difficulty`, fotografia del peso
- `marked_at`, nullable
- vincolo univoco su `board_id, position`

Il centro bonus ha `cell_id = null`, testo `BONUS`, difficoltà `0` ed è marcato alla creazione. Testo e difficoltà sono copiati nella scheda: modificare una cella centrale non altera schede già generate o sottoposte a revisione.

## Generazione della scheda

La scheda nasce al primo accesso dell'utente nella giornata e non viene rigenerata. La creazione avviene in transazione e il vincolo univoco impedisce doppioni in caso di richieste concorrenti.

Le celle eleggibili sono quelle attive che:

- non sono associate all'utente;
- sono ordinarie oppure hanno `special_date` uguale al giorno corrente;
- rispettano le eventuali esclusioni per giorno della settimana importate dal JSON esistente.

Tutte le celle speciali eleggibili del giorno vengono incluse. I posti restanti sono riempiti con celle ordinarie. Se le celle disponibili sono meno di 24, la scheda non viene creata e viene mostrato un errore utile all'admin. Più di 24 celle speciali eleggibili per lo stesso utente e giorno è una configurazione non valida.

Il bilanciamento usa una piccola euristica:

1. genera fino a 100 disposizioni casuali delle 24 celle attorno al bonus;
2. calcola per ciascuna la differenza tra il totale più alto e più basso delle cinque righe e delle cinque colonne;
3. conserva la disposizione con differenza minore, interrompendosi subito se raggiunge una differenza massima di `1`.

Non vengono bilanciate le diagonali. L'euristica non promette una soluzione perfetta, ma produce schede eque senza solver o dipendenze. Un commento `ponytail:` documenterà il limite e indicherà un solver come upgrade solo se emergono schede concretamente sbilanciate.

## Gioco e stato

In stato `playing` o `rejected`, l'utente può marcare e smarcare le proprie celle. Ogni azione Livewire aggiorna `marked_at` nel database. Il server ricalcola righe, colonne e diagonali complete; il client gestisce soltanto gli effetti visivi e i festeggiamenti.

Quando esiste almeno una linea completa, compare il comando "Invia in revisione". L'invio ricontrolla la linea sul server e porta la scheda in `pending`. Una scheda `pending` o `approved` è bloccata e non più modificabile.

L'admin può:

- approvare: stato `approved`;
- rifiutare con nota facoltativa: stato `rejected`, nuovamente modificabile e reinviabile.

Poiché esiste una sola scheda per utente e giorno, ogni utente può ottenere al massimo un punto approvato al giorno.

## Classifica

Il punteggio visualizzato è:

`users.starting_score + numero di boards approved dell'utente`

La classifica è ordinata per punteggio decrescente e, a parità, per nome. Mostra anche la data dell'ultima approvazione quando presente. Non viene mantenuta una seconda tabella di risultati o un contatore duplicato.

## Schede dei colleghi

Gli utenti autenticati vedono le schede create nella giornata corrente. L'elenco mostra nome, progresso e stato; il dettaglio mostra celle e marcature in sola lettura.

La pagina aperta si aggiorna con polling Livewire ogni 15 secondi. Non vengono introdotti WebSocket o infrastruttura realtime. Le schede di utenti che non hanno ancora aperto l'app quel giorno non esistono e quindi non compaiono.

## Amministrazione

### Utenti

L'admin può aggiungere un account esterno tramite email, modificare nome e `starting_score`, autorizzarlo o disabilitarlo. Gli utenti del dominio aziendale vengono creati automaticamente al primo login; possono anche essere precreati per associarli alle celle prima del loro primo accesso.

### Celle

L'admin può creare, modificare, attivare o disattivare una cella, assegnare difficoltà, data speciale, giorni esclusi e persone coinvolte. Le modifiche valgono soltanto per schede non ancora generate.

La migrazione iniziale importa i testi e le esclusioni settimanali da `bingo.json`. La modifica locale esistente al file viene preservata. I punteggi correnti di `leaderboard.json` vengono riportati nei rispettivi `starting_score` durante la configurazione iniziale degli utenti.

### Revisioni

La coda mostra prima le schede `pending` più vecchie. L'admin vede la scheda congelata, la linea completata e può approvare o rifiutare senza modificare le marcature.

## Errori e vincoli

- Callback Google incompleto, dominio non ammesso o email non autorizzata: accesso negato senza creare sessione.
- Pool insufficiente o troppe celle speciali: nessuna scheda parziale; messaggio all'utente e dettaglio diagnostico nei log.
- Azione su una scheda non propria o bloccata: rifiuto lato server.
- Invio senza linea completa: validazione fallita, stato invariato.
- Doppia approvazione o richieste concorrenti: aggiornamento condizionato allo stato `pending` dentro una transazione.
- Date applicative calcolate nel timezone `Europe/Rome`.

## Interfaccia

L'aspetto attuale viene trasferito in Blade/Livewire riutilizzando CSS, immagini dei colleghi, griglia 5×5, bonus centrale, doppio click, animazione della linea e confetti. Il mobile mantiene celle utilizzabili e controlli accessibili da tastiera; il doppio click non è l'unico modo per marcare una cella.

Il pannello admin privilegia form e tabelle HTML semplici. Non è prevista una nuova design system.

## Test

Il set minimo di test copre i comportamenti con rischio reale:

- accesso consentito per dominio, allowlist e admin; accesso esterno negato;
- esclusione delle celle associate all'utente e delle esclusioni settimanali;
- inclusione garantita delle celle speciali eleggibili;
- generazione di 25 posizioni uniche con bonus centrale e bilanciamento entro il miglior risultato trovato;
- stabilità della scheda dopo modifiche alle celle sorgente;
- marcature consentite solo al proprietario e solo negli stati modificabili;
- invio consentito soltanto con una linea completa;
- approvazione e rifiuto admin, inclusa la protezione dalle doppie approvazioni;
- classifica derivata da `starting_score` e schede approvate;
- sola lettura delle schede dei colleghi.

Non vengono creati test separati per getter Eloquent, markup statico o dettagli del framework.

## Distribuzione

L'applicazione viene pubblicata su un sottodominio aziendale con PHP, MySQL, HTTPS e scheduler/strumenti Laravel già disponibili. La configurazione richiede credenziali OAuth Google, dominio Workspace, email admin, connessione MySQL e timezone.

Non sono necessari processi queue, Redis o server WebSocket. Deploy: dipendenze Composer, build degli asset, migrazioni, cache Laravel e configurazione del callback OAuth sul sottodominio definitivo.

## Criteri di accettazione

Il progetto è pronto quando:

1. un dipendente accede con Google e riceve una sola scheda stabile per il giorno;
2. nessuna cella associata a quel dipendente compare nella sua scheda;
3. righe e colonne sono distribuite usando i pesi `1–3` e le celle speciali del giorno sono presenti;
4. marcature e progresso persistono tra dispositivi e sessioni;
5. un bingo valido può essere inviato, approvato dall'admin e contato una sola volta in classifica;
6. ogni utente può vedere in sola lettura le schede giornaliere e il progresso degli altri;
7. l'admin gestisce utenti, celle e revisioni senza strumenti esterni;
8. l'esperienza visiva essenziale del bingo statico viene mantenuta.
