# Folder Structure

```text
admin/
  css/
  dist/
  images/
  includes/
  js/
  plugins/
  *.php

api/
  Controllers/
  Core/
  Http/
  Middleware/
  Models/
  Services/
  Validators/
  v1/

config/
database/
  migrations/
docs/

integrations/
  wordpress/
    football-sync-api/
      includes/
      football-sync-api.php
```

## Convenzioni

- una pagina admin deve avere un file PHP e, se dinamica, un file JS dedicato
- ogni modulo API deve avere controller, model e validator se modifica dati
- le migrazioni devono essere additive quando possibile
- le integrazioni distribuibili a sistemi esterni devono restare isolate sotto
  `integrations/` e non dipendere dal bootstrap di `arbitri-app`
