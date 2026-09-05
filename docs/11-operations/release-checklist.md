# Release Checklist

- versione aggiornata in `VERSION` e `CHANGELOG.md`
- nessun segreto o dump incluso nei file da versionare
- dipendenze installabili da `composer.lock`
- `composer audit` senza advisory aperti
- lint PHP
- lint JS
- migrazioni applicate
- baseline e seed di riferimento importabili su database vuoto
- migration ledger aggiornato
- login admin verificato
- ruoli verificati
- CRUD principali verificati
- dashboard verificata
- documentazione aggiornata
