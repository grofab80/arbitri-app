# Integrazione Continua

Il workflow GitHub Actions `.github/workflows/quality.yml` verifica ogni push
su `main`, ogni pull request verso `main` e ogni esecuzione manuale.

## Controllo Obbligatorio

Il job pubblicato da GitHub si chiama `Quality checks` ed esegue:

- validazione di `composer.json` e `composer.lock`
- installazione e audit delle dipendenze PHP
- lint di tutti i file PHP applicativi
- controllo sintattico dei file JavaScript in `admin/js`
- creazione di un database MariaDB temporaneo dalla baseline
- caricamento dei dati di riferimento e dei seed di test
- esecuzione dei sette smoke test presenti in `tests/smoke`

L'ambiente CI usa PHP 8.2, Node.js 24 e MariaDB 10.11. Le credenziali e le
chiavi presenti nel workflow sono valori temporanei validi soltanto nel job e
non devono essere riutilizzati in ambienti reali.

## Protezione Di Main

Il branch `main` e la linea stabile del progetto. La configurazione GitHub deve:

- richiedere il superamento di `Quality checks` prima dell'integrazione
- richiedere che il branch sia aggiornato rispetto a `main`
- impedire force push e cancellazione del branch
- richiedere la risoluzione delle conversazioni nelle pull request
- mantenere una cronologia lineare

Il progetto ha un solo manutentore operativo, quindi non viene richiesto un
numero minimo di approvazioni. Il passaggio tramite pull request resta utile per
rendere visibili diff, controlli e motivazione dell'intervento.

### Stato Del Repository

Il workflow `Quality checks` e attivo. La protezione tecnica del branch non e
attualmente applicabile perche il repository e privato e il piano GitHub in uso
non include Branch Protection per repository privati. GitHub richiede il
passaggio a un piano che supporti la funzione oppure la visibilita pubblica.

Fino a tale modifica, pull request e superamento della CI restano regole
operative del progetto, ma GitHub non puo impedire tecnicamente push diretti,
force push o cancellazione di `main`.

## Esecuzione Locale

Prima di pubblicare un branch eseguire almeno:

```powershell
composer validate --strict
composer audit
```

Eseguire inoltre il lint PHP, `node --check` sui file in `admin/js` e gli smoke
test pertinenti. I test che usano il database devono puntare a un database di
sviluppo preparato con lo schema e i dati richiesti: aprono una transazione e
annullano le modifiche al termine.

## Modifiche Al Workflow

Ogni modifica al nome del job `Quality checks` richiede l'aggiornamento della
regola di protezione su GitHub. Nuovi test stabili devono essere aggiunti al
workflow e alla `release-checklist.md` quando diventano requisito di rilascio.
