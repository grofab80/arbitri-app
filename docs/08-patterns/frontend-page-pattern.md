# Frontend Page Pattern

Per una nuova pagina CRUD:

```text
admin/module.php
admin/js/module.js
api/Controllers/ModuleController.php
api/Models/Module.php
api/Validators/ModuleValidator.php
docs/...
```

## Regole

- modale per form
- DataTables per griglia
- rispettare lo standard di allineamento colonne DataTables
- toast per feedback
- `AppDialog.confirm()` per conferme distruttive
- `AppDialog.message()` per messaggi bloccanti
- pulsante `Nuovo...` a destra con icona `fa fa-plus`
- box di pagina con classe `box-primary`; fanno eccezione i box della dashboard
- validazione backend obbligatoria

Non usare `alert()` o `confirm()` nativi del browser.
