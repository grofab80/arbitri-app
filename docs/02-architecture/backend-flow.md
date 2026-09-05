# Backend Flow

## Request API

```text
HTTP request
  -> api/v1/router.php
  -> api/v1/routes.php
  -> JwtMiddleware
  -> Controller
  -> Request::json()
  -> Validator
  -> Model/Service
  -> Response::ok() / Response::error()
```

## Error Handling

Il router intercetta eccezioni non gestite e restituisce errore JSON.

Le validazioni devono restituire HTTP `422`.

Le risorse mancanti devono restituire HTTP `404`.

Le operazioni non consentite devono restituire HTTP `403`.
