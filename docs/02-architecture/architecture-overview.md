# Architecture Overview

L'applicazione segue un'impostazione MVC custom.

## Livelli

- Controller: gestiscono request, response e orchestrazione minima
- Model: gestiscono accesso al database
- Validator: validano payload e input
- Service: contengono business logic condivisa o complessa
- Frontend JS: gestisce interazione pagina/API, senza business logic critica

## Flusso Generale

```text
Browser
  -> admin/*.php
  -> admin/js/*.js
  -> api/v1/router.php
  -> Middleware JWT
  -> Controller
  -> Validator
  -> Model/Service
  -> Response JSON
```
