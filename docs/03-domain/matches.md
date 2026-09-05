# Matches

Le partite appartengono a una competizione e quindi a una stagione.

## Campi Principali

- `id`
- `season_id`
- `competition_id`
- `match_day`
- `difficulty_rating`
- `home_team_id`
- `away_team_id`
- `referee_id`
- `field_id`
- `match_date`
- `match_time`
- `home_goals`
- `away_goals`
- `status`
- `result_type`
- `walkover_reason`
- `notes`

## Regole

- squadra casa e squadra trasferta devono essere diverse
- la giornata e obbligatoria per tutte le competizioni
- la difficolta partita va da 1 a 5, ha default 3 ed e modificabile dalla
  modale partita
- la pagina partite filtra le select per non proporre come trasferta la squadra
  gia scelta in casa, e viceversa
- lo stadio e opzionale
- il risultato puo essere parziale o assente
- se viene indicata una rete, vanno indicate entrambe
- se viene indicato un risultato completo, il frontend imposta la partita come
  giocata salvo stato annullato
- l'orario e opzionale; la UI lavora in formato `HH:MM`, mentre il backend
  accetta anche `HH:MM:SS` proveniente dal database
- se `result_type` e a tavolino, reti e motivo sono obbligatori
- nella scelta arbitro, se lo stadio e l'arbitro sono geocodificati, viene
  mostrata la distanza indicativa in km
- la distanza arbitro-stadio e calcolata sulle coordinate geografiche e non
  rappresenta ancora il tragitto stradale
- la griglia partite puo essere filtrata per intervallo date, competizione,
  squadra e arbitro
- la griglia mostra solo i dati operativi principali; stadio e note restano
  gestiti nella modale di dettaglio
- la griglia mostra la difficolta partita con rating a stelle
- l'orario in griglia viene mostrato in formato `HH:MM`
- la difficolta partita verra usata dal modulo designazioni per confrontarla
  con il rating arbitro
