# Designations Page

File:

- `admin/designations.php`
- `admin/js/designations.js`

## Funzioni

- filtro per disciplina
- filtro per competizione
- filtro per giornata
- blocco KPI compatto affiancato alla tabella arbitri piu utilizzati
- tabella arbitri piu utilizzati
- lista partite della stagione corrente
- visualizzazione difficolta partita
- visualizzazione arbitro assegnato e stato designazione
- visualizzazione score proposta e dettaglio motivazioni
- generazione proposta automatica per i filtri selezionati
- rigenerazione proposte filtrate non confermate
- conferma massiva proposte filtrate
- pulizia proposte automatiche filtrate non confermate
- modifica manuale arbitro per singola partita
- conferma designazione per singola partita
- evidenza disponibilita arbitro nella select di modifica manuale
- tab `Designazioni` con filtri, KPI, azioni massive e griglia partite
- tab `Blacklist` con gestione blacklist arbitro/squadra

## Regole UI

- la pagina e visibile con `designations.view`
- il tab `Designazioni` e il tab principale della pagina
- il tab `Blacklist` separa la gestione blacklist dalla griglia designazioni
- i KPI e la tabella arbitri piu utilizzati sono affiancati in un'unica riga
  per ridurre altezza della pagina
- il cambio disciplina aggiorna le competizioni disponibili
- la select arbitro mostra solo arbitri abilitati alla disciplina selezionata
- la select arbitro indica se l'arbitro e disponibile, non disponibile o se la
  disponibilita non e calcolabile per mancanza dello slot partita
- il salvataggio manuale di un arbitro non disponibile richiede conferma ma non
  viene bloccato
- il salvataggio e disponibile solo con `designations.edit`
- la generazione proposta e disponibile solo con `designations.generate`
- la generazione richiede conferma tramite modale
- la pulizia proposte automatiche e disponibile solo con `designations.generate`
- la conferma massiva e disponibile solo con `designations.confirm`
- la conferma designazione e disponibile solo con `designations.confirm`
- la conferma richiede conferma tramite modale
- il tab blacklist e visibile solo con `designations.blacklist.manage`
- la rimozione blacklist richiede conferma tramite modale
- lo stato salvato manualmente viene mostrato come `Modificata`
- le proposte automatiche vengono mostrate come `Proposta`
- le designazioni confermate vengono mostrate come `Confermata`
- lo score mostra un pulsante informativo quando sono disponibili i dettagli
  tecnici della proposta
- il dettaglio score mostra rating, distanza, carico stagionale e penalita per
  conflitti consecutivi
- il dettaglio score mostra anche la finestra minima oraria usata dal motore
- il dettaglio score mostra i principali pesi configurati usati dal calcolo
- i KPI seguono gli stessi filtri della griglia designazioni

## Sidebar

La pagina e nel gruppo `Sport`, insieme a partite, competizioni, squadre,
arbitri e stadi.

## Prossimi Step

- affinamento pesi score e gestione calendario arbitro su finestre orarie piu
  evolute
