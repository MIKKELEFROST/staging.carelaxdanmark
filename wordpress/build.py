"""Bygger WordPress-kodestykket (Code Snippets) ud fra index.html, styles.css og app.js.

Kør:  python3 wordpress/build.py
Output: wordpress/dist/carelax-rygtjek-snippet.php og wordpress/dist/medier/ (filer til upload i Medier).
"""
import os, re, shutil

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
assert '/*__' not in out

shutil.rmtree(DIST, ignore_errors=True)
os.makedirs(MEDIA)
with open(os.path.join(DIST, 'carelax-rygtjek-snippet.php'), 'w', encoding='utf-8') as f:
    f.write(out)

used = sorted(set(re.findall(r"assets/([A-Za-z0-9._-]+)", body + css + js)))
copied = []
for name in sorted(os.listdir(os.path.join(ROOT, 'assets'))):
    # mag0..mag10 bruges via JS ('assets/mag' + i + '.webp')
    if name in used or re.fullmatch(r'mag\d+\.webp', name):
        shutil.copy(os.path.join(ROOT, 'assets', name), os.path.join(MEDIA, PREFIX + name))
        copied.append(PREFIX + name)
print('Snippet:', os.path.join(DIST, 'carelax-rygtjek-snippet.php'))
print('Medier (%d filer):' % len(copied), ', '.join(copied))
