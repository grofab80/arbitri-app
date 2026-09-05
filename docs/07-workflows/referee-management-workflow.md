# Referee Management Workflow

1. Admin apre arbitri.
2. Crea o modifica arbitro.
3. Compila eventuale residenza.
4. Seleziona abilitazioni 11, 7 e/o 5.
5. Imposta rating da 1 a 5.
6. Backend richiede almeno una abilitazione e rating valido.

## Disponibilita

1. Utente apre la pagina `Arbitri`.
2. Clicca il pulsante calendario sulla riga dell'arbitro.
3. Frontend carica `GET /referee-availabilities?referee_id=...`.
4. Utente gestisce disponibilita ricorrenti o date specifiche.
5. Frontend salva tramite `POST` o `PUT /referee-availabilities`.
6. Le cancellazioni richiedono conferma modale e usano
   `DELETE /referee-availabilities`.

La disponibilita viene usata dal motore designazioni come vincolo bloccante
per le proposte automatiche quando la partita ha data e ora valorizzate.

## Smoke Test

Lo script `tests/smoke/referee_availabilities_smoke.php` verifica:

- validazione disponibilita
- disponibilita ricorrente
- indisponibilita puntuale che sovrascrive la ricorrenza
- generazione proposta che rispetta lo slot disponibile
- payload disponibilita usato dal frontend designazioni

## Geocoding

La residenza viene salvata come testo strutturato.

Il servizio OpenStreetMap/Nominatim valorizza coordinate e data geocodifica
tramite azione manuale.

1. Utente salva l'arbitro con indirizzo residenza.
2. Utente riapre/modifica l'arbitro.
3. Clicca "Verifica indirizzo".
4. Backend usa l'indirizzo salvato e chiama Nominatim.
5. Se viene trovato un risultato, backend salva coordinate e timestamp.
