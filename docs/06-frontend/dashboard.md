# Dashboard

File: `admin/dashboard.php`

## Obiettivo

Mostrare KPI economici e sportivi della stagione corrente.

## KPI

- entrate
- uscite
- utile
- competizioni
- squadre iscritte
- partite gestite nelle designazioni
- partite da designare
- proposte designazione
- designazioni confermate
- designazioni manuali

## Grafici

- andamento economico mensile
- entrate per categoria
- uscite per categoria

## Tabelle

- saldo per competizione con entrate, uscite e saldo
- arbitri piu utilizzati con totale designazioni, confermate e proposte
- alert operativi su dati mancanti o azioni richieste

## Alert Operativi

Gli alert sono sempre visibili, anche quando il conteggio e 0.

Elenco:

- stagioni da chiudere
- partite programmate senza arbitro
- squadre senza stadio
- arbitri senza indirizzo
- stadi senza coordinate
- competizioni senza squadre

Gli alert sono cliccabili quando esiste una pagina coerente. Il link include un
parametro `alert` e la pagina di destinazione applica un filtro client-side dove
possibile.

Graficamente ogni alert usa icona, accento colore per severita, badge numerico
e stato attenuato quando il conteggio e 0.

## Layout

- Riga 1: KPI Entrate, Uscite, Utile, Competizioni, Squadre iscritte
- Riga 2: KPI Partite gestite, Da designare, Proposte, Confermate, Manuali
- Riga 3: grafico Andamento economico mensile e Alert operativi
- Riga 4: Saldo per competizione e Arbitri piu utilizzati
- Riga 5: grafici Entrate per categoria e Uscite per categoria

## API Usate

- `GET /dashboard-kpi`
- `GET /dashboard-charts`

## Note UI

La dashboard usa `small-box` AdminLTE per i KPI e Chart.js per i grafici.

Il precedente blocco `Indicatori stagione` e stato rimosso per evitare
duplicazioni rispetto ai KPI principali e liberare spazio per widget piu
operativi.
