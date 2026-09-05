# Permissions Management Workflow

1. Utente accede alla pagina Permessi.
2. Frontend verifica `permissions.view`.
3. Frontend carica profili, permessi e assegnazioni da `/api/v1/permissions`.
4. Utente seleziona un profilo.
5. La matrice mostra i permessi assegnati.
6. Se l'utente possiede `permissions.manage`, puo modificare le checkbox.
7. Il salvataggio invia `PUT /api/v1/profile-permissions`.
8. Backend valida profilo e permessi.
9. Backend sostituisce le assegnazioni del profilo in transazione.
10. Frontend ricarica la matrice aggiornata.

## Regole

- Il profilo `admin` non viene modificato.
- `admin` resta sempre superprofilo applicativo.
- I permessi `*.delete` restano riservati ad admin.
- La UI li mostra disabilitati sui profili non admin.
- Il backend rifiuta comunque eventuali payload che provino ad assegnarli.
- Il frontend migliora l'esperienza, ma il controllo reale resta negli endpoint.
