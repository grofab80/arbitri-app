# Designation Management Workflow

## Consultazione

1. Utente apre la pagina `Designazioni`.
2. Frontend verifica `designations.view`.
3. Utente seleziona disciplina, competizione e giornata.
4. Backend restituisce le partite della giornata con eventuale designazione.

Stato corrente:

- implementato endpoint `GET /designations`
- implementato endpoint `GET /designations/summary`
- implementata pagina frontend `admin/designations.php`
- implementati filtri disciplina, competizione e giornata
- implementati KPI: partite totali, da designare, proposte, confermate,
  modificate manualmente e arbitri piu utilizzati

## Generazione Proposta

1. Utente con `designations.generate` richiede la proposta.
2. Backend seleziona le partite filtrate.
3. Backend individua gli arbitri abilitati alla disciplina.
4. Backend esclude gli arbitri non disponibili per giorno e orario partita,
   quando la partita ha un orario valorizzato.
5. Backend esclude gli arbitri in blacklist con squadra casa o trasferta.
6. Backend calcola uno score per ogni candidato.
7. Backend salva la proposta in `designations` con stato `proposta`.
8. Frontend mostra arbitro proposto e motivazioni principali.

Stato corrente:

- implementato endpoint `POST /designations/generate`
- implementato pulsante `Genera proposta` nella pagina designazioni
- la generazione richiede conferma modale
- il backend salva score e dettagli tecnici dello score
- la pagina mostra arbitro proposto, stato, score e dettaglio motivazioni
  principali
- lo score include rating, distanza, carico stagionale e conflitti consecutivi
- il controllo orario blocca arbitri con partite nello stesso giorno troppo
  ravvicinate
- i pesi score sono letti da `config/designations.php`
- la tabella `referee_availabilities` viene applicata alla generazione
  automatica come vincolo bloccante
- in assenza di righe disponibili nello slot partita, l'arbitro non viene
  proposto automaticamente

## Modifica Manuale

1. Utente con `designations.edit` modifica una designazione.
2. Frontend propone tutti gli arbitri abilitati alla stessa disciplina.
3. Utente seleziona l'arbitro desiderato.
4. Backend salva `assignment_type = manual`.
5. Backend imposta stato `modificata`.

Stato corrente:

- implementato endpoint `PUT /designations`
- il salvataggio mantiene allineato anche `matches.referee_id`

## Conferma

1. Utente con `designations.confirm` conferma una proposta o modifica.
2. Backend imposta stato `confermata`.
3. La conferma non impedisce modifiche successive se l'utente possiede
   `designations.edit`.

Stato corrente:

- implementato endpoint `POST /designations/confirm`
- implementato pulsante conferma nella griglia designazioni
- la conferma richiede una modale
- la conferma e disponibile solo quando esiste un arbitro assegnato

## Azioni Massive

Azioni disponibili dalla pagina designazioni:

- rigenera proposte non confermate: aggiorna proposte automatiche e crea quelle
  mancanti senza toccare manuali o confermate
- conferma proposte filtrate: conferma tutte le proposte filtrate con arbitro
  assegnato
- pulisci proposte automatiche: elimina le proposte automatiche non confermate
  senza toccare manuali o confermate

Ogni azione richiede conferma modale.

## Blacklist

1. Utente apre il tab `Blacklist` nella pagina `Designazioni`.
2. Utente con `designations.blacklist.manage` gestisce coppie arbitro/squadra.
3. Backend salva motivo e stato attivo.
4. Il motore proposta ignora le righe non attive.
5. Le righe attive escludono l'arbitro per partite della squadra indicata.

Stato corrente:

- implementati endpoint `GET`, `POST` e `DELETE /designation-blacklist`
- implementato tab frontend dedicato nella pagina designazioni
- la rimozione disattiva la regola impostando `active = 0`
- la proposta automatica esclude le righe attive

## Smoke Test

Lo script `tests/smoke/designations_smoke.php` verifica:

- modifica manuale
- conferma
- blacklist
- generazione proposta
- conflitto orario
- azioni massive

Il test lavora dentro una transazione e termina con rollback.
