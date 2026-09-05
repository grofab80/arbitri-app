# WordPress Sync Import Workflow

## Flusso Manuale

1. L'amministratore salva la sorgente nella pagina Sincronizzazione.
2. La chiave API viene cifrata prima del salvataggio.
3. `Verifica connessione` controlla `/info` sul plugin WordPress.
4. Il primo sync usa la modalita completa.
5. Il client chiama `/sync` e riceve gli ID modificati e cancellati.
6. I dati vengono letti in ordine: stagioni, competizioni, stadi, squadre,
   arbitri, partite.
7. Ogni ID esterno viene collegato stabilmente al relativo ID locale.
8. Esiti e anomalie vengono salvati nello storico.
9. Il cursore avanza solo con esito completamente positivo.

La verifica connessione aggiorna uno stato dedicato e non sovrascrive esito,
messaggio o data dell'ultima sincronizzazione. Se URL o chiave API cambiano, la
connessione torna `Non verificata` fino a un nuovo test.

Durante l'esecuzione `sync_runs` registra totale, record elaborati, entita
corrente e heartbeat. Il frontend interroga periodicamente
`/football-sync/progress`; ricaricare la pagina non perde la visualizzazione
dello stato. Le modifiche alla sorgente e ai mapping restano bloccate fino alla
fine del processo.

Se il sync e parziale per stagioni o competizioni ambigue:

1. la sezione `Correzioni mapping` raccoglie i record da risolvere
2. l'amministratore assegna disciplina e/o stagione locale, anche in massa
3. un record realmente estraneo puo essere marcato `Ignora`
4. si esegue nuovamente un sync completo
5. squadre e partite vengono elaborate dopo la risoluzione delle competizioni

## Idempotenza

- il mapping ha chiave unica `source + entity_type + external_id`
- l'hash remoto evita update non necessari
- in assenza di mapping viene cercato un candidato per chiave naturale
- candidati multipli producono errore esplicito
- una riesecuzione dopo esito parziale salta i record gia importati e riprova
  quelli falliti
- le correzioni locali partecipano all'hash effettivo e restano applicate nei
  sync successivi

## Ownership Dati

- WordPress governa calendario, risultati e anagrafiche importate
- rating partita e designazione arbitrale locale non vengono sovrascritti
- le associazioni squadra/competizione locali vengono conservate e unite a
  quelle importate
- una cancellazione remota non elimina dati locali
- una stagione remota attiva non sostituisce automaticamente una stagione
  locale gia in corso
- i separatori `-` e `/` nei nomi stagione sono normalizzati prima della
  ricerca del record locale
- le correzioni mapping e le esclusioni non modificano mai WordPress

## Errori Attesi

- disciplina competizione non deducibile
- relazione obbligatoria non ancora mappata
- chiave naturale ambigua
- record indicato da `/sync` ma non restituito dall'endpoint entita
- timeout, errore TLS o autenticazione remota
