# Authentication And Authorization

L'autenticazione usa JWT.

Il token viene generato al login e usato dal frontend per chiamare le API protette.

## Ruoli

- `admin`: accesso completo
- `user`: accesso operativo limitato

## Permessi

La Fase 1 RBAC introduce profili e permessi configurabili da database.

Componenti coinvolti:

- `User::permissions()` legge i permessi attivi dell'utente
- `User::hasPermissions()` verifica un set di permessi richiesti
- `JwtMiddleware::authorizePermissions()` prepara il controllo backend
- `GET /api/v1/me` restituisce identita e permessi aggiornati

## Regola

Il controllo dei permessi deve stare sempre nel backend. La logica frontend puo
nascondere pagine o pulsanti, ma non e una protezione sufficiente.

Durante la transizione le route possono usare ancora `roles`; le nuove
autorizzazioni granulari useranno codici `permissions`.
