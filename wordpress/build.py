"""Bygger WordPress-kodestykket (Code Snippets) ud fra index.html, styles.css og app.js.

Kør:  python3 wordpress/build.py
Output: wordpress/dist/carelax-rygtjek-snippet.php og wordpress/dist/medier/ (filer til upload i Medier).

Pipedrive (via Make): webhook-adresse og nøgle ligger i wordpress/secrets.local.json, som ikke er i git
(repoet er offentligt). Findes filen, bygges også wordpress/dist/carelax-rygtjek-snippet.local.php med
dem udfyldt – det er den, der skal i Snippets. Uden filen sender kodestykket kun mail.
"""
import json, os, re, shutil

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
DIST = os.path.join(ROOT, 'wordpress', 'dist')
MEDIA = os.path.join(DIST, 'medier')
PREFIX = 'rygtjek-'
TOKEN = '{{RYGTJEK_ASSETS}}'

def read(name):
    with open(os.path.join(ROOT, name), encoding='utf-8') as f:
        return f.read()

def assetize(text):
    # assets/navn  ->  {{RYGTJEK_ASSETS}}rygtjek-navn  (erstattes med Medier-adressen i PHP)
    return text.replace('assets/', TOKEN + PREFIX)

html = read('index.html')
body = html[html.index('<body>') + len('<body>'):html.index('<script src="app.js"></script>')]
css = read('styles.css')
js = read('app.js')

parts = {'BODY': assetize(body).strip(), 'CSS': assetize(css).strip(), 'JS': assetize(js).strip()}
for k, v in parts.items():
    assert 'RYGTJEK_' + k + '_END' not in v

template = read('wordpress/snippet-template.php')
out = template
for k, v in parts.items():
    out = out.replace('/*__' + k + '__*/', v)

def with_secrets(text, values):
    for k, v in values.items():
        assert "'" not in v and '\\' not in v, k
        text = text.replace('/*__' + k + '__*/', v)
    assert '/*__' not in text
    return text

def write(name, text):
    with open(os.path.join(DIST, name), 'w', encoding='utf-8') as f:
        f.write(text)

# Ryd op i dist, men behold filer, der ikke bygges her (fx rygtjek-medier.zip).
shutil.rmtree(MEDIA, ignore_errors=True)
os.makedirs(MEDIA)
write('carelax-rygtjek-snippet.php', with_secrets(out, {'MAKE_URL': '', 'MAKE_KEY': ''}))
secrets_file = os.path.join(ROOT, 'wordpress', 'secrets.local.json')
local = os.path.join(DIST, 'carelax-rygtjek-snippet.local.php')
if os.path.exists(secrets_file):
    with open(secrets_file, encoding='utf-8') as f:
        s = json.load(f)
    write('carelax-rygtjek-snippet.local.php', with_secrets(out, {'MAKE_URL': s['make_url'], 'MAKE_KEY': s['make_key']}))
elif os.path.exists(local):
    os.remove(local)

used = sorted(set(re.findall(r"assets/([A-Za-z0-9._-]+)", body + css + js)))
copied = []
for name in sorted(os.listdir(os.path.join(ROOT, 'assets'))):
    # mag0..mag10 bruges via JS ('assets/mag' + i + '.webp')
    if name in used or re.fullmatch(r'mag\d+\.webp', name):
        shutil.copy(os.path.join(ROOT, 'assets', name), os.path.join(MEDIA, PREFIX + name))
        copied.append(PREFIX + name)
print('Snippet:', os.path.join(DIST, 'carelax-rygtjek-snippet.php'), '(uden Pipedrive)')
if os.path.exists(local):
    print('Snippet med Pipedrive (til Snippets, ikke i git):', local)
print('Medier (%d filer):' % len(copied), ', '.join(copied))
