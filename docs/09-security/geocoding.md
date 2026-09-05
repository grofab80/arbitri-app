# Geocoding

## Provider

Il provider iniziale e OpenStreetMap/Nominatim.

Configurazione:

- `config/geocoding.php`

## Regole

- La geocodifica e manuale, non automatica.
- Non usare autocomplete con il servizio pubblico Nominatim.
- Non fare bulk geocoding.
- Mantenere un User-Agent identificabile.
- Salvare/cacheare le coordinate nel database.
- Per uso intensivo valutare provider compatibile OSM o istanza Nominatim
  dedicata.

## Privacy

Gli indirizzi di residenza sono dati personali.

Limitare l'accesso alle funzioni di geocodifica ai soli profili autorizzati.
