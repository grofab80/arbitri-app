# Financial Categories

Le categorie finanziarie classificano i movimenti di Prima Nota e alimentano il
Bilancio.

## Obiettivo

La nuova tassonomia sostituisce la struttura legacy del database `asdaca`, dove
le categorie erano organizzate come:

- tipologia
- categoria
- sottocategoria 1
- sottocategoria 2

Nel modello attuale di `arbitri-app` la struttura resta a quattro livelli:

```text
Entrate/Uscite
  Area contabile
    Voce
      Dettaglio
```

Solo il livello 4 deve essere usato nei movimenti.

## Regole

- `Entrate` e `Uscite` sono i soli livelli 1.
- Le entita operative come competizione, squadra e arbitro non devono essere
  duplicate nel nome categoria quando possono essere gestite da relazione.
- I flag `allow_competition`, `allow_team` e `allow_referee` dichiarano quali
  riferimenti possono essere richiesti nel movimento.
- La voce `Altro` deve essere usata solo come eccezione e con descrizione
  obbligatoriamente chiara.

## Struttura Proposta

### Entrate

```text
Entrate
  Quote e iscrizioni
    Iscrizioni competizioni
      Iscrizione campionato
      Iscrizione torneo
  Tesseramenti
    Tessere atleti
      Tessera annuale
  Sponsorizzazioni e pubblicita
    Sponsor
      Sponsor associazione
    Pubblicita
      Materiale pubblicitario
  Eventi e tornei
    Incassi evento
      Torneo
      Festa / evento
      Altro evento
```

### Uscite

```text
Uscite
  Arbitri e ufficiali gara
    Compensi arbitri
      Arbitro centrale
      Assistente
      Terna
    Osservatori
      Osservatore
    Rimborsi arbitri
      Rimborso spese
      Chilometraggio
  Costi competizioni
    Premi e coppe
      Coppe
      Premi
      Coppa disciplina
    Materiale gara
      Palloni
      Referti
      Attrezzatura sportiva
  Strutture e logistica
    Stadi e campi
      Affitto campo
      Noleggio struttura
    Eventi
      Cena arbitri
      Premiazione
      Bar / ristoro
    Trasporti
      Benzina
      Corrieri
      Noleggio mezzo
  Costi associazione
    Affiliazioni
      ACSI
      Affiliazione federativa
    Sede e segreteria
      Cancelleria
      Segreteria
      Manutenzione
      Donazioni
      Altro sede
    Sito e software
      Dominio
      Hosting / gestione sito
      Plugin / servizi digitali
      Canva / software
  Tesseramenti passivi
    Tessere
      Tessere ACSI / ente
  Staff e collaboratori
    Rimborsi staff
      Staff
      Collaboratori
```

## Mapping Legacy `asdaca`

| Legacy | Nuova categoria |
|---|---|
| `ENTRATE > CAMPIONATI > ISCRIZIONI > ISCRIZIONI` | `Entrate > Quote e iscrizioni > Iscrizioni competizioni > Iscrizione campionato` |
| `ENTRATE > CAMPIONATI > TESSERE > TESSERE` | `Entrate > Tesseramenti > Tessere atleti > Tessera annuale` |
| `ENTRATE > ACA > PUBBLICITA > PUBBLICITA` | `Entrate > Sponsorizzazioni e pubblicita > Pubblicita > Materiale pubblicitario` |
| `ENTRATE > ACA > SPONSOR > SPONSOR` | `Entrate > Sponsorizzazioni e pubblicita > Sponsor > Sponsor associazione` |
| `ENTRATE > TORNEI > ALTRO > ALTRO` | `Entrate > Eventi e tornei > Incassi evento > Torneo` |
| `USCITE > CAMPIONATI > ARBITRI > ARBITRI` | `Uscite > Arbitri e ufficiali gara > Compensi arbitri > Arbitro centrale` |
| `USCITE > CAMPIONATI > ASSISTENTI > ASSISTENTI` | `Uscite > Arbitri e ufficiali gara > Compensi arbitri > Assistente` |
| `USCITE > CAMPIONATI > OSSERVATORI > OSSERVATORI` | `Uscite > Arbitri e ufficiali gara > Osservatori > Osservatore` |
| `USCITE > RIMBORSI > STAFF > STAFF` | `Uscite > Staff e collaboratori > Rimborsi staff > Staff` |
| `USCITE > ACA > AFFILIAZIONE > ACSI` | `Uscite > Costi associazione > Affiliazioni > ACSI` |
| `USCITE > ACA > SITO > DOMINIO` | `Uscite > Costi associazione > Sito e software > Dominio` |
| `USCITE > ACA > SITO > GESTIONE` | `Uscite > Costi associazione > Sito e software > Hosting / gestione sito` |
| `USCITE > ACA > SITO > PLUGIN` | `Uscite > Costi associazione > Sito e software > Plugin / servizi digitali` |
| `USCITE > ACA > SEDE > ATTREZZATURA` | Da valutare tra `Materiale gara > Attrezzatura sportiva` e `Sede e segreteria > Cancelleria` in base alla descrizione |
| `USCITE > ACA > SEDE > AFFITTO` | `Uscite > Strutture e logistica > Stadi e campi > Affitto campo` |
| `USCITE > ACA > SEDE > CENE` | `Uscite > Strutture e logistica > Eventi > Cena arbitri` |
| `USCITE > ACA > SEDE > MAGLIE ARBITRI` | `Uscite > Costi competizioni > Materiale gara > Attrezzatura sportiva` |
| `USCITE > ACA > SEDE > SEGRETERIA` | `Uscite > Costi associazione > Sede e segreteria > Segreteria` |
| `USCITE > ACA > SEDE > BENZINA` | `Uscite > Strutture e logistica > Trasporti > Benzina` |
| `USCITE > ACA > SEDE > PREMI` | `Uscite > Strutture e logistica > Eventi > Premiazione` |
| `USCITE > ACA > TESSERE > TESSERE` | `Uscite > Tesseramenti passivi > Tessere > Tessere ACSI / ente` |

## Migration

La migration:

- `database/migrations/2026_07_13_rebuild_financial_categories.sql`

prepara il database locale prima dell'import storico da `asdaca`.

Esegue queste operazioni:

- elimina dati stagionali/sportivi/contabili esistenti:
  `balance_closures`, `competition_standings`, `matches`, `movements`,
  `competition_teams`, `teams`, `competitions`, `seasons`, `categories`
- preserva utenti, profili, permessi, arbitri e stadi
- ricrea solo la stagione `2025/2026`
- imposta la stagione `2025/2026` come `in_corso`
- ricostruisce la tassonomia finanziaria nuova

Al termine dello script deve esistere una sola stagione:

```text
2025/2026 - in_corso
```

Questa migration e distruttiva e deve essere usata solo in locale o in un
ambiente preparato all'import.

## Seed Sportivo 2025/2026

Dopo il reset della stagione e delle categorie puo essere eseguito:

- `database/migrations/2026_07_13_seed_2025_2026_competitions_teams.sql`

Lo script crea dati di esempio ispirati al database legacy `asdaca`:

- campionati di calcio a 11
- campionati di calcio a 7
- campionati di calcio a 5
- tornei
- squadre collegate tramite `competition_teams`

Ordine consigliato in locale:

1. `2026_07_13_rebuild_financial_categories.sql`
2. `2026_07_13_seed_2025_2026_competitions_teams.sql`
3. `2026_07_13_seed_2025_2026_movements.sql` per dati demo di test
4. import storico dei movimenti da `asdaca`, se serve sostituire i dati demo
