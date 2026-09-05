# Pagina Sincronizzazione

## Percorso

`admin/import.php`

La pagina compare nel gruppo sidebar `Configurazione` con permesso
`import.view`.

## Funzioni

- configurazione URL, chiave API, SSL e timeout
- verifica collegamento con il plugin WordPress
- sync incrementale
- sync completo con conferma modale
- storico esecuzioni DataTables
- dettaglio esiti in modale
- griglia correzioni mapping con modifica singola o massiva
- associazione manuale di disciplina e stagione locale
- esclusione esplicita di record remoti anomali
- stato sorgente distinto dall'esito dell'ultima sincronizzazione
- progress bar reale, collocata accanto allo stato della sorgente e sopra la
  nota `Import non distruttivo`, con percentuale, fase, contatori e durata
- ripristino automatico della progress bar dopo il refresh della pagina

Una sorgente disabilitata usa un badge neutro e mantiene disabilitati i comandi
di sync anche quando il test di connessione ha avuto esito positivo.

Il badge `Sorgente dati` rappresenta soltanto la raggiungibilita della sorgente:
`Non verificata`, `Connessa` o `Non raggiungibile`. `Ultimo esito` rappresenta
invece soltanto il sync: `Mai eseguita`, `Completata`, `Completata con anomalie`
o `Fallita`.

Il riquadro `Stato sincronizzazione` usa lo stesso bordo superiore blu della
sorgente e presenta l'esito anche in un badge nell'intestazione. Durante
l'elaborazione il badge mostra `In corso` e viene aggiornato con l'esito finale.

Durante una sincronizzazione vengono disabilitati configurazione sorgente,
verifica connessione, comandi di sync e correzioni mapping. Al termine la barra
usa verde, giallo o rosso rispettivamente per esito positivo, parziale o
fallito, quindi aggiorna sorgente, storico e anomalie.

Il riquadro stato mostra sempre la data dell'ultima esecuzione. Il checkpoint
tecnico e presentato come `Dati sincronizzati fino al` e resta invariato quando
un sync e parziale, perche i record falliti devono essere ripresi.

## Permessi UI

- `import.view`: pagina e storico
- `import.manage`: modifica sorgente, test connessione e correzioni mapping
- `import.run`: avvio sincronizzazioni

Il frontend gestisce la visibilita per usabilita. Le stesse regole sono sempre
applicate dalle route backend.
