# Carelax – Gratis ergonomisk rygtjek (CRO v2)

Statisk landingsside (HTML/CSS/JS), bygget ud fra Claude Design-filen `Rygtjek - CRO v2`. Deployes direkte på Vercel uden build-trin.

## Varianter via URL
- `?headline=problem`: overskriften "Vågner du med ondt i ryggen?" (standard: "Sov bedre…")
- `?steps=1`: formularen i ét trin (standard: 2 trin)
- `?sticky=0`: slå den faste bjælke fra
- `?stats=0` / `?press=0`: skjul tal- eller Sund Forskning-sektionen

## Noter
- Formularen har endnu ingen backend. Sæt `data-endpoint="https://…"` på `<form id="form">`, så sendes bookingen som JSON.
- Specialistfoto: læg billedet i `assets/specialist.jpg`, så vises det automatisk.
