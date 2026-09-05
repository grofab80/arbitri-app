# Frontend Flow

Le pagine admin sono PHP e caricano JS dedicato.

## Pattern Pagina

```text
admin/module.php
  -> include topbar/sidebar
  -> markup tabella/modale
  -> script js/module.js

admin/js/module.js
  -> load data da API
  -> inizializza DataTables
  -> gestisce modale
  -> invia payload JSON
  -> mostra toast/errori
```

La business logic critica deve sempre stare nel backend.
