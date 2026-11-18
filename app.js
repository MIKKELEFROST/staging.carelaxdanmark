(function () {
  // Varianter styres via URL, fx ?sticky=0&press=0
  var q = new URLSearchParams(location.search);
  var cfg = {
    sticky: q.get('sticky') !== '0',
    press: q.get('press') !== '0'
  };

  // Trustpilot TrustBox: indsæt Business Unit ID fra Trustpilot Business → Integrations → TrustBox.
  // tags filtrerer anmeldelserne (kræver at anmeldelserne er tagget i Trustpilot).
  var TRUSTPILOT = {
    businessUnitId: '',
    templateId: '54ad5defc6454f065c28af8b', // "Slider"
    tags: 'seng',
    stars: '4,5'
  };

  // Udvalgte seng- og rygrelaterede anmeldelser fra Trustpilot (vises indtil TrustBox er sat op)
  var REVIEWS = [
    { name: 'Bente Kristiansen', date: '22. okt. 2026', title: 'God og konstruktiv oplevelse',
      text: 'Jeg havde besøg af Samuel fra Carelax, der foretog en rygmåling. Denne viste en skæv ryg. Vi blev enige i, at jeg skulle investere i en ny seng med specialmadras. Resultatet er bare fantastisk. Jeg sover igennem om natten uden problemer. I september foretog Samuel en ny scanning af ryggen – min ryg har nu rettet sig fra skæv til normal.' },
    { name: 'Lisette Jespersen', date: '26. okt. 2026', title: 'Grundig information og scanning',
      text: 'Grundig information og scanning af rygsøjle gjorde mig tryg ved at investere i god madras.' },
    { name: 'Dan Madsen', date: '22. okt. 2026', title: 'Bedste investering',
      text: 'Det er den bedste investering, jeg har gjort i mange år. Er kun ked af, jeg ikke har købt den madras tidligere.' }
  ];

  // "Kender du det?" – titlerne sendes med bookingen (skal matche listen i WordPress-kodestykket)
  var SYMPTOMS = [
    ['Stiv i lænden om morgenen', 'Det letter, når du har været oppe lidt'],
    ['Ondt i nakke eller skuldre', 'Især når du vågner'],
    ['Du sover på armen eller skulderen', 'Og vågner med prikken i fingrene'],
    ['Du vender dig hele natten', 'Og har svært ved at finde ro'],
    ['Madrassen er over 8–10 år gammel', 'Eller har en tydelig fordybning'],
    ['Du vågner træt', 'Selvom du har sovet længe nok']
  ];
  // Gener i trin 2 (skal matche listen i WordPress-kodestykket)
  var PAINS = ['Ondt i ryggen', 'Nakke og skuldre', 'Lænd', 'Søvnløshed', 'Gammel madras'];

  // [titel, dato, artikel på sund-forskning.dk]
  var SF = 'https://sund-forskning.dk/artikler/';
  var MAGS = [
    ['Madrassen: en forskel ved osteoporose og rygsmerter', '18. februar 2026', SF + 'madrassen-en-forskel-ved-osteoporose-og-rygsmerter/'],
    ['Fem centrale sygdomme i bevægeapparatet', '3. december 2025', SF + 'fem-centrale-sygdomme-i-bevaegeapparatet/'],
    ['En nyhed i Danmark til skrøbelige eller hårdt ramte rygge', '9. oktober 2025', SF + 'en-nyhed-i-danmark-til-skroebelige-eller-haardt-ramte-rygge/'],
    ['Tjek dine symptomer – og pas på din ryg', '15. august 2025', SF + 'tjek-dine-symptomer-og-pas-paa-din-ryg/'],
    ['Få scannet din ryg derhjemme', '26. maj 2025', SF + 'faa-scannet-din-ryg-derhjemme/'],
    ['Når skuldrene gør ondt', '4. februar 2025', SF + 'nar-skuldrene-gor-ondt/'],
    ['Har du stenose, skal du både træne og hvile', '2. december 2024', SF + 'har-du-stenose-skal-du-bade-traene-og-hvile-feb-2025-foto-af-3-omrader-mangler/'],
    ['Når der er smerter fra top til tå', '20. november 2024', SF + 'nar-der-er-smerter-fra-top-til-ta/'],
    ['Lændesmerter tærer på livskvaliteten', '19. september 2024', SF + 'laendesmerter-taerer-pa-livskvaliteten/'],
    ['Når madrassen bliver en del af din terapi', '2. juli 2024', SF + 'nar-madrassen-bliver-en-del-af-din-terapi/'],
    ['Når madrassen er en del af en livsstrategi', '16. april 2024', SF + 'nar-madrassen-er-en-del-af-en-livsstrategi/']
  ];

  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var el = function (tag, cls, text) { var e = document.createElement(tag); if (cls) e.className = cls; if (text != null) e.textContent = text; return e; };

  // Varianter
  if (!cfg.press) $('[data-section="press"]').remove();

  // På WordPress sætter kodestykket window.CARELAX_RYGTJEK.endpoint (mail til samme modtagere som den
  // gamle rygtjek-formular + Pipedrive via Make). Uden endpoint (fx Vercel-demoen) virker formularen,
  // men intet sendes.
  var form = $('#form');
  var endpoint = form.dataset.endpoint || (window.CARELAX_RYGTJEK && window.CARELAX_RYGTJEK.endpoint);
  var loadedAt = Date.now();
  function post(payload) {
    if (!endpoint) return Promise.resolve();
    payload.side = location.href;
    payload.website = form.website ? form.website.value : ''; // spamfælde
    payload.t = Date.now() - loadedAt;
    return fetch(endpoint, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) })
      .then(function (r) {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json().catch(function () { return {}; });
      });
  }

  // "Kender du det?" – valgte symptomer vises i formularen og sendes med bookingen
  var symSel = [];
  var symBtns = {};
  SYMPTOMS.forEach(function (s) {
    var b = el('button', 'sym');
    b.type = 'button';
    b.setAttribute('aria-pressed', 'false');
    b.appendChild(el('span', 'box'));
    var t = el('span');
    t.appendChild(el('b', null, s[0]));
    t.appendChild(el('span', null, s[1]));
    b.appendChild(t);
    b.addEventListener('click', function () { toggleSym(s[0]); });
    symBtns[s[0]] = b;
    $('#symptoms').appendChild(b);
  });
  function toggleSym(name) {
    var i = symSel.indexOf(name);
    if (i > -1) symSel.splice(i, 1); else symSel.push(name);
    renderSym();
  }
  function renderSym() {
    var n = symSel.length;
    Object.keys(symBtns).forEach(function (k) { symBtns[k].setAttribute('aria-pressed', symSel.indexOf(k) > -1 ? 'true' : 'false'); });
    $('#symText').textContent = n === 0
      ? 'Kan du nikke genkendende til bare én af dem, er et rygtjek et godt sted at starte.'
      : 'Du har sat ' + n + ' flueben. Dine svar sendes med, når du booker, så specialisten kan forberede sig.';
    $('#symBtn').textContent = n === 0 ? 'Book gratis rygtjek' : 'Book med mine svar';
    var list = $('#symList');
    list.innerHTML = '';
    symSel.forEach(function (name) {
      var li = el('li');
      li.appendChild(el('span', null, name));
      var x = el('button', null, '×');
      x.type = 'button';
      x.setAttribute('aria-label', 'Fjern ' + name);
      x.addEventListener('click', function () { toggleSym(name); });
      li.appendChild(x);
      list.appendChild(li);
    });
    $('#symBox').hidden = n === 0;
  }

  // Gener (trin 2)
  var painSel = [];
  PAINS.forEach(function (p) {
    var c = el('button', 'chip', p);
    c.type = 'button';
    c.setAttribute('aria-pressed', 'false');
    c.addEventListener('click', function () {
      var on = c.getAttribute('aria-pressed') !== 'true';
      c.setAttribute('aria-pressed', on ? 'true' : 'false');
      if (on) painSel.push(p); else painSel.splice(painSel.indexOf(p), 1);
    });
    $('#pains').appendChild(c);
  });

  // Formular: bookingen sendes ved trin 1, trin 2 sender valgfrie ekstra oplysninger
  var fields = { navn: form.navn, tlf: form.tlf, post: form.post };
  var tried = false;
  var booking = null;

  function validity() {
    return {
      navn: fields.navn.value.trim().length > 1,
      tlf: fields.tlf.value.replace(/\D/g, '').length === 8,
      post: /^\d{4}$/.test(fields.post.value.trim())
    };
  }
  function isOk(v) { return v.navn && v.tlf && v.post; }
  function paint() {
    var v = validity();
    Object.keys(fields).forEach(function (k) { fields[k].classList.toggle('invalid', tried && !v[k]); });
    $('#formErr').hidden = !(tried && !isOk(v));
  }
  Object.keys(fields).forEach(function (k) { fields[k].addEventListener('input', paint); });

  function showStep(n) {
    ['#step1', '#step2', '#step3'].forEach(function (id, i) { $(id).hidden = i !== n - 1; });
  }

  // Konverteringer, når en booking er sendt. Id'erne kommer fra WordPress-kodestykket
  // (window.CARELAX_RYGTJEK.tracking); uden dem (fx Vercel-demoen) sendes intet.
  function trackBooking() {
    var t = window.CARELAX_RYGTJEK && window.CARELAX_RYGTJEK.tracking;
    if (!t) return;
    try {
      window.dataLayer = window.dataLayer || [];
      window.dataLayer.push({ event: 'rygtjek_lead', rygtjek_symptomer: symSel.length });
      var gtag = window.gtag || function () { window.dataLayer.push(arguments); };
      if (t.ga4) gtag('event', 'rygtjek_lead', { send_to: t.ga4 });
      if (t.ads) gtag('event', 'conversion', { send_to: t.ads });
      if (typeof window.fbq === 'function') (t.meta || []).forEach(function (ev) { window.fbq('track', ev); });
    } catch (err) { /* tracking må aldrig stoppe bookingen */ }
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    $('#sendErr').hidden = true;
    if (!isOk(validity())) { tried = true; paint(); return; }
    booking = {
      navn: fields.navn.value.trim(),
      telefon: fields.tlf.value.trim(),
      postnummer: fields.post.value.trim()
    };
    var btn = $('#submit1');
    btn.disabled = true;
    post({ type: 'booking', navn: booking.navn, telefon: booking.telefon, postnummer: booking.postnummer, symptomer: symSel.slice() })
      .then(function (res) {
        // Nøglen til Pipedrive-dealen sendes med trin 2, så de ekstra oplysninger lander på samme deal
        booking.lead = (res && res.lead) || '';
        if (endpoint) trackBooking();
        $('#firstName').textContent = booking.navn.split(' ')[0];
        $('#thanksTlf').textContent = booking.telefon;
        showStep(2);
      }, function () { $('#sendErr').hidden = false; })
      .then(function () { btn.disabled = false; });
  });

  $('#send2').addEventListener('click', function () {
    var btn = this;
    var extra = { gener: painSel.slice(), email: form.mail.value.trim(), bemaerkning: form.note.value.trim() };
    $('#sendErr2').hidden = true;
    // Intet udfyldt: spring direkte til tak
    if (!extra.gener.length && !extra.email && !extra.bemaerkning) { showStep(3); return; }
    btn.disabled = true;
    post({ type: 'extra', navn: booking.navn, telefon: booking.telefon, postnummer: booking.postnummer, lead: booking.lead,
      gener: extra.gener, email: extra.email, bemaerkning: extra.bemaerkning })
      .then(function () { showStep(3); }, function () { $('#sendErr2').hidden = false; })
      .then(function () { btn.disabled = false; });
  });

  $('#reset').addEventListener('click', function () {
    form.reset();
    tried = false; booking = null; symSel = []; painSel = [];
    $$('.chip').forEach(function (c) { c.setAttribute('aria-pressed', 'false'); });
    $('#sendErr').hidden = true; $('#sendErr2').hidden = true;
    renderSym(); paint(); showStep(1);
  });

  // Glidende anmeldelser (dubleres så båndet kan køre i ring)
  (function () {
    var track = $('#rvTrack');
    if (!track) return;
    var star = '<svg width="12" height="12" viewBox="0 0 24 24" fill="#fff"><path d="M12 2l2.9 6.9 7.1.6-5.4 4.7 1.6 7L12 17.3 5.8 21.2l1.6-7L2 9.5l7.1-.6z"/></svg>';
    function card(r, hidden) {
      var c = el('article', 'rv-card');
      if (hidden) c.setAttribute('aria-hidden', 'true');
      var stars = el('div', 'rv-stars'); stars.innerHTML = Array(6).join('<i>' + star + '</i>');
      c.appendChild(stars);
      c.appendChild(el('h3', 'rv-title', r.title));
      c.appendChild(el('p', 'rv-text', r.text));
      var meta = el('div', 'rv-meta');
      meta.appendChild(el('b', null, r.name));
      meta.appendChild(el('span', null, r.date + ' · Trustpilot'));
      c.appendChild(meta);
      return c;
    }
    var set = REVIEWS.concat(REVIEWS);
    set.forEach(function (r) { track.appendChild(card(r, false)); });
    set.forEach(function (r) { track.appendChild(card(r, true)); });
  })();

  // Trustpilot live-widget med fallback til egne anmeldelser
  if (TRUSTPILOT.businessUnitId) {
    var tp = $('#tpLive');
    tp.innerHTML = '<div class="trustpilot-widget" data-locale="da-DK" data-template-id="' + TRUSTPILOT.templateId +
      '" data-businessunit-id="' + TRUSTPILOT.businessUnitId + '" data-style-height="240px" data-style-width="100%" data-theme="light"' +
      (TRUSTPILOT.tags ? ' data-tags="' + TRUSTPILOT.tags + '"' : '') + ' data-stars="' + TRUSTPILOT.stars + '" data-review-languages="da">' +
      '<a href="https://dk.trustpilot.com/review/carelaxdanmark.dk" target="_blank" rel="noopener">Trustpilot</a></div>';
    tp.hidden = false;
    $('#tpFallback').hidden = true;
    var tps = document.createElement('script');
    tps.src = 'https://widget.trustpilot.com/bootstrap/v5/tp.widget.bootstrap.min.js';
    tps.async = true;
    document.head.appendChild(tps);
  }

  // Video med Simon: starter uden lyd (indbrændte undertekster), lyd kan slås til
  var video = $('#simonVideo');
  var soundBtn = $('#vidSound');
  if (video && soundBtn) {
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      video.removeAttribute('autoplay'); video.pause(); video.controls = true;
    }
    soundBtn.addEventListener('click', function () {
      video.muted = !video.muted;
      if (!video.muted) { video.currentTime = 0; video.play(); }
      soundBtn.setAttribute('aria-pressed', video.muted ? 'false' : 'true');
      $('.sound-on', soundBtn).style.display = video.muted ? 'none' : '';
      $('.sound-off', soundBtn).style.display = video.muted ? '' : 'none';
      $('span', soundBtn).textContent = video.muted ? 'Slå lyd til' : 'Slå lyd fra';
    });
  }

  // Mobilmenu
  var header = $('.header');
  $('#navToggle').addEventListener('click', function () {
    var open = header.classList.toggle('open');
    this.setAttribute('aria-expanded', open);
  });
  $$('.nav a').forEach(function (a) { a.addEventListener('click', function () { header.classList.remove('open'); }); });

  // Undermenuer: pilen åbner/lukker (mobil og touch). På desktop åbner de også ved hover.
  var closeSubs = function (except) {
    $$('.nav-item.open').forEach(function (it) {
      if (it === except) return;
      it.classList.remove('open');
      $('.sub-toggle', it).setAttribute('aria-expanded', 'false');
    });
  };
  $$('.sub-toggle').forEach(function (b) {
    b.addEventListener('click', function () {
      var item = b.parentNode;
      var open = !item.classList.contains('open');
      closeSubs(item);
      item.classList.toggle('open', open);
      b.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  });
  document.addEventListener('click', function (e) { if (!e.target.closest('.nav-item')) closeSubs(); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeSubs(); });

  // Scroll til formularen (og sæt fokus i navnefeltet)
  var card = $('#book');
  $$('[data-to-form]').forEach(function (a) {
    a.addEventListener('click', function (e) {
      e.preventDefault();
      var offset = window.innerWidth <= 900 ? 12 : 90;
      window.scrollTo({ top: card.getBoundingClientRect().top + window.scrollY - offset, behavior: 'smooth' });
      setTimeout(function () { if (!$('#step1').hidden) try { fields.navn.focus({ preventScroll: true }); } catch (_) {} }, 500);
    });
  });

  // Fast bjælke når formularen er scrollet forbi
  var sticky = $('#sticky');
  if (cfg.sticky) {
    var onScroll = function () { sticky.hidden = !(card.getBoundingClientRect().bottom < 0); };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  // Sund Forskning-karrusel
  var track = $('#pressTrack');
  if (track) {
    MAGS.forEach(function (m, i) {
      var a = el('a', 'mag'); a.href = m[2]; a.target = '_blank'; a.rel = 'noopener';
      var img = el('img'); img.src = 'assets/mag' + i + '.webp'; img.alt = ''; img.loading = 'lazy';
      a.appendChild(img);
      a.appendChild(el('div', 'mag-t', m[0]));
      a.appendChild(el('div', 'mag-d', m[1]));
      track.appendChild(a);
    });
    $$('[data-press]').forEach(function (b) {
      b.addEventListener('click', function () { track.scrollBy({ left: Number(b.dataset.press) * 488, behavior: 'smooth' }); });
    });
  }

  renderSym(); paint();
})();
