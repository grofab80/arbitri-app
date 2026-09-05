# Roadmap

## Fase 1: Stabilizzazione

- completare migrazioni DB
- consolidare CRUD anagrafiche
- completare dashboard con KPI e grafici
- uniformare UI AdminLTE
- mantenere controller sottili

## Fase 2: Refactoring Architetturale

- spostare business logic in service dedicati
- introdurre test automatici
- migliorare gestione errori lato frontend
- documentare tutti gli endpoint

## Fase 3: Evoluzione

- gestione designazioni arbitri
- uso rating arbitro per supportare le designazioni
- ordinamento/suggerimento arbitri nelle partite in base al rating
- filtri avanzati per stagione e competizione
- report economici
- bilancio di stagione derivato dai movimenti
- import/export dati
- audit log

## Integrazione WordPress / AnWP

- Fase 1 completata: analisi schema reale, contratto API e mapping
- Fase 2 completata e validata in produzione: plugin WordPress MVP
- Fase 3 completata nel repository: sync incrementale, cache e logging chiamate
- plugin `0.2.1` pronto: riconoscimento discipline reali e contesto lega
- Fase 4 completata nel repository: importer, mapping esterno/locale, storico,
  RBAC e pagina di gestione in `arbitri-app`
- gestione assistita conflitti completata: override disciplina/stagione,
  esclusioni persistenti e conteggio record ignorati
- prossimo passo: deploy `0.2.1`, migrazione override e nuovo sync completo
- evoluzione successiva: esecuzione schedulata
