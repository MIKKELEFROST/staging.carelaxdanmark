(function () {
  // Varianter styres via URL, fx ?headline=problem&steps=1&sticky=0&stats=0&press=0
  var q = new URLSearchParams(location.search);
  var cfg = {
    headline: q.get('headline') === 'problem' ? 'problem' : 'gevinst',
    twoStep: q.get('steps') !== '1',
    sticky: q.get('sticky') !== '0',
    stats: q.get('stats') !== '0',
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
  // Video med Simon: mp4-fil (fx assets/simon.mp4) eller et YouTube/Vimeo-link.
  var VIDEO = { src: '', poster: '' };

  var PAINS = ['Ondt i ryggen', 'Nakke & skuldre', 'Lænd', 'Søvnløshed', 'Gammel madras'];
  var FAQS = [
    ['Er rygtjekket virkelig gratis?', 'Ja. Rygtjekket er helt gratis og uforpligtende. Du betaler intet for besøget, scanningen eller rådgivningen.'],
    ['Skal jeg købe noget bagefter?', 'Nej. Du får vores vurdering og konkrete råd til en bedre nattesøvn. Om du vil handle noget, er helt op til dig.'],
    ['Hvor foregår rygtjekket?', 'Hjemme hos dig. Vi kommer ud, uanset hvor i landet du bor, så du sparer transporttiden og vi kan vurdere den madras, du sover på.'],
    ['Hvad sker der, når jeg har booket?', 'Vi ringer dig op inden for 24 timer på hverdage og aftaler et tidspunkt, der passer dig.']
  ];
  var MAGS = [
    ['Madrassen: en forskel ved osteoporose og rygsmerter', '18. februar 2026'],
    ['Fem centrale sygdomme i bevægeapparatet', '3. december 2025'],
    ['En nyhed i Danmark til skrøbelige eller hårdt ramte rygge', '9. oktober 2025'],
    ['Tjek dine symptomer – og pas på din ryg', '15. august 2025'],
    ['Få scannet din ryg derhjemme', '26. maj 2025'],
    ['Når skuldrene gør ondt', '4. februar 2025'],
    ['Har du stenose, skal du både træne og hvile', '2. december 2024'],
    ['Når der er smerter fra top til tå', '20. november 2024'],
    ['Lændesmerter tærer på livskvaliteten', '19. september 2024'],
    ['Når madrassen bliver en del af din terapi', '2. juli 2024'],
    ['Når madrassen er en del af en livsstrategi', '16. april 2024']
  ];

  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var el = function (tag, cls, text) { var e = document.createElement(tag); if (cls) e.className = cls; if (text != null) e.textContent = text; return e; };

  // Varianter
  $$('[data-headline]').forEach(function (h) { h.hidden = h.dataset.headline !== cfg.headline; });
  if (!cfg.stats) $('[data-section="stats"]').remove();
  if (!cfg.press) $('[data-section="press"]').remove();
  $$('[data-two-step]').forEach(function (n) { n.hidden = !cfg.twoStep; });
  $$('[data-one-step]').forEach(function (n) { n.hidden = cfg.twoStep; });
  $('#primaryLabel').textContent = cfg.twoStep ? 'Næste: Vælg dine gener' : 'Book gratis rygtjek';

  // Specialistfoto (vises hvis assets/specialist.jpg findes)
  var photo = new Image();
  photo.onload = function () { var s = $('#photoSlot'); s.innerHTML = ''; s.appendChild(photo); };
  photo.alt = 'Specialist scanner en ryg hjemme hos en kunde';
  photo.src = 'assets/specialist.jpg';

  // Formular
  var form = $('#form');
  var state = { step: 1, tried: false, pains: [] };
  var fields = { navn: form.navn, tlf: form.tlf, post: form.post };

  function validity() {
    return {
      navn: fields.navn.value.trim().length > 1,
      tlf: fields.tlf.value.replace(/\D/g, '').length === 8,
      post: /^\d{4}$/.test(fields.post.value.trim())
    };
  }
  function isOk(v) { return v.navn && v.tlf && v.post; }

  function renderPains() {
    $$('.pains').forEach(function (wrap) {
      wrap.innerHTML = '';
      PAINS.forEach(function (t) {
        var b = el('button', 'chip', t);
        b.type = 'button';
        b.setAttribute('aria-pressed', state.pains.indexOf(t) > -1 ? 'true' : 'false');
        b.addEventListener('click', function () {
          var i = state.pains.indexOf(t);
          if (i > -1) state.pains.splice(i, 1); else state.pains.push(t);
          renderPains();
        });
        wrap.appendChild(b);
      });
    });
  }

  function render() {
    var v = validity();
    Object.keys(fields).forEach(function (k) { fields[k].classList.toggle('invalid', state.tried && !v[k]); });
    $('#formErr').hidden = !(state.tried && !isOk(v));
    var s2 = cfg.twoStep && state.step === 2;
    $('#step1').hidden = s2;
    $('#step2').hidden = !s2;
    $('#stepNo').textContent = state.step;
    $('#bar2').classList.toggle('on', state.step === 2);
  }

  Object.keys(fields).forEach(function (k) { fields[k].addEventListener('input', render); });

  function send() {
    var data = {
      navn: fields.navn.value.trim(), telefon: fields.tlf.value.trim(), postnummer: fields.post.value.trim(),
      gener: state.pains.slice(), email: form.mail.value.trim(), bemaerkning: form.note.value.trim(),
      variant: { headline: cfg.headline, steps: cfg.twoStep ? 2 : 1 }
    };
    // Demo: ingen backend endnu. Sæt data-endpoint på <form> for at sende bookingen som JSON.
    var endpoint = form.dataset.endpoint;
    var done = function () {
      $('#firstName').textContent = data.navn.split(' ')[0];
      $('#thanksTlf').textContent = data.telefon;
      form.hidden = true;
      $('#thanks').hidden = false;
    };
    if (!endpoint) return done();
    $$('button[type="submit"]', form).forEach(function (b) { b.disabled = true; });
    fetch(endpoint, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) })
      .then(done, done)
      .then(function () { $$('button[type="submit"]', form).forEach(function (b) { b.disabled = false; }); });
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    if (!isOk(validity())) { state.tried = true; state.step = 1; render(); return; }
    if (cfg.twoStep && state.step === 1) { state.step = 2; render(); return; }
    send();
  });
  $('#back').addEventListener('click', function () { state.step = 1; render(); });
  $('#reset').addEventListener('click', function () {
    form.reset();
    state = { step: 1, tried: false, pains: [] };
    form.hidden = false;
    $('#thanks').hidden = true;
    renderPains(); render();
  });

  renderPains(); render();

  // Trustpilot live-widget med fallback til billedet
  if (TRUSTPILOT.businessUnitId) {
    var tp = $('#tpLive');
    tp.innerHTML = '<div class="trustpilot-widget" data-locale="da-DK" data-template-id="' + TRUSTPILOT.templateId +
      '" data-businessunit-id="' + TRUSTPILOT.businessUnitId + '" data-style-height="240px" data-style-width="100%" data-theme="light"' +
      (TRUSTPILOT.tags ? ' data-tags="' + TRUSTPILOT.tags + '"' : '') + ' data-stars="' + TRUSTPILOT.stars + '" data-review-languages="da">' +
      '<a href="https://dk.trustpilot.com/review/carelaxdanmark.dk" target="_blank" rel="noopener">Trustpilot</a></div>';
    tp.hidden = false;
    $('#tpFallback').hidden = true;
    $('.reviews-hint').hidden = true;
    var tps = document.createElement('script');
    tps.src = 'https://widget.trustpilot.com/bootstrap/v5/tp.widget.bootstrap.min.js';
    tps.async = true;
    document.head.appendChild(tps);
  }

  // Video med Simon
  if (VIDEO.src) {
    var frame = $('#videoFrame');
    var yt = VIDEO.src.match(/(?:youtu\.be\/|v=|shorts\/)([\w-]{11})/);
    var vm = VIDEO.src.match(/vimeo\.com\/(\d+)/);
    if (yt || vm) {
      var ifr = document.createElement('iframe');
      ifr.src = yt ? 'https://www.youtube-nocookie.com/embed/' + yt[1] + '?rel=0' : 'https://player.vimeo.com/video/' + vm[1];
      ifr.allow = 'autoplay; fullscreen; picture-in-picture';
      ifr.allowFullscreen = true;
      ifr.title = 'Simon fortæller om rygtjekket';
      frame.appendChild(ifr);
    } else {
      var v = document.createElement('video');
      v.src = VIDEO.src; v.controls = true; v.playsInline = true; v.preload = 'metadata';
      if (VIDEO.poster) v.poster = VIDEO.poster;
      frame.appendChild(v);
    }
    $('#videoSection').hidden = false;
  }

  // Mobilmenu
  var header = $('.header');
  $('#navToggle').addEventListener('click', function () {
    var open = header.classList.toggle('open');
    this.setAttribute('aria-expanded', open);
  });
  $$('.nav a').forEach(function (a) { a.addEventListener('click', function () { header.classList.remove('open'); }); });

  // Scroll til formular
  var card = $('#book');
  $$('[data-to-form]').forEach(function (a) {
    a.addEventListener('click', function (e) {
      e.preventDefault();
      var offset = window.innerWidth <= 900 ? 12 : 90;
      window.scrollTo({ top: card.getBoundingClientRect().top + window.scrollY - offset, behavior: 'smooth' });
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
      var a = el('a', 'mag'); a.href = '#';
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

  // FAQ
  var openFaq = 0;
  function renderFaq() {
    var list = $('#faqList');
    list.innerHTML = '';
    FAQS.forEach(function (f, i) {
      var open = openFaq === i;
      var item = el('div', 'faq-item');
      var btn = el('button', 'faq-q');
      btn.type = 'button';
      btn.setAttribute('aria-expanded', open);
      btn.appendChild(document.createTextNode(f[0]));
      btn.appendChild(el('span', 'faq-icon', open ? '–' : '+'));
      btn.addEventListener('click', function () { openFaq = open ? -1 : i; renderFaq(); });
      item.appendChild(btn);
      if (open) item.appendChild(el('p', 'faq-a', f[1]));
      list.appendChild(item);
    });
  }
  renderFaq();
})();
