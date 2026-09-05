# Admin Layout

Il layout admin e composto da:

- `admin/includes/topbar.php`
- `admin/includes/sidebar.php`
- `admin/includes/footer.php`

## Convenzioni

- topbar mostra utente e stagione corrente
- topbar mostra le notifiche operative con badge non lette e link alla pagina
  filtrata dell'alert
- sidebar applica visibilita tramite permessi pagina `*.view`
- sidebar mostra il logo ASDACA con dimensione controllata e stile coerente con la login
- sidebar fissa con scroll verticale interno quando le voci superano l'altezza viewport
- footer carica script comuni

## Permessi Sidebar

Le voci menu dichiarano un attributo `data-permission`.

Esempio:

```html
<li data-permission="teams.view">
  <a href="teams">Squadre</a>
</li>
```

`admin/js/auth.js` legge i permessi da `/api/v1/me` e nasconde le voci non
consentite. Gli header della sidebar vengono nascosti quando non contengono
voci visibili.

Le voci correlate possono essere raggruppate con `treeview`. Il gruppo padre
non dichiara un permesso proprio: resta visibile quando almeno uno dei figli e
visibile.

## Struttura Sidebar

La sidebar usa un solo header principale:

- `GESTIONE`

Le voci sono organizzate in gruppi funzionali:

- Dashboard
- Sport
  - Partite
  - Designazioni
  - Competizioni
  - Squadre
  - Arbitri
  - Stadi
- Segreteria
  - Prima Nota
  - Bilancio
- Configurazione
  - Stagioni
- Sicurezza
  - Utenti
  - Permessi

Il gruppo `Sport` raccoglie le funzioni operative e anagrafiche sportive.

Il gruppo `Configurazione` raccoglie impostazioni amministrative trasversali,
come le stagioni.

Il gruppo `Sicurezza` raccoglie le funzioni di utenti e permessi.

Il gruppo `Segreteria` raccoglie le funzioni economiche e amministrative:
`Prima Nota` per i movimenti puntuali e `Bilancio` per la vista aggregata della
stagione corrente.

Esempio:

```html
<li class="treeview">
  <a href="#"><span>Sicurezza</span></a>
  <ul class="treeview-menu">
    <li data-permission="users.view"><a href="users">Utenti</a></li>
    <li data-permission="permissions.view"><a href="permissions">Permessi</a></li>
  </ul>
</li>
```

Il controllo frontend e solo UX. La protezione definitiva deve restare sugli
endpoint backend.

## Notifiche Topbar

La topbar usa il componente AdminLTE `notifications-menu`.

Comportamento:

- `GET /api/v1/notifications` carica notifiche visibili all'utente
- il badge mostra solo le notifiche non lette
- il click su una notifica la marca come letta e apre la pagina/filtro
  configurato da `href`
- il link footer marca come lette tutte le notifiche visibili

La visibilita finale e controllata dal backend in base ai permessi; il
frontend visualizza solo il payload ricevuto.
