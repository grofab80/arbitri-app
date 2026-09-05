# Role Permission Workflow

1. Utente effettua login.
2. JWT contiene ruolo, dati utente e permessi disponibili come bootstrap.
3. Il frontend rinfresca identita e permessi tramite `/api/v1/me`.
4. Le pagine sono filtrate tramite permessi `*.view`.
5. Le azioni dispositive sono abilitate tramite permessi granulari.
6. Backend verifica l'autorizzazione sulle route protette tramite `permissions`.

## Stato Corrente

La Fase 1 ha introdotto il catalogo permessi e la lettura backend.

La Fase 2 ha introdotto filtro sidebar e guard frontend sulle pagine.

La Fase 3 ha introdotto il controllo backend `permissions` sulle route.

La Fase 4 ha introdotto i controlli frontend granulari sulle azioni CRUD e sui
cambi stato.

La Fase 5 ha introdotto la pagina frontend di configurazione permessi e gli
endpoint di assegnazione permessi ai profili.

La Fase 6 ha introdotto la pagina utenti per assegnare profili RBAC agli utenti.

Le route applicative possono mantenere ancora `roles` per compatibilita. Quando
`roles` e `permissions` sono entrambi presenti, vengono verificati entrambi.
