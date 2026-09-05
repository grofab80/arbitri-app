# UI Components

## KPI

Usare `small-box` AdminLTE.

## Box

Tutti i box delle pagine amministrative devono usare `box-primary`, così da
avere il bordo superiore blu uniforme. La dashboard è l'unica eccezione e può
usare varianti cromatiche coerenti con KPI, grafici e alert riepilogativi.

## Griglie

Usare DataTables.

Standard griglie:

- header centrati orizzontalmente e verticalmente
- testo a sinistra
- date, numeri, flag, immagini e azioni centrati

## Modali

Usare modali Bootstrap.

Le conferme distruttive e i messaggi bloccanti devono usare la modale globale
`AppDialog`.

Non usare `alert()` o `confirm()` nativi del browser.

## Pulsanti Nuovo

I pulsanti di creazione nelle pagine CRUD devono:

- stare a destra nell'header pagina
- usare classe Bootstrap/AdminLTE coerente
- mostrare icona `fa fa-plus`
- mantenere testo descrittivo, ad esempio `Nuova Squadra`

## Messaggi

Usare toast.

Se il plugin toast non e disponibile, usare `AppDialog.message()`.
