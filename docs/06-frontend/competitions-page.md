# Competitions Page

File:

- `admin/competitions.php`
- `admin/js/competitions.js`

## Funzioni

- lista competizioni
- creazione/modifica/eliminazione
- relazione con stagione
- tipo e calcio
- classifica come attributo della competizione per i campionati

## Classifica

Non e prevista una pagina `classifiche` separata.

La classifica verra gestita dalla pagina Competizioni tramite azione sulla riga
del campionato.

La modifica richiedera:

- `competitions.standings.manage`

## Stato Implementazione

Backend classifiche disponibile.

La pagina Competizioni mostra il pulsante `Classifica` sulle righe di tipo
`campionato`.

La modale classifica:

- mostra le squadre collegate alla competizione
- permette lettura con `competitions.view`
- abilita la modifica solo con `competitions.standings.manage`
- permette di indicare penalizzazioni nella colonna `Pen`
- salva tramite `PUT /api/v1/competition-standings`
- permette il ricalcolo da partite giocate tramite
  `PUT /api/v1/competition-standings-recalculate`
- mantiene le penalizzazioni durante il ricalcolo
