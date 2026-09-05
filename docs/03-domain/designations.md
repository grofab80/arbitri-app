# Designations

Le designazioni arbitrali assegnano un arbitro a una partita.

Il sistema dovra proporre una designazione, ma l'utente potra sempre modificarla
manualmente scegliendo qualsiasi arbitro abilitato per la stessa disciplina.

## Ambito

Le designazioni vengono gestite per:

- disciplina: calcio a 5, calcio a 7, calcio a 11
- competizione
- giornata

## Entita

### `matches.difficulty_rating`

Rating/difficolta della partita.

Regole:

- valore da 1 a 5
- default `3`
- sara usato per confrontare difficolta partita e rating arbitro
- e modificabile dalla modale partita

### `designations`

Designazione associata a una partita.

Campi principali:

- `match_id`
- `referee_id`
- `status`: `proposta`, `confermata`, `modificata`
- `assignment_type`: `auto`, `manual`
- `score`
- `score_details_json`
- `notes`
- `created_by`
- `updated_by`

Regole:

- una partita puo avere al massimo una designazione attiva nella tabella
  corrente
- la designazione puo nascere da proposta automatica o modifica manuale
- lo score e informativo e non blocca la modifica manuale

### `referee_team_blacklist`

Blacklist manuale tra arbitro e squadra.

Campi principali:

- `referee_id`
- `team_id`
- `reason`
- `active`

Regole:

- una coppia arbitro/squadra puo essere presente una sola volta
- solo le righe attive saranno considerate dal motore proposta
- la rimozione da frontend disattiva la riga, non elimina lo storico tecnico

## Regole Di Proposta

Regole bloccanti per la proposta automatica:

- arbitro abilitato alla disciplina della competizione
- arbitro disponibile per giorno e orario partita, se la partita ha un orario
  valorizzato
- arbitro non in blacklist con squadra casa o trasferta
- arbitro non gia designato su altra partita dello stesso giorno entro la
  finestra minima configurata

Regole di ranking:

- rating arbitro vicino alla difficolta partita
- distanza tra residenza arbitro e stadio
- distribuire il carico tra arbitri
- penalizzare due partite consecutive con la stessa squadra
- penalizzare ulteriormente due partite consecutive con la stessa squadra in
  casa

Implementazione corrente:

- la proposta automatica e disponibile tramite `POST /designations/generate`
- la proposta non sovrascrive designazioni manuali o confermate
- le proposte assenti o gia automatiche vengono create/aggiornate
- lo score e salvato su `designations.score`
- i dettagli del calcolo sono salvati su `designations.score_details_json`
- la pagina mostra score e motivazioni principali della proposta
- pesi e vincoli principali sono configurati in `config/designations.php`
- lo score penalizza arbitri gia molto designati nella stagione corrente
- il conflitto consecutivo sulla stessa squadra non blocca la proposta, ma
  abbassa fortemente lo score
- la finestra minima tra due partite dello stesso arbitro nello stesso giorno e
  attualmente 120 minuti
- se una partita non ha orario, qualsiasi altra designazione dello stesso
  arbitro nello stesso giorno viene considerata conflitto
- la tabella `referee_availabilities` viene usata dal motore proposta per
  escludere arbitri non disponibili nello slot partita
- se una partita non ha orario, il controllo disponibilita viene saltato per
  mancanza dello slot temporale

## Configurazione Score

File:

- `config/designations.php`

Parametri:

- `score.base`: score iniziale
- `score.rating_gap_penalty`: penalita per ogni punto di differenza tra
  difficolta partita e rating arbitro
- `score.assignment_load_penalty`: penalita per ogni designazione stagionale
  gia presente sull'arbitro
- `score.assignment_load_max_penalty`: tetto massimo penalita carico
- `score.consecutive_team_penalty`: penalita per stessa squadra consecutiva
- `score.consecutive_home_team_penalty`: penalita aggiuntiva per stessa squadra
  in casa consecutiva
- `score.distance_km_divisor`: divisore per trasformare km in penalita
- `score.distance_max_penalty`: tetto massimo penalita distanza
- `constraints.minimum_minutes_between_matches`: finestra minima tra due
  partite dello stesso arbitro nello stesso giorno

La configurazione e versionata nel codice. Una futura evoluzione potra spostare
questi valori su database e renderli modificabili da frontend.

## Modifica Manuale

La modifica manuale deve restare sempre possibile.

Regola:

- l'utente puo selezionare qualsiasi arbitro abilitato alla disciplina, anche se
  non rispetta tutti i criteri usati dalla proposta automatica
- la disponibilita non blocca la modifica manuale: l'utente puo comunque
  selezionare un arbitro abilitato alla disciplina
- il frontend evidenzia nella select se un arbitro non e disponibile nello slot
  della partita e richiede conferma prima del salvataggio manuale

La blacklist resta un vincolo della proposta automatica; una fase successiva
definira se e quando consentire override manuale esplicito.

Implementazione corrente:

- la pagina `Designazioni` permette il filtro per disciplina, competizione e
  giornata
- il salvataggio manuale crea o aggiorna la riga in `designations`
- il salvataggio manuale aggiorna anche `matches.referee_id`
- gli arbitri selezionabili sono filtrati per abilitazione alla disciplina
- la proposta automatica puo essere rigenerata dai filtri della pagina
- la designazione puo essere confermata da frontend con permesso
  `designations.confirm`
- una designazione confermata puo ancora essere modificata manualmente da chi
  possiede `designations.edit`
- la blacklist arbitro/squadra puo essere gestita dal tab `Blacklist` della
  pagina con permesso `designations.blacklist.manage`
- la pagina espone un riepilogo con partite totali, da designare, proposte,
  confermate, modificate manualmente e arbitri piu utilizzati
- le azioni massive consentono rigenerazione delle proposte non confermate,
  conferma delle proposte filtrate e pulizia delle proposte automatiche non
  confermate

## Permessi

- `designations.view`: visualizzazione pagina designazioni
- `designations.generate`: generazione proposta automatica
- `designations.edit`: modifica manuale designazione
- `designations.confirm`: conferma designazione
- `designations.blacklist.manage`: gestione blacklist arbitro/squadra
