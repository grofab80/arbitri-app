# Balance Page

File:

- `admin/balance.php`

## Permesso

- `balance.view`

## Funzioni

- KPI entrate, uscite e saldo sempre visibili sopra i tab
- header con stagione corrente e origine dati `Da prima nota`
- filtri compatti per periodo dentro la stagione corrente
- filtro competizione
- filtro squadra
- filtro arbitro
- riepilogo per categoria
- riepilogo per competizione
- andamento mensile
- tab `Confronto stagioni` con selezione stagione base/confronto, grafico,
  riepilogo, confronto per categorie e confronto per competizioni
- badge stato nel confronto stagioni: `Nuovo`, `In corso`, `Bozza`,
  `Approvato`, `Storicizzato`
- badge fonte nel confronto stagioni: `Prima nota` o `Snapshot`
- evidenza verde/rossa per saldi e variazioni positive/negative nel confronto
- tabelle del confronto ordinate per maggiore variazione assoluta del saldo
- approvazione dello snapshot della stagione base chiusa con `balance.approve`
- storicizzazione dei movimenti della stagione base approvata con
  `balance.archive`
- esportazione CSV nella barra filtri con `balance.export`

## Endpoint Usato

- `GET /api/v1/balance`
- `GET /api/v1/balance-season-comparison`
- `GET /api/v1/balance-season-analysis`
- `PUT /api/v1/balance-closure-approve`
- `PUT /api/v1/balance-closure-archive`
- `GET /api/v1/balance-export`

I filtri `from`, `to`, `competition_id`, `team_id` e `referee_id` vengono
applicati sia alla visualizzazione sia all'esportazione CSV.

Il tab `Confronto stagioni` e indipendente dai filtri della stagione corrente.
Permette di scegliere una stagione base e una stagione di confronto. Le tabelle
mostrano la variazione di saldo complessiva, per categoria/sottocategoria e per
competizione.

Il pulsante di approvazione usa una modale di conferma e viene abilitato solo
per una stagione base chiusa con snapshot non ancora approvato.

Il pulsante di storicizzazione usa una modale di conferma e viene abilitato
solo per una stagione base chiusa, approvata e non ancora storicizzata.

I KPI restano fuori dai tab per mantenere sempre visibile la sintesi economica
della stagione corrente.

L'header mostra la stagione corrente usando i dati restituiti da
`GET /api/v1/balance`.

## Regola UI

La voce Bilancio e raggruppata nella sidebar sotto `Segreteria`, insieme a
`Prima Nota`.
