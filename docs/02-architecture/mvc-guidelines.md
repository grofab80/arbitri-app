# MVC Guidelines

## Controller

I controller devono:

- leggere request/query string
- chiamare validator
- chiamare service/model
- restituire response JSON

I controller non devono:

- contenere SQL
- contenere business logic complessa
- validare manualmente molti campi se esiste un validator

## Model

I model devono:

- usare prepared statements
- incapsulare query e transazioni
- restituire array o valori semplici

## Validator

I validator devono:

- validare campi obbligatori
- validare formati e valori consentiti
- restituire array di errori

## Service

I service devono:

- coordinare regole di business complesse
- orchestrare piu model
- evitare duplicazioni tra controller
