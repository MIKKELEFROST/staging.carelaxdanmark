# Carelax – Gratis rygtjek (landingpage v2)

Statisk landingpage (HTML/CSS/JS) for Carelax Danmarks gratis rygtjek. Deployes direkte på Vercel uden build-trin.
Indholdet følger "Rygtjek landingpage v2" (godkendt tekst, nov. 2026).

## Opsætning i `app.js`
- `LEAD_ENDPOINT`: URL der modtager leads som JSON (fx en Vercel-funktion, der opretter leadet i Pipedrive). Tom = demo, intet sendes.
  - Trin 1 sendes ved booking: `trin, navn, telefon, postnummer, symptomer[], kilde`. Svar gerne med `{ "id": "..." }`.
  - Trin 2 (valgfrit) opdaterer samme lead: `trin, id, telefon, gener[], email, bemaerkning`.
  - Fejler trin 1, vises en fejlbesked i stedet for tak-siden.
- `PRIVACY_URL`: link til privatlivspolitikken.

## Tracking
Ved trin 1 skubbes `{event: 'rygtjek_lead'}` til `dataLayer` (GTM/GA4/Google Ads), og Meta Pixel får `Lead`, hvis `fbq` er installeret. Trin 2 giver `rygtjek_lead_detaljer`.

## Mangler før lancering
- Pipedrive-kobling (backend til `LEAD_ENDPOINT`)
- Meta Pixel og GA4/Google Ads-scripts
- Link til privatlivspolitik og Sund Forskning-artiklerne
- Videoen er en WhatsApp-version (480×848). Udskift `assets/simon.mp4` med den originale fil, når den er klar.
