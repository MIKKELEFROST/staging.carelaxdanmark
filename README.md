# Carelax – Gratis ergonomisk rygtjek

Statisk landingsside (HTML/CSS/JS). Indholdet følger Bilals "Rygtjek landingpage v2". Header, Trustpilot, Sund Forskning og footer (inkl. "Klar til en bedre nattesøvn?") er beholdt fra den tidligere side. Deployes direkte på Vercel uden build-trin.

## Varianter via URL
- `?sticky=0`: slå den faste bjælke fra
- `?press=0`: skjul Sund Forskning-sektionen

## Formularen
- Trin 1 (navn, telefon, postnummer + valgte punkter fra "Kender du det?") sender bookingen. Trin 2 (gener, e-mail, bemærkning) er valgfrit og sendes som en ekstra mail.
- På WordPress sender kodestykket mails til modtagerne på Contact Form 7-formularen "Ergonomisk rygtjek" (ID 1883). Se `wordpress/snippet-template.php`.
- Uden endpoint (Vercel-demoen) virker formularen, men intet sendes.
- Listerne `SYMPTOMS` og `PAINS` i `app.js` skal matche listerne i kodestykket.

## Tracking (kun på WordPress)
- Kodestykket indlæser GTM-T3H8WGW, fordi temaet ellers indsætter den i `header.php`, som siden ikke bruger. GA4/Google Ads-sidevisninger og Meta PageView kommer fra plugins via `wp_head()`.
- Når en booking er sendt (trin 1), sendes: GA4 `rygtjek_lead` (key event), Google Ads "OMN | Rygtjek-lead", Meta `Lead` + `CompleteRegistration` og `dataLayer`-hændelsen `rygtjek_lead`. Id'erne står som konstanter øverst i `wordpress/snippet-template.php`.
- Vercel-demoen sender ingen tracking.

## WordPress
`python3 wordpress/build.py` bygger `wordpress/dist/carelax-rygtjek-snippet.php` (indsættes i Snippets) og `wordpress/dist/medier/` (filer med præfiks `rygtjek-` til Medier). Ret i `index.html`, `styles.css`, `app.js` eller `wordpress/snippet-template.php`, ikke i `dist`.

## Trustpilot live (kun seng-anmeldelser)
Indsæt jeres Business Unit ID i `TRUSTPILOT.businessUnitId` øverst i `app.js`. Så erstattes de glidende anmeldelser af den rigtige TrustBox, filtreret på tagget `seng`. Kræver at anmeldelserne er tagget i Trustpilot Business, og at domænet er tilladt for TrustBoxen.

## Video med Simon
Videoen ligger i `assets/simon.mp4` (plakatbillede `assets/simon-poster.webp`) og vises i "Sådan foregår det". Udskift filen for at skifte video.
