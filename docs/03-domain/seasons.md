# Seasons

La stagione rappresenta il periodo sportivo/amministrativo.

## Campi Principali

- `id`
- `name`
- `starts_on`
- `ends_on`
- `status`
- `is_current`
- `created_at`

## Regole

- gli stati possibili sono `nuovo`, `in_corso`, `chiuso`
- puo esistere una sola stagione `in_corso`
- la stagione `in_corso` viene usata per filtrare movimenti e dashboard
- una competizione appartiene a una stagione
- `is_current` e mantenuto per compatibilita e viene sincronizzato con `status = in_corso`
- quando una stagione passa a `chiuso`, viene generato o aggiornato lo snapshot
  ufficiale del bilancio

## Transizioni

- `nuovo` -> `in_corso`
- `nuovo` -> `chiuso`
- `in_corso` -> `chiuso`

Quando una stagione passa a `in_corso`, eventuali altre stagioni in corso vengono chiuse.
La chiusura automatica di una stagione genera anche la relativa chiusura bilancio.
