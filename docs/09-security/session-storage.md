# Session Storage

Il token JWT e gestito dal frontend.

## Attenzione

Se salvato in localStorage e esposto a XSS.

Mitigare con:

- escaping output
- Content Security Policy futura
- riduzione superfici `innerHTML`
