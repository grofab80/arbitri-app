# Error Handling

## Formato Success

```json
{
  "success": true,
  "data": {}
}
```

## Formato Error

```json
{
  "success": false,
  "error": "Messaggio",
  "errors": {
    "field": "Errore campo"
  }
}
```

## Regole

- usare `Response::ok` per successi
- usare `Response::error` per errori noti
- non fare echo manuali nei controller
