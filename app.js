(function () {
  // Leads sendes som JSON til denne adresse (fx en Vercel-funktion, der opretter leadet i Pipedrive).
  // Tom = demo-tilstand: formularen virker, men intet sendes.
  var LEAD_ENDPOINT = '';
  var PRIVACY_URL = ''; // indsæt link til privatlivspolitikken

  var SYMPTOMS = [
    ['Stiv i lænden om morgenen', 'Det letter, når du har været oppe lidt'],
    ['Ondt i nakke eller skuldre', 'Især når du vågner'],
    ['Du sover arm eller skulder', 'Og vågner med prikken i fingrene'],
    ['Du vender dig hele natten', 'Og har svært ved at finde ro'],
    ['Madrassen er over 8-10 år gammel', 'Eller har en tydelig fordybning'],
    ['Du vågner træt', 'Selvom du har sovet længe nok']
  ];
  var PAINS = ['Ondt i ryggen', 'Nakke og skuldre', 'Lænd', 'Søvnløshed', 'Gammel madras'];

  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var el = function (tag, cls, text) { var e = document.createElement(tag); if (cls) e.className = cls; if (text != null) e.textContent = text; return e; };

  // Tracking: virker sammen med GTM/GA4 (dataLayer) og Meta Pixel (fbq), når de er installeret
  function track(event, data) {
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push(Object.assign({ event: event }, data || {}));
    if (event === 'rygtjek_lead' && typeof window.fbq === 'function') window.fbq('track', 'Lead');
  }

  function post(payload) {
    if (!LEAD_ENDPOINT) return Promise.resolve({});
    return fetch(LEAD_ENDPOINT, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) })
      .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json().catch(function () { return {}; }); });
  }

  if (PRIVACY_URL) $('#privacyLink').href = PRIVACY_URL;

  // "Kender du det?" – valgte symptomer vises i formularen og sendes med leadet
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
    Object.keys(symBtns).forEach(function (k) { symBtns[k].setAttribute('aria-pressed', symSel.indexOf(k) > -1); });
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
      c.setAttribute('aria-pressed', on);
      if (on) painSel.push(p); else painSel.splice(painSel.indexOf(p), 1);
    });
    $('#pains').appendChild(c);
  });

  // Formular: leadet sendes ved trin 1, trin 2 opdaterer det samme lead
  var form = $('#form');
  var fields = { navn: form.navn, tlf: form.tlf, post: form.post };
  var tried = false;
  var leadRef = null;

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

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    $('#sendErr').hidden = true;
    if (!isOk(validity())) { tried = true; paint(); return; }
    var lead = {
      trin: 1,
      navn: fields.navn.value.trim(),
      telefon: fields.tlf.value.replace(/\D/g, ''),
      postnummer: fields.post.value.trim(),
      symptomer: symSel.slice(),
      kilde: location.href
    };
    var btn = $('#submit1');
    btn.disabled = true;
    post(lead).then(function (res) {
      leadRef = (res && res.id) || null;
      track('rygtjek_lead', { trin: 1, symptomer: lead.symptomer.length });
      $('#firstName').textContent = lead.navn.split(' ')[0];
      $('#thanksTlf').textContent = fields.tlf.value.trim();
      showStep(2);
    }, function () {
      $('#sendErr').hidden = false;
    }).then(function () { btn.disabled = false; });
  });

  $('#send2').addEventListener('click', function () {
    var btn = this;
    var extra = {
      trin: 2,
      id: leadRef,
      telefon: fields.tlf.value.replace(/\D/g, ''),
      gener: painSel.slice(),
      email: form.mail.value.trim(),
      bemaerkning: form.note.value.trim()
    };
    btn.disabled = true;
    // Trin 2 er valgfrit, så kunden ser tak-beskeden, også hvis opdateringen fejler
    post(extra).catch(function () {}).then(function () {
      track('rygtjek_lead_detaljer');
      btn.disabled = false;
      showStep(3);
    });
  });

  $('#reset').addEventListener('click', function () {
    form.reset();
    tried = false; leadRef = null; symSel = []; painSel = [];
    $$('.chip').forEach(function (c) { c.setAttribute('aria-pressed', 'false'); });
    renderSym(); paint(); showStep(1);
  });

  // Scroll til formularen
  var card = $('#book');
  function toForm(e) {
    if (e) e.preventDefault();
    var offset = window.innerWidth <= 900 ? 12 : 90;
    window.scrollTo({ top: card.getBoundingClientRect().top + window.scrollY - offset, behavior: 'smooth' });
    setTimeout(function () { if (!$('#step1').hidden) try { fields.navn.focus({ preventScroll: true }); } catch (_) {} }, 500);
  }
  $$('[data-to-form]').forEach(function (a) { a.addEventListener('click', toForm); });

  // Fast bjælke, når formularen er scrollet forbi
  var sticky = $('#sticky');
  function onScroll() { sticky.hidden = !(card.getBoundingClientRect().bottom < 0); }
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  // Video med Simon: starter uden lyd (indbrændte undertekster), lyd kan slås til
  var video = $('#simonVideo');
  var soundBtn = $('#vidSound');
  if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    video.removeAttribute('autoplay'); video.pause(); video.controls = true;
  }
  soundBtn.addEventListener('click', function () {
    video.muted = !video.muted;
    if (!video.muted) { video.currentTime = 0; video.play(); }
    soundBtn.setAttribute('aria-pressed', !video.muted);
    $('.sound-on', soundBtn).style.display = video.muted ? 'none' : '';
    $('.sound-off', soundBtn).style.display = video.muted ? '' : 'none';
    $('span', soundBtn).textContent = video.muted ? 'Slå lyd til' : 'Slå lyd fra';
  });
})();
