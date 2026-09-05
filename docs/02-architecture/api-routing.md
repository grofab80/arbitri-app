# API Routing

Le rotte sono definite in `api/v1/routes.php`.

Ogni rotta specifica:

- metodo e path
- handler controller/metodo
- flag `auth`
- eventuali `roles`
- eventuali `permissions`

Esempio:

```php
'POST /teams' => [
    'handler' => [TeamController::class, 'store'],
    'auth' => true,
    'roles' => ['admin'],
    'permissions' => ['teams.create']
]
```

Durante la transizione RBAC, `roles` resta supportato per compatibilita.

Se una route dichiara sia `roles` sia `permissions`, il router verifica entrambi
i vincoli.

I controlli granulari usano codici permesso stabili, ad esempio `teams.create`.
