/**
 * Carelax – Rygtjek landingpage (ny)
 *
 * Tilføjer skabelonen "Rygtjek landingpage (ny)" til rullemenuen Sideattributter → Skabelon.
 * Vælges den på en side, vises den nye landingsside i stedet for temaets layout.
 * Vælg den gamle skabelon igen for at gå tilbage. Temaet bliver ikke ændret.
 *
 * Indsættes i Snippets → Tilføj ny (kør overalt / "Run snippet everywhere").
 * Genereret af wordpress/build.py i repoet staging.carelaxdanmark – ret i repoet, ikke her.
 */

// Indlæs kun én gang, også hvis kodestykket ved en fejl er sat ind to gange.
if ( ! defined( 'CARELAX_RYGTJEK_LOADED' ) ) {
	define( 'CARELAX_RYGTJEK_LOADED', true );

	if ( ! defined( 'CARELAX_RYGTJEK_TEMPLATE' ) ) {
		define( 'CARELAX_RYGTJEK_TEMPLATE', 'carelax-rygtjek-landingpage' );
	}

	// 1) Vis skabelonen i rullemenuen for sider
	add_filter( 'theme_page_templates', function ( $templates ) {
		$templates[ CARELAX_RYGTJEK_TEMPLATE ] = 'Rygtjek landingpage (ny)';
		return $templates;
	} );

	if ( ! function_exists( 'carelax_rygtjek_is_active' ) ) {
		function carelax_rygtjek_is_active() {
			return is_singular( 'page' ) && get_page_template_slug( get_queried_object_id() ) === CARELAX_RYGTJEK_TEMPLATE;
		}
	}

	// Mappen i Medier, hvor filerne med præfiks "rygtjek-" ligger. Findes automatisk ud fra
	// rygtjek-usp0.png, så det virker både med og uden år/måned-mapper i uploads.
	// Kan overstyres med define( 'CARELAX_RYGTJEK_ASSETS', 'https://…/' ); i wp-config.php.
	if ( ! function_exists( 'carelax_rygtjek_assets_url' ) ) {
		function carelax_rygtjek_assets_url() {
			if ( defined( 'CARELAX_RYGTJEK_ASSETS' ) ) {
				return CARELAX_RYGTJEK_ASSETS;
			}
			global $wpdb;
			$file = $wpdb->get_var( $wpdb->prepare(
				"SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND ( meta_value = %s OR meta_value LIKE %s ) ORDER BY meta_id DESC LIMIT 1",
				'rygtjek-usp0.png',
				'%/' . $wpdb->esc_like( 'rygtjek-usp0.png' )
			) );
			$uploads = wp_get_upload_dir();
			$base    = trailingslashit( $uploads['baseurl'] );
			$dir     = $file ? dirname( $file ) : '.';
			return ( '.' === $dir || '' === $dir ) ? $base : $base . trailingslashit( $dir );
		}
	}

	// 2) Fjern temaets og plugins' styling på netop denne side, så layoutet ikke forstyrres.
	//    Cookie-banner/samtykke og admin-bjælken beholdes.
	add_action( 'wp_enqueue_scripts', function () {
		if ( ! carelax_rygtjek_is_active() ) {
			return;
		}
		global $wp_styles;
		$keep = '/(admin-bar|dashicons|cookie|consent|cmplz|complianz|borlabs|cookiebot|cookieyes|gdpr|moove)/i';
		foreach ( (array) $wp_styles->queue as $handle ) {
			if ( ! preg_match( $keep, $handle ) ) {
				wp_dequeue_style( $handle );
			}
		}
	}, 9999 );

	// 3) Udskriv landingssiden. wp_head()/wp_footer() bevares, så SEO (Rank Math),
	//    cookie-samtykke, Google Tag Manager og pixels virker som på resten af sitet.
	add_action( 'template_redirect', function () {
		if ( ! carelax_rygtjek_is_active() ) {
			return;
		}
		$assets = carelax_rygtjek_assets_url();

		$css = <<<'RYGTJEK_CSS_END'
:root{--ink:#1f2a24;--muted:#5a645e;--line:#e7e2d9;--cream:#faf8f4;--sand:#f3efe8;--mint:#e9f5eb;--green:#33b048;--green-d:#2b9a3e}
body{margin:0;background:#fff;font-family:'Open Sans',sans-serif;color:var(--ink);-webkit-font-smoothing:antialiased}
*{box-sizing:border-box}
a{color:inherit;text-decoration:none}
a:hover{color:#33b048}
p{margin:0}
input,textarea,button{font-family:inherit}
[hidden]{display:none!important}

.page{min-width:1240px;font-size:15px;line-height:1.6}
.wrap{max-width:1200px;margin:0 auto}
.raleway{font-family:Raleway,sans-serif;font-weight:800}
.eyebrow{font-size:13px;font-weight:800;letter-spacing:1.5px;color:#33b048}
.h2{margin:10px 0 0;font-family:Raleway,sans-serif;font-weight:800;font-size:44px;line-height:1.15}
.check{flex:none}

/* Sticky bar */
.sticky{position:fixed;top:0;left:0;right:0;z-index:50;background:#fff;border-bottom:1px solid var(--line);box-shadow:0 6px 24px rgba(31,42,36,.08)}
.sticky .wrap{height:64px;display:flex;align-items:center;justify-content:space-between;color:var(--ink)}
.sticky-l{display:flex;align-items:center;gap:18px}
.sticky-l img{height:34px;filter:brightness(0)}
.sticky-sep{width:1px;height:26px;background:var(--line)}
.sticky-sub{color:#7a827d;font-size:13px}
.sticky-r{display:flex;align-items:center;gap:20px}
.sticky-tel{font-weight:700;font-size:14px}
.sticky-cta{background:#33b048;color:#fff;font-weight:700;font-size:14px;padding:11px 22px;border-radius:4px}
.sticky-cta:hover{color:#fff}

/* Header */
.header{background:#fff;border-bottom:1px solid var(--line)}
.header .wrap{height:80px;display:flex;align-items:center;justify-content:space-between;color:var(--ink)}
.header .logo-link{display:block;line-height:0}
.header .logo{height:46px;filter:brightness(0)}
.nav{display:flex;align-items:center;gap:22px;font-size:14px;font-weight:600}
.nav-active{border:2px solid #33b048;border-radius:3px;padding:6px 11px;font-weight:700}
.nav-tel{display:flex;align-items:center;gap:8px;border-left:1px solid var(--line);padding-left:22px}

/* Hero */
.hero{position:relative;background:radial-gradient(ellipse at 12% 0%,#e3f2e5 0%,rgba(250,248,244,0) 55%),radial-gradient(ellipse at 100% 100%,#f1ebe1 0%,rgba(250,248,244,0) 50%),var(--cream);color:var(--ink);overflow:hidden}
.hero .wrap{padding:72px 0 88px;display:grid;grid-template-columns:minmax(0,1fr) 470px;gap:72px;align-items:start}
.hero-copy{padding-top:12px}
.pill{display:inline-flex;align-items:center;gap:10px;border:1px solid #cbe7d0;background:#fff;color:#23803a;font-size:13px;font-weight:700;padding:7px 14px;border-radius:30px}
.pill-dot{width:8px;height:8px;border-radius:50%;background:#33b048}
.hero h1{margin:26px 0 0;font-family:Raleway,sans-serif;font-weight:800;font-size:60px;line-height:1.08;letter-spacing:-.5px;text-wrap:balance}
.green{color:#33b048}
.hero-lead{margin-top:24px;font-size:18px;line-height:1.7;color:var(--muted);max-width:560px;text-wrap:pretty}
.hero-checks{display:flex;flex-direction:column;gap:14px;margin-top:32px}
.hero-check{display:flex;align-items:center;gap:14px;font-size:16px;font-weight:600}
.hero-check-ico{flex:none;width:26px;height:26px;border-radius:50%;background:#33b048;display:flex;align-items:center;justify-content:center}
.trust{display:flex;align-items:center;gap:28px;margin-top:44px;padding-top:28px;border-top:1px solid var(--line)}
.trust-item{display:flex;flex-direction:column;gap:4px}
.trust-tp{gap:6px}
.tp-badge{display:flex;align-items:center;gap:8px;height:20px;font-size:13px;font-weight:700;color:var(--ink)}
.tp-stars{display:flex;gap:2px}
.tp-stars i{width:18px;height:18px;background:#00b67a;display:flex;align-items:center;justify-content:center}
.tp-logo{display:flex;align-items:center;gap:3px;font-weight:700;font-size:13px}
.trust-item b{font-size:14px}
.trust-sub{font-size:12.5px;color:#6f7772}
.trust-sep{width:1px;height:40px;background:var(--line)}

/* Form card */
.card{background:#fff;color:var(--ink);border-radius:14px;border:1px solid var(--line);box-shadow:0 30px 70px rgba(31,42,36,.14);overflow:hidden}
.card-body{padding:30px 32px 28px}
.card-head{display:flex;justify-content:space-between;align-items:baseline}
.card-head h2{margin:0;font-family:Raleway,sans-serif;font-weight:800;font-size:25px}
.step-no{font-size:12.5px;font-weight:700;color:#777}
.bars{display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-top:14px}
.bar{height:4px;border-radius:2px;background:#e6e6e6}
.bar.on{background:#33b048}
.card-intro{margin-top:12px;font-size:13.5px;color:#666}
.fields{display:flex;flex-direction:column;gap:14px;margin-top:22px}
.fields.step2{gap:16px}
.field{display:flex;flex-direction:column;gap:6px;font-size:13px;font-weight:700}
.opt{font-weight:400;color:#888}
.field .opt{margin-top:-6px}
.row2{display:grid;grid-template-columns:minmax(0,1.3fr) minmax(0,1fr);gap:12px}
.input{width:100%;min-width:0;height:50px;border:1.5px solid #dcdcdc;border-radius:8px;padding:0 14px;font-size:15px;outline:none;font-weight:400;color:#222;background:#fff}
textarea.input{height:76px;padding:12px 14px;resize:none}
.input:focus{border-color:#33b048}
.input.invalid{border-color:#e57373}
.input.invalid:focus{border-color:#33b048}
.pains-wrap{display:flex;flex-direction:column;gap:8px}
.pains-label{font-size:13px;font-weight:700}
.pains{display:flex;flex-wrap:wrap;gap:8px}
.chip{border:1.5px solid #dcdcdc;background:#fff;color:#222;font-size:13.5px;font-weight:600;padding:8px 14px;border-radius:30px;cursor:pointer}
.chip[aria-pressed="true"]{border-color:#33b048;background:#e7f5e9}
.form-err{font-size:13px;color:#c62828;font-weight:600}
.btn-primary{height:58px;border:0;border-radius:8px;background:#33b048;color:#fff;font-size:17px;font-weight:800;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:10px;box-shadow:0 8px 20px rgba(51,176,72,.35)}
.btn-primary:hover{background:#2b9a3e}
.btn-primary:disabled{opacity:.7;cursor:wait}
.btn-row{display:grid;grid-template-columns:auto 1fr;gap:10px}
.btn-back{height:58px;padding:0 20px;border:1.5px solid #dcdcdc;background:#fff;border-radius:8px;font-weight:700;font-size:15px;cursor:pointer;color:#222}
.assure{display:flex;justify-content:center;gap:18px;margin-top:18px;font-size:12.5px;color:#555;font-weight:600}
.assure span{display:flex;align-items:center;gap:6px}
.card-foot{background:var(--sand);border-top:1px solid #ececec;padding:16px 32px;display:flex;align-items:center;justify-content:space-between;font-size:13.5px}
.card-foot span{color:#555}
.card-foot a{font-weight:800;font-size:16px;color:#222}
.thanks{padding:56px 36px;text-align:center}
.thanks-ico{width:72px;height:72px;margin:0 auto;border-radius:50%;background:#33b048;display:flex;align-items:center;justify-content:center}
.thanks h2{margin:24px 0 0;font-family:Raleway,sans-serif;font-weight:800;font-size:28px}
.thanks p{margin-top:12px;font-size:15px;color:#555}
.link-btn{margin-top:26px;border:0;background:none;color:#33b048;font-weight:700;font-size:14px;cursor:pointer}

/* Stats */
.stats{background:#33b048;color:#fff}
.stats .wrap{padding:40px 0;display:grid;grid-template-columns:auto auto 1fr;gap:64px;align-items:center}
.stat-n{font-family:Raleway,sans-serif;font-weight:800;font-size:52px;line-height:1}
.stat-l{margin-top:8px;font-size:14px;font-weight:600}
.stats p{font-size:16px;line-height:1.7;border-left:2px solid rgba(255,255,255,.4);padding-left:32px;text-wrap:pretty}

/* Steps */
.steps{background:#fff;padding:110px 0 100px}
.sec-head{display:flex;justify-content:space-between;align-items:end;gap:40px}
.btn-dark{flex:none;background:var(--green);color:#fff;font-weight:700;font-size:15px;padding:15px 26px;border-radius:6px}
.btn-dark:hover{background:var(--green-d);color:#fff}
.steps-grid{position:relative;display:grid;grid-template-columns:repeat(4,1fr);gap:28px;margin-top:64px}
.steps-line{position:absolute;left:30px;right:30px;top:30px;height:2px;background:repeating-linear-gradient(90deg,#cfe9d4 0 8px,transparent 8px 14px)}
.step{position:relative}
.step-n{width:60px;height:60px;border-radius:50%;background:#fff;border:2px solid #33b048;display:flex;align-items:center;justify-content:center;font-family:Raleway,sans-serif;font-weight:800;font-size:22px;color:#33b048}
.step h3{margin:26px 0 10px;font-family:Raleway,sans-serif;font-weight:800;font-size:21px}
.step p{font-size:15px;color:#555;line-height:1.7}

/* Benefits */
.benefits{background:var(--sand);padding:100px 0}
.benefits .wrap{display:grid;grid-template-columns:520px minmax(0,1fr);gap:80px;align-items:center}
.photo{height:600px;position:relative}
.photo-slot{position:absolute;inset:0;border-radius:16px;overflow:hidden;background:#e8e3d9;color:#8a8f84;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;text-align:center;padding:12px;font-size:13px}
.photo-slot::after{content:"";position:absolute;inset:0;border:1.5px dashed currentColor;opacity:.35;border-radius:16px;pointer-events:none}
.photo-slot img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
.photo-badge{position:absolute;right:-36px;bottom:40px;background:#fff;border-radius:12px;box-shadow:0 18px 50px rgba(0,0,0,.15);padding:18px 22px;display:flex;align-items:center;gap:14px;pointer-events:none}
.photo-badge-ico{width:44px;height:44px;border-radius:50%;background:#e7f5e9;display:flex;align-items:center;justify-content:center}
.photo-badge b{display:block;font-size:15px}
.photo-badge span{font-size:13px;color:#666}
.ben-list{display:flex;flex-direction:column;gap:22px;margin-top:36px}
.ben{display:grid;grid-template-columns:44px 1fr;gap:18px}
.ben-ico{width:44px;height:44px;border-radius:10px;background:#fff;border:1px solid #e1e4dc;display:flex;align-items:center;justify-content:center}
.ben b{display:block;font-size:17px}
.ben p{margin-top:4px;font-size:15px;color:#555}

/* Reviews */
.reviews{background:#fff;padding:100px 0 90px}
.reviews .wrap{text-align:center}
.reviews .h2{margin:10px 0 40px;line-height:1.6}
.reviews img{width:100%;display:block;mix-blend-mode:multiply}

/* Press */
.press{background:var(--cream);color:var(--ink);padding:100px 0 110px;overflow:hidden;border-top:1px solid var(--line)}
.press .h2{max-width:700px}
.press-nav{display:flex;gap:10px}
.round-btn{width:52px;height:52px;border-radius:50%;border:1.5px solid #d9d3c8;background:#fff;color:var(--ink);cursor:pointer;font-size:18px}
.round-btn:hover{border-color:#33b048}
.press-track{display:flex;gap:24px;margin-top:48px;overflow-x:auto;scroll-behavior:smooth;scrollbar-width:none;padding-bottom:4px}
.press-track::-webkit-scrollbar{display:none}
.mag{flex:none;width:220px;display:block}
.mag img{width:220px;height:296px;object-fit:cover;display:block;border-radius:6px;box-shadow:0 12px 30px rgba(31,42,36,.14)}
.mag-t{margin-top:14px;font-size:14.5px;font-weight:700;line-height:1.4}
.mag-d{margin-top:6px;font-size:12.5px;color:#7a827d}

/* FAQ */
.faq{background:#fff;padding:100px 0}
.faq .wrap{display:grid;grid-template-columns:380px minmax(0,1fr);gap:80px}
.faq-intro{margin-top:18px;color:#555}
.faq-intro a{font-weight:700;color:#222}
.faq-list{border-top:1px solid #e5e5e5}
.faq-item{border-bottom:1px solid #e5e5e5}
.faq-q{width:100%;display:flex;justify-content:space-between;align-items:center;gap:20px;background:none;border:0;padding:26px 0;text-align:left;font-size:19px;font-weight:700;color:#222;cursor:pointer}
.faq-icon{flex:none;width:32px;height:32px;border-radius:50%;background:var(--mint);display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:400;color:#33b048}
.faq-a{padding:0 60px 26px 0;font-size:15.5px;color:#555;line-height:1.75}

/* Final CTA */
.final{background:radial-gradient(ellipse at 85% 100%,#d6eedb 0%,rgba(233,245,235,0) 60%),var(--mint);color:var(--ink);padding:90px 0}
.final .wrap{display:flex;justify-content:space-between;align-items:center;gap:40px}
.final h2{margin:0;font-family:Raleway,sans-serif;font-weight:800;font-size:46px;line-height:1.1}
.final p{margin-top:14px;font-size:17px;color:var(--muted)}
.final-r{display:flex;align-items:center;gap:22px;flex:none}
.final-tel{font-weight:700;font-size:16px;color:var(--ink)}
.btn-big{background:#33b048;color:#fff;font-weight:800;font-size:17px;padding:19px 34px;border-radius:8px;box-shadow:0 8px 20px rgba(51,176,72,.35)}
.btn-big:hover{background:#2b9a3e;color:#fff}


/* ===== Responsivt: desktop (≥1240px) er uændret ===== */
.m-actions{display:none}

/* Små laptops/tablets i landskab: ingen vandret scroll, luft i siderne */
@media (max-width:1239px){
  .page{min-width:0}
  .wrap{padding-left:24px;padding-right:24px}
  .nav{gap:16px;font-size:13px}
  .nav-tel{padding-left:16px}
  .hero .wrap{gap:48px;grid-template-columns:minmax(0,1fr) 440px;padding-left:24px;padding-right:24px}
  .stats .wrap{padding-left:24px;padding-right:24px;gap:48px}
  .benefits .wrap{grid-template-columns:minmax(0,440px) minmax(0,1fr);gap:64px}
  .photo-badge{right:-20px}
  .input{width:100%;min-width:0}
  .row2{grid-template-columns:minmax(0,1.3fr) minmax(0,1fr)}
}

/* Tablet og mobil */
@media (max-width:900px){
  body{font-size:15px}
  .h2{font-size:32px;line-height:1.2}
  .eyebrow{font-size:12px}

  /* Header + mobilmenu */
  .header{position:relative;z-index:40}
  .header .wrap{height:64px}
  .header .logo{height:36px}
  .m-actions{display:flex;align-items:center;gap:8px}
  .m-call,.nav-toggle{width:44px;height:44px;border-radius:8px;border:1px solid var(--line);background:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;padding:0}
  .nav-toggle{flex-direction:column;gap:5px}
  .nav-toggle span{display:block;width:20px;height:2px;background:var(--ink);border-radius:1px;transition:transform .2s,opacity .2s}
  .header.open .nav-toggle span:nth-child(1){transform:translateY(7px) rotate(45deg)}
  .header.open .nav-toggle span:nth-child(2){opacity:0}
  .header.open .nav-toggle span:nth-child(3){transform:translateY(-7px) rotate(-45deg)}
  .nav{display:none;position:absolute;top:64px;left:0;right:0;flex-direction:column;align-items:stretch;gap:0;background:#fff;border-top:1px solid var(--line);border-bottom:1px solid var(--line);padding:8px 24px 16px;font-size:16px;box-shadow:0 18px 40px rgba(31,42,36,.12)}
  .header.open .nav{display:flex}
  .nav a{padding:14px 0;border-bottom:1px solid #f0ece5}
  .nav .nav-active{align-self:flex-start;margin:10px 0;padding:8px 12px;border-bottom:2px solid #33b048}
  .nav .nav-tel{border-left:0;padding-left:0;border-bottom:0}

  /* Fast bjælke bliver en bund-bjælke med de to vigtigste handlinger */
  .sticky{top:auto;bottom:0;border-bottom:0;border-top:1px solid var(--line);box-shadow:0 -6px 24px rgba(31,42,36,.1);padding-bottom:env(safe-area-inset-bottom)}
  .sticky .wrap{height:auto;padding-top:10px;padding-bottom:10px}
  .sticky-l{display:none}
  .sticky-r{width:100%;gap:10px}
  .sticky-tel{flex:none;display:flex;align-items:center;justify-content:center;height:48px;padding:0 16px;border:1px solid var(--line);border-radius:6px}
  .sticky-cta{flex:1;text-align:center;height:48px;display:flex;align-items:center;justify-content:center;font-size:15px;border-radius:6px}

  /* Hero: overskrift → tekst → formular → fordele → tillid */
  .hero .wrap{display:flex;flex-direction:column;padding:32px 24px 48px;gap:0}
  .hero-copy{display:contents}
  .pill{order:1;align-self:flex-start;font-size:12px;padding:6px 12px}
  .hero h1{order:2;margin-top:18px;font-size:40px;line-height:1.1;letter-spacing:-.3px}
  .hero-lead{order:3;margin-top:16px;font-size:16px;line-height:1.65}
  .card{order:4;margin-top:28px;width:100%;max-width:560px;align-self:center}
  .hero-checks{order:5;margin-top:32px;gap:12px}
  .hero-check{font-size:15px;align-items:flex-start}
  .trust{order:6;margin-top:32px;padding-top:24px;flex-wrap:wrap;gap:18px 24px}
  .trust-sep{display:none}
  .trust-item{flex:1 1 40%}
  .trust-tp{flex-basis:100%}

  /* Formular */
  .card-body{padding:24px 20px 22px}
  .card-head h2{font-size:22px}
  .input{font-size:16px;width:100%;min-width:0}
  .row2{grid-template-columns:minmax(0,1.3fr) minmax(0,1fr)}
  .assure{flex-wrap:wrap;gap:8px 16px}
  .card-foot{padding:14px 20px;flex-wrap:wrap;gap:4px 12px}
  .thanks{padding:44px 24px}

  /* Tal */
  .stats .wrap{grid-template-columns:1fr 1fr;gap:28px 24px;padding:36px 24px}
  .stat-n{font-size:40px}
  .stats p{grid-column:1/-1;border-left:0;border-top:2px solid rgba(255,255,255,.4);padding-left:0;padding-top:20px;font-size:15px}

  /* Sektioner generelt */
  .steps{padding:64px 0}
  .benefits,.reviews,.faq{padding:64px 0}
  .press{padding:64px 0 72px}
  .final{padding:56px 0}
  .sec-head{flex-direction:column;align-items:flex-start;gap:20px}

  /* Fire trin: lodret tidslinje */
  .steps-grid{grid-template-columns:1fr;gap:0;margin-top:36px}
  .steps-line{display:none}
  .step{display:grid;grid-template-columns:60px 1fr;column-gap:18px;padding-bottom:28px}
  .step:not(:last-child)::before{content:"";position:absolute;left:29px;top:60px;bottom:0;width:2px;background:repeating-linear-gradient(180deg,#cfe9d4 0 8px,transparent 8px 14px)}
  .step:last-child{padding-bottom:0}
  .step-n{grid-row:1/3;width:60px;height:60px;font-size:20px;position:relative;z-index:1}
  .step h3{margin:14px 0 6px;font-size:19px}
  .step p{font-size:15px}

  /* Det får du */
  .benefits .wrap{display:flex;flex-direction:column;gap:40px}
  .benefits .wrap>div:last-child{order:-1}
  .photo{width:100%;height:340px}
  .photo-badge{right:12px;bottom:12px;padding:12px 16px}
  .ben-list{margin-top:28px;gap:18px}
  .ben{grid-template-columns:40px 1fr;gap:14px}
  .ben-ico{width:40px;height:40px}
  .ben b{font-size:16px}

  /* Anmeldelser: widget kan swipes vandret */
  .reviews .h2{margin:10px 0 28px;line-height:1.2}

  /* Sund Forskning */
  .press-nav{display:none}
  .press-track{margin:32px -24px 0;padding:0 24px 4px;gap:16px;scroll-snap-type:x mandatory;scroll-padding-left:24px}
  .mag{width:180px;scroll-snap-align:start}
  .mag img{width:180px;height:242px}
  .mag-t{font-size:14px}

  /* FAQ */
  .faq .wrap{grid-template-columns:1fr;gap:28px}
  .faq-q{font-size:17px;padding:20px 0;align-items:flex-start}
  .faq-a{padding:0 0 20px;font-size:15px}

  /* Afslutning + footer */
  .final .wrap{flex-direction:column;align-items:stretch;text-align:center;gap:28px}
  .final h2{font-size:32px}
  .final p{font-size:16px}
  .final-r{flex-direction:column-reverse;align-items:stretch;gap:16px}
  .btn-big{display:block;text-align:center}

}

/* Mobil */
@media (max-width:560px){
  .wrap{padding-left:16px;padding-right:16px}
  .nav{padding-left:16px;padding-right:16px}
  .hero .wrap{padding:24px 16px 40px}
  .hero h1{font-size:33px}
  .h2{font-size:28px}
  .card{margin-top:24px}
  .card-body{padding:22px 16px 20px}
  .card-head h2{font-size:20px}
  .step-no{white-space:nowrap;font-size:12px}
  .card-foot{padding:14px 16px}
  .row2{grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:10px}
  .btn-primary{font-size:16px;height:56px}
  .btn-row .btn-back{padding:0 16px}
  .stat-n{font-size:34px}
  .stats .wrap{gap:24px 16px;padding-left:16px;padding-right:16px}
  .photo{height:280px}
  .press-track{margin-left:-16px;margin-right:-16px;padding-left:16px;padding-right:16px;scroll-padding-left:16px}
  .final h2{font-size:28px}
  .sticky .wrap{padding-left:12px;padding-right:12px}
}

/* Trustpilot live + video (lyst tema) */
.tp-live{width:100%;min-height:240px}
.video{background:#fff;padding:0 0 100px}
.video .wrap{display:grid;grid-template-columns:minmax(0,1fr) 360px;gap:80px;align-items:center}
.video-copy{max-width:560px}
.video-quote{margin:22px 0 0;padding-left:18px;border-left:3px solid var(--green);font-style:italic;font-size:17px;line-height:1.7;color:var(--ink)}
.video-quote cite{display:block;margin-top:10px;font-style:normal;font-weight:700;font-size:13.5px;color:var(--muted)}
.video-lead{margin-top:18px;font-size:16px;color:var(--muted);line-height:1.7}
.video .btn-dark{display:inline-block;margin-top:28px}
.video-frame{position:relative;aspect-ratio:9/16;width:100%;max-width:360px;border-radius:18px;overflow:hidden;background:var(--ink);box-shadow:0 30px 70px rgba(31,42,36,.18)}
.vid-sound{position:absolute;left:14px;bottom:14px;border:0;border-radius:30px;padding:9px 16px;background:rgba(255,255,255,.92);color:var(--ink);font-weight:700;font-size:13px;cursor:pointer;box-shadow:0 6px 18px rgba(0,0,0,.2)}
.vid-sound::before{content:'🔈 '}
.vid-sound[aria-pressed="true"]::before{content:'🔊 '}
.video-frame video,.video-frame iframe{position:absolute;inset:0;width:100%;height:100%;border:0;object-fit:cover}
@media (max-width:1239px){.video .wrap{gap:48px}}
@media (max-width:900px){
  .video{padding:0 0 64px}
  .video .wrap{grid-template-columns:1fr;gap:28px}
  .video-frame{justify-self:center;max-width:340px}
  .tp-live{min-height:300px}
}

/* Footer – samme som carelaxdanmark.dk */
.usp-bar{background:#222;height:54px}
.usp-bar .wrap{height:100%;display:grid;grid-template-columns:repeat(5,1fr);align-items:center;color:#fff;font-size:13px;font-weight:700}
.usp{display:flex;align-items:center;gap:8px}
.usp img{width:28px;height:28px;margin:-2px}
.footer{background:linear-gradient(90deg,#040404 0%,#161616 60%,#222 100%);padding:60px 0 22px;color:#fff}
.footer a{color:#fff}
.footer a:hover{color:#33b048}
.footer-grid{display:grid;grid-template-columns:245px 245px 245px 245px 1fr;font-size:13px}
.footer h4{margin:0 0 14px;font-family:Raleway,sans-serif;font-weight:700;font-size:17px}
.f-contact{display:flex;flex-direction:column;line-height:28px;font-weight:600}
.f-row{display:flex;align-items:center;gap:8px}
.f-row svg{flex:none}
.f-social{position:relative;width:62px;margin-top:14px}
.f-social img{width:62px;display:block}
.f-social a{position:absolute;top:0;bottom:0;width:50%}
.f-social a:first-of-type{left:0}
.f-social a:last-of-type{right:0}
.f-gazelle-link{display:block;line-height:0}
.f-links{display:flex;flex-direction:column;line-height:27px;font-weight:600}
.f-arrow{color:#33b048}
.f-news input{width:220px;max-width:100%;background:transparent;border:0;border-bottom:1px solid #888;color:#fff;font:inherit;font-size:13px;padding:6px 0;outline:none}
.f-news input::placeholder{color:#999}
.f-news button{margin-top:14px;background:#33b048;color:#fff;border:0;font:inherit;font-weight:700;font-size:10px;padding:12px 20px;border-radius:2px;cursor:pointer}
.f-badges{display:flex;flex-direction:column;align-items:flex-end}
.f-gazelle{width:224px}
.f-tp{align-self:flex-start;margin-left:40px;margin-top:6px}
.f-tp img{width:164px;display:block}
.footer-bottom{text-align:center;margin-top:72px}
.f-pay{height:22px}
.footer-bottom p{margin-top:14px;font-size:10.5px;color:#999}
@media (max-width:1239px){
  .footer-grid{grid-template-columns:repeat(4,minmax(0,1fr)) auto;gap:24px}
  .usp-bar .wrap{padding-left:24px;padding-right:24px}
}
@media (max-width:900px){
  .usp-bar{height:auto}
  .usp-bar .wrap{grid-template-columns:repeat(3,auto);justify-content:center;gap:12px 24px;padding-top:14px;padding-bottom:14px;font-size:12.5px}
  .footer{padding:44px 0 96px}
  .footer-grid{grid-template-columns:1fr 1fr;gap:36px 24px}
  .f-badges{grid-column:1/-1;align-items:flex-start}
  .f-tp{margin-left:0}
  .footer-bottom{margin-top:44px;padding:0 16px}
}
@media (max-width:560px){
  .usp-bar .wrap{grid-template-columns:repeat(2,auto);justify-content:start}
  .footer-grid{grid-template-columns:1fr}
}

/* Så simpelt er det (under formularen) */
.hero-side{min-width:0}
.simple{margin-top:20px;background:#fff;border:1px solid var(--line);border-radius:14px;padding:20px 26px 22px;box-shadow:0 14px 36px rgba(31,42,36,.07)}
.simple h3{margin:0;font-family:Raleway,sans-serif;font-weight:800;font-size:21px}
.simple ol{list-style:none;margin:12px 0 0;padding:0;display:grid;grid-template-columns:1fr;gap:8px}
.simple li{display:flex;align-items:baseline;gap:8px;font-size:12.5px;font-weight:800;text-transform:uppercase;letter-spacing:.2px;line-height:1.35}
.simple li b{color:var(--green);flex:none}
.zzz{color:var(--green);font-weight:800;letter-spacing:0}
.zzz sup{font-size:.75em}
@media (max-width:900px){
  .hero-side{display:contents}
  .simple{order:5;margin-top:20px;width:100%;max-width:560px;align-self:center}
  .hero-checks{order:6}
  .trust{order:7}
}
@media (max-width:560px){
  .simple{padding:18px 18px 20px}
}

/* Glidende Trustpilot-anmeldelser */
.rv{position:relative;overflow:hidden;-webkit-mask-image:linear-gradient(90deg,transparent,#000 6%,#000 94%,transparent);mask-image:linear-gradient(90deg,transparent,#000 6%,#000 94%,transparent)}
.rv-track{display:flex;gap:20px;width:max-content;animation:rv-scroll 60s linear infinite;padding:6px 0 10px}
.rv:hover .rv-track{animation-play-state:paused}
@keyframes rv-scroll{to{transform:translateX(-50%)}}
.rv-card{flex:none;width:340px;text-align:left;background:#fff;border:1px solid var(--line);border-radius:14px;padding:22px 24px;box-shadow:0 10px 28px rgba(31,42,36,.06);display:flex;flex-direction:column}
.rv-stars{display:flex;gap:2px}
.rv-stars i{width:20px;height:20px;background:#00b67a;display:flex;align-items:center;justify-content:center}
.rv-title{margin:14px 0 6px;font-size:16px;font-weight:800}
.rv-text{font-size:14px;line-height:1.6;color:var(--muted);flex:1}
.rv-meta{margin-top:16px;display:flex;flex-direction:column;font-size:13px}
.rv-meta span{color:#7a827d;font-size:12px}
.rv-all{display:inline-block;margin-top:26px;font-weight:700;font-size:14px;color:var(--green)}
@media (prefers-reduced-motion:reduce){
  .rv-track{animation:none}
  .rv{overflow-x:auto;-webkit-mask-image:none;mask-image:none}
}
@media (max-width:900px){
  .rv{margin:0 -24px}
  .rv-card{width:290px;padding:20px}
  .rv-track{animation-duration:45s}
}
@media (max-width:560px){.rv{margin:0 -16px}}
RYGTJEK_CSS_END;

		$body = <<<'RYGTJEK_BODY_END'
<div class="page">

<div class="sticky" id="sticky" hidden>
  <div class="wrap">
    <div class="sticky-l"><img src="https://carelaxdanmark.dk/wp-content/uploads/carelaxdanmark-1-1.svg" alt="Carelax"><span class="sticky-sep"></span><span style="font-weight:700">Gratis ergonomisk rygtjek</span><span class="sticky-sub">· Vi kommer til dig · 100 % uforpligtende</span></div>
    <div class="sticky-r"><a href="tel:+4578155703" class="sticky-tel">78 15 57 03</a><a href="#book" class="sticky-cta" data-to-form>Book gratis rygtjek</a></div>
  </div>
</div>

<header class="header">
  <div class="wrap">
    <a href="https://carelaxdanmark.dk/" class="logo-link"><img class="logo" src="https://carelaxdanmark.dk/wp-content/uploads/carelaxdanmark-1-1.svg" alt="Carelax Danmark"></a>
    <nav class="nav">
      <a href="https://carelaxdanmark.dk/">Massagestole</a><a href="https://carelaxdanmark.dk/produkt-kategori/massageapparater/">Massageapparater</a><a href="https://carelaxdanmark.dk/produkt-kategori/senge/">Senge</a><a href="https://carelaxdanmark.dk/ergonomisk-rygtjek/" class="nav-active" aria-current="page">Gratis ergonomisk rygtjek</a><a href="https://carelaxdanmark.dk/erhverv/">Erhverv</a><a href="https://carelaxdanmark.dk/om-os/">Om os</a><a href="https://carelaxdanmark.dk/blog/">Viden</a>
      <a href="tel:+4578155703" class="nav-tel"><svg width="14" height="14" viewBox="0 0 24 24" fill="#33b048"><path d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1A17 17 0 0 1 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1z"/></svg>78 15 57 03</a>
    </nav>
    <div class="m-actions">
      <a href="tel:+4578155703" class="m-call" aria-label="Ring 78 15 57 03"><svg width="18" height="18" viewBox="0 0 24 24" fill="#33b048"><path d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1A17 17 0 0 1 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1z"/></svg></a>
      <button type="button" class="nav-toggle" id="navToggle" aria-label="Menu" aria-expanded="false"><span></span><span></span><span></span></button>
    </div>
  </div>
</header>

<section class="hero">
  <div class="wrap">
    <div class="hero-copy">
      <div class="pill"><span class="pill-dot"></span>Gratis · Uforpligtende · Vi kommer til dig</div>
      <h1 data-headline="gevinst">Sov bedre. Få et <span class="green">gratis rygtjek</span> hjemme hos dig.</h1>
      <h1 data-headline="problem" hidden>Vågner du med <span class="green">ondt i ryggen?</span> Vi finder årsagen – gratis.</h1>
      <p class="hero-lead">En af vores specialister i ergonomi og søvn kommer ud til dig, scanner din ryg og vurderer din madras. Du får konkrete råd til en bedre nattesøvn – uden at skulle købe noget.</p>
      <div class="hero-checks">
        <div class="hero-check"><span class="hero-check-ico"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3.5"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg></span>Specialist i ergonomi og søvn kommer hjem til dig</div>
        <div class="hero-check"><span class="hero-check-ico"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3.5"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg></span>Liggende scanning af ryggen og vurdering af din madras</div>
        <div class="hero-check"><span class="hero-check-ico"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3.5"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg></span>Konkrete råd – helt uden købsforpligtelse</div>
      </div>
      <div class="trust">
        <a href="https://dk.trustpilot.com/review/carelaxdanmark.dk" target="_blank" rel="noopener" class="trust-item trust-tp">
          <span class="tp-badge" aria-label="Fremragende – 5 stjerner på Trustpilot">Fremragende <span class="tp-stars"><i><svg width="12" height="12" viewBox="0 0 24 24" fill="#fff"><path d="M12 2l2.9 6.9 7.1.6-5.4 4.7 1.6 7L12 17.3 5.8 21.2l1.6-7L2 9.5l7.1-.6z"/></svg></i><i><svg width="12" height="12" viewBox="0 0 24 24" fill="#fff"><path d="M12 2l2.9 6.9 7.1.6-5.4 4.7 1.6 7L12 17.3 5.8 21.2l1.6-7L2 9.5l7.1-.6z"/></svg></i><i><svg width="12" height="12" viewBox="0 0 24 24" fill="#fff"><path d="M12 2l2.9 6.9 7.1.6-5.4 4.7 1.6 7L12 17.3 5.8 21.2l1.6-7L2 9.5l7.1-.6z"/></svg></i><i><svg width="12" height="12" viewBox="0 0 24 24" fill="#fff"><path d="M12 2l2.9 6.9 7.1.6-5.4 4.7 1.6 7L12 17.3 5.8 21.2l1.6-7L2 9.5l7.1-.6z"/></svg></i><i><svg width="12" height="12" viewBox="0 0 24 24" fill="#fff"><path d="M12 2l2.9 6.9 7.1.6-5.4 4.7 1.6 7L12 17.3 5.8 21.2l1.6-7L2 9.5l7.1-.6z"/></svg></i></span><span class="tp-logo"><svg width="14" height="14" viewBox="0 0 24 24" fill="#00b67a"><path d="M12 2l2.9 6.9 7.1.6-5.4 4.7 1.6 7L12 17.3 5.8 21.2l1.6-7L2 9.5l7.1-.6z"/></svg>Trustpilot</span></span>
          <span class="trust-sub">TrustScore 4,9 · 551 anmeldelser</span></a>
        <span class="trust-sep"></span>
        <div class="trust-item"><b>Børsen Gazelle</b><span class="trust-sub">Flere år i træk</span></div>
        <span class="trust-sep"></span>
        <div class="trust-item"><b>Kendt fra Sund Forskning</b><span class="trust-sub">11 artikler siden 2024</span></div>
      </div>
    </div>

    <div class="hero-side">
    <div id="book" class="card">
      <form id="form" novalidate>
        <div class="card-body">
          <div class="card-head">
            <h2>Book dit gratis rygtjek</h2>
            <span class="step-no" data-two-step>Trin <span id="stepNo">1</span> af 2</span>
          </div>
          <div class="bars" data-two-step><span class="bar on"></span><span class="bar" id="bar2"></span></div>
          <p class="card-intro">Vi ringer dig op inden for 24 timer på hverdage og aftaler et tidspunkt, der passer dig.</p>

          <div class="fields" id="step1">
            <label class="field">Navn<input class="input" name="navn" autocomplete="name" placeholder="Fornavn og efternavn"></label>
            <div class="row2">
              <label class="field">Telefon<input class="input" name="tlf" type="tel" autocomplete="tel" inputmode="tel" placeholder="12 34 56 78"></label>
              <label class="field">Postnummer<input class="input" name="post" autocomplete="postal-code" inputmode="numeric" placeholder="2600"></label>
            </div>
            <div class="pains-wrap" data-one-step hidden>
              <span class="pains-label">Hvad generer dig mest? <span class="opt">(valgfrit)</span></span>
              <div class="pains"></div>
            </div>
            <p class="form-err" id="formErr" hidden>Udfyld navn, telefon (8 cifre) og postnummer (4 cifre).</p>
            <button type="submit" class="btn-primary" id="primary"><span id="primaryLabel">Næste: Vælg dine gener</span><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.6"><path d="M5 12h14M13 6l6 6-6 6"/></svg></button>
          </div>

          <div class="fields step2" id="step2" hidden>
            <div class="pains-wrap">
              <span class="pains-label">Hvad generer dig mest? <span class="opt">(vælg gerne flere)</span></span>
              <div class="pains"></div>
            </div>
            <label class="field">E-mail <span class="opt">(valgfrit – til bekræftelse)</span><input class="input" name="mail" type="email" autocomplete="email" placeholder="navn@mail.dk"></label>
            <label class="field">Bemærkning <span class="opt">(valgfrit)</span><textarea class="input" name="note" placeholder="Fx hvornår du bedst kan kontaktes"></textarea></label>
            <div class="btn-row">
              <button type="button" class="btn-back" id="back">Tilbage</button>
              <button type="submit" class="btn-primary">Send booking</button>
            </div>
          </div>

          <div class="assure">
            <span><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#33b048" stroke-width="3"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>Koster 0 kr.</span>
            <span><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#33b048" stroke-width="3"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>Ingen købsforpligtelse</span>
            <span><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#33b048" stroke-width="3"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>Hele landet</span>
          </div>
        </div>
        <div class="card-foot">
          <span>Hellere tale med os nu?</span>
          <a href="tel:+4578155703">Ring 78 15 57 03</a>
        </div>
      </form>
      <div class="thanks" id="thanks" hidden>
        <div class="thanks-ico"><svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg></div>
        <h2>Tak, <span id="firstName"></span>!</h2>
        <p>Din booking er modtaget. Vi ringer til dig på <b id="thanksTlf"></b> inden for 24 timer på hverdage for at aftale tid.</p>
        <button type="button" class="link-btn" id="reset">Lav en ny booking</button>
      </div>
    </div>
    <div class="simple">
      <h3>Så <span class="green">simpelt</span> er det</h3>
      <ol>
        <li><b>1.</b>Bestil ergonomisk konsultation</li>
        <li><b>2.</b>Lad madrassen vælge dig</li>
        <li><b>3.</b>Modtag den perfekte madras</li>
        <li><b>4.</b>Sov helt perfekt <span class="zzz" aria-hidden="true">z<sup>z</sup><sup>z</sup></span></li>
      </ol>
    </div>
    </div>
  </div>
</section>

<section class="stats" data-section="stats">
  <div class="wrap">
    <div><div class="stat-n">1 ud af 5</div><div class="stat-l">danskere oplever problemer med søvnløshed</div></div>
    <div><div class="stat-n">10 %</div><div class="stat-l">af befolkningen har kroniske søvnproblemer</div></div>
    <p>Dårlig søvn påvirker energi og koncentration – og den forkerte sovestilling kan føre til smerter i nakke, ryg og lænd. Ofte starter løsningen med madrassen.</p>
  </div>
</section>

<section class="steps">
  <div class="wrap">
    <div class="sec-head">
      <div><div class="eyebrow">SÅDAN FOREGÅR DET</div><h2 class="h2" style="max-width:640px">Fire trin fra ondt i ryggen til en bedre nattesøvn</h2></div>
      <a href="#book" class="btn-dark" data-to-form>Book gratis rygtjek</a>
    </div>
    <div class="steps-grid">
      <div class="steps-line"></div>
      <div class="step"><div class="step-n">1</div><h3>Vi kommer på besøg</h3><p>Carelax Danmark kommer til dig, uanset hvor i landet, og taler om dine behov.</p></div>
      <div class="step"><div class="step-n">2</div><h3>Scanning af ryggen</h3><p>Vi foretager en liggende scanning af ryggen og vurderer den madras, du sover på.</p></div>
      <div class="step"><div class="step-n">3</div><h3>Dybdegående samtale</h3><p>Vi gennemgår resultatet og taler om, hvordan du bedst forbedrer din søvn.</p></div>
      <div class="step"><div class="step-n">4</div><h3>Sov allerede bedre</h3><p>Med vores analyse og gode råd kan du allerede fra første nat begynde at sove bedre.</p></div>
    </div>
  </div>
</section>

<section class="video" id="videoSection" hidden>
  <div class="wrap">
    <div class="video-copy">
      <div class="eyebrow">MØD SIMON – FYSIOTERAPEUT</div>
      <h2 class="h2">Hør hvad der sker til et rygtjek</h2>
      <p class="video-lead">Simon laver fysioterapeutiske rygtjek, så madrassen bliver tilpasset dig og ikke bare er en standard.</p>
      <blockquote class="video-quote">"Næsten alle siger, at deres ryg er fin. Men du kan ikke mærke, hvordan din ryg ligger, mens du sover. Det kan vi se på en 3D-scanning. Og er din ryg fin, har du fået det bekræftet gratis."<cite>Simon, fysioterapeut hos Carelax Danmark</cite></blockquote>
      <a href="#book" class="btn-dark" data-to-form>Book gratis rygtjek</a>
    </div>
    <div class="video-frame" id="videoFrame"></div>
  </div>
</section>

<section class="benefits">
  <div class="wrap">
    <div class="photo">
      <!-- Læg et foto i {{RYGTJEK_ASSETS}}rygtjek-specialist.jpg, så vises det automatisk her -->
      <div class="photo-slot" id="photoSlot">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="M21 17l-5-5-9 8"/></svg>
        <span>Foto: specialist der scanner en ryg hjemme hos en kunde</span>
      </div>
      <div class="photo-badge">
        <div class="photo-badge-ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#33b048" stroke-width="2.4"><path d="M12 21s-7-5.5-7-11a7 7 0 0 1 14 0c0 5.5-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg></div>
        <div><b>Vi kommer til dig</b><span>Uanset hvor i landet du bor</span></div>
      </div>
    </div>
    <div>
      <div class="eyebrow">DET FÅR DU – HELT GRATIS</div>
      <h2 class="h2">Et personligt rygtjek med konkrete råd</h2>
      <div class="ben-list">
        <div class="ben"><span class="ben-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#33b048" stroke-width="3"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg></span><div><b>Besøg af en specialist</b><p>En af vores førende specialister i ergonomi og søvn kommer ud til dig – du sparer transporttiden.</p></div></div>
        <div class="ben"><span class="ben-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#33b048" stroke-width="3"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg></span><div><b>Grundig analyse af din ryg</b><p>Vi undersøger årsagerne til eventuelle smerter i nakke, ryg og lænd.</p></div></div>
        <div class="ben"><span class="ben-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#33b048" stroke-width="3"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg></span><div><b>Vurdering af din madras</b><p>Vi ser på, om din nuværende madras og sovestilling understøtter din krop.</p></div></div>
        <div class="ben"><span class="ben-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#33b048" stroke-width="3"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg></span><div><b>Konkrete råd til bedre søvn</b><p>En dybdegående samtale om, hvordan du bliver fri for smerter og søvnløshed.</p></div></div>
      </div>
    </div>
  </div>
</section>

<section class="reviews">
  <div class="wrap">
    <div class="eyebrow">551 ANMELDELSER PÅ TRUSTPILOT</div>
    <h2 class="h2">Kunderne giver os 4,9 ud af 5</h2>
    <div class="tp-live" id="tpLive" hidden></div>
    <div class="rv" id="tpFallback">
      <div class="rv-track" id="rvTrack"></div>
    </div>
    <a class="rv-all" href="https://dk.trustpilot.com/review/carelaxdanmark.dk" target="_blank" rel="noopener">Læs alle 551 anmeldelser på Trustpilot →</a>
  </div>
</section>

<section class="press" data-section="press">
  <div class="wrap">
    <div class="sec-head">
      <div><div class="eyebrow">KENDT FRA SUND FORSKNING</div><h2 class="h2">Vores eksperter skriver om ryg, søvn og madrasser</h2></div>
      <div class="press-nav">
        <button type="button" class="round-btn" data-press="-1" aria-label="Forrige">←</button>
        <button type="button" class="round-btn" data-press="1" aria-label="Næste">→</button>
      </div>
    </div>
    <div class="press-track" id="pressTrack"></div>
  </div>
</section>

<section class="faq">
  <div class="wrap">
    <div><div class="eyebrow">SPØRGSMÅL &amp; SVAR</div><h2 class="h2">Det spørger folk typisk om</h2><p class="faq-intro">Fandt du ikke svaret? Ring på <a href="tel:+4578155703">78 15 57 03</a>.</p></div>
    <div class="faq-list" id="faqList"></div>
  </div>
</section>

<section class="final">
  <div class="wrap">
    <div><h2>Klar til en bedre nattesøvn?</h2><p>Det tager 30 sekunder at booke. Vi ringer og aftaler resten.</p></div>
    <div class="final-r">
      <a href="tel:+4578155703" class="final-tel">eller ring 78 15 57 03</a>
      <a href="#book" class="btn-big" data-to-form>Book gratis rygtjek</a>
    </div>
  </div>
</section>

<div class="usp-bar"><div class="wrap"><div class="usp"><img src="{{RYGTJEK_ASSETS}}rygtjek-usp0.png" alt="">Rentefrit køb</div><div class="usp"><img src="{{RYGTJEK_ASSETS}}rygtjek-usp1.png" alt="">Høj kvalitet</div><div class="usp"><img src="{{RYGTJEK_ASSETS}}rygtjek-usp2.png" alt="">Prisgaranti</div><div class="usp"><img src="{{RYGTJEK_ASSETS}}rygtjek-usp3.png" alt="">Vi leverer</div><div class="usp"><img src="{{RYGTJEK_ASSETS}}rygtjek-usp4.png" alt="">Byt til nyt</div></div></div>
<footer class="footer">
  <div class="wrap footer-grid">
    <div>
      <h4>Carelax Danmark ApS</h4>
      <div class="f-contact"><div class="f-row"><svg width="13" height="13" viewBox="0 0 24 24" fill="#fff"><path d="M12 3 2 12h3v8h5v-6h4v6h5v-8h3z"/></svg><span>Naverland 25, 2600 Glostrup</span></div><div class="f-row"><svg width="13" height="13" viewBox="0 0 24 24" fill="#fff"><path d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1A17 17 0 0 1 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1z"/></svg><span><a href="tel:+4578155703">+45 78 15 57 03</a></span></div><div class="f-row"><svg width="13" height="13" viewBox="0 0 24 24" fill="#fff"><path d="M12 2a10 10 0 1 0 5 18.7l-1-1.7A8 8 0 1 1 20 12v1.5a1.5 1.5 0 0 1-3 0V12a5 5 0 1 0-1.5 3.5A3.5 3.5 0 0 0 22 13.5V12A10 10 0 0 0 12 2zm0 13a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/></svg><span><a href="mailto:info@carelaxdanmark.dk">Skriv til os her</a></span></div><div class="f-row"><svg width="13" height="13" viewBox="0 0 24 24" fill="#fff"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm1 15h-2v-6h2zm0-8h-2V7h2z"/></svg><span>CVR: 38219499</span></div></div>
      <div class="f-social"><img src="{{RYGTJEK_ASSETS}}rygtjek-f-social.png" alt=""><a href="https://www.facebook.com/CarelaxDk/" target="_blank" rel="noopener" aria-label="Carelax på Facebook"></a><a href="https://www.instagram.com/carelaxdk/" target="_blank" rel="noopener" aria-label="Carelax på Instagram"></a></div>
    </div>
    <div>
      <h4>Information</h4>
      <div class="f-links"><a href="https://carelaxdanmark.dk/om-os/"><span class="f-arrow">›</span> Om Carelax Danmark</a><a href="https://carelaxdanmark.dk/kontakt/"><span class="f-arrow">›</span> Kontakt os</a><a href="https://carelaxdanmark.dk/handelsbetingelser/"><span class="f-arrow">›</span> Handelsbetingelser</a><a href="https://carelaxdanmark.dk/handelsbetingelser/#fortrydelsesret"><span class="f-arrow">›</span> Fortrydelsesret</a><a href="https://carelaxdanmark.dk/privatlivspolitik/"><span class="f-arrow">›</span> Privatlivspolitik</a></div>
    </div>
    <div>
      <h4>Populære produkter</h4>
      <div class="f-links"><a href="https://carelaxdanmark.dk/shop/carelax-stratus/"><span class="f-arrow">›</span> Carelax Stratus</a><a href="https://carelaxdanmark.dk/shop/carelax-infinity-pro/"><span class="f-arrow">›</span> Carelax Infinity Pro</a><a href="https://carelaxdanmark.dk/shop/massagepude-relax/"><span class="f-arrow">›</span> Massagepude Relax</a><a href="https://carelaxdanmark.dk/shop/careconcept-7-zoner/"><span class="f-arrow">›</span> CareConcept™ 7 zoner</a></div>
    </div>
    <div>
      <h4>Tilmeld nyhedsbrev</h4>
      <form class="f-news" onsubmit="return false"><input type="email" placeholder="Indtast din email" aria-label="Email"><div><button type="submit">TILMELD NYHEDSBREV</button></div></form>
    </div>
    <div class="f-badges">
      <a href="https://borsen.dk/gazelle/" target="_blank" rel="noopener" class="f-gazelle-link"><img src="https://carelaxdanmark.dk/wp-content/uploads/gazeller.svg" class="f-gazelle" alt="Børsen Gazelle"></a>
      <a href="https://dk.trustpilot.com/review/carelaxdanmark.dk" target="_blank" rel="noopener" class="f-tp"><img src="{{RYGTJEK_ASSETS}}rygtjek-f-tp.png" alt="Trustpilot – TrustScore 4,9, 551 anmeldelser"></a>
    </div>
  </div>
  <div class="footer-bottom">
    <img src="https://carelaxdanmark.dk/wp-content/uploads/carelax-betaling-any.png" class="f-pay" alt="Betalingsmetoder">
    <p>Copyright 2026 © <b>Carelax Danmark ApS</b> | Alle rettigheder forbeholdes.</p>
  </div>
</footer>
</div>
RYGTJEK_BODY_END;

		$js = <<<'RYGTJEK_JS_END'
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
  // Video med Simon: mp4-fil (fx {{RYGTJEK_ASSETS}}rygtjek-simon.mp4) eller et YouTube/Vimeo-link.
  var VIDEO = { src: '{{RYGTJEK_ASSETS}}rygtjek-simon.mp4', poster: '{{RYGTJEK_ASSETS}}rygtjek-simon-poster.webp' };

  // Udvalgte seng- og rygrelaterede anmeldelser fra Trustpilot (vises indtil TrustBox er sat op)
  var REVIEWS = [
    { name: 'Bente Kristiansen', date: '22. okt. 2026', title: 'God og konstruktiv oplevelse',
      text: 'Jeg havde besøg af Samuel fra Carelax, der foretog en rygmåling. Denne viste en skæv ryg. Vi blev enige i, at jeg skulle investere i en ny seng med specialmadras. Resultatet er bare fantastisk. Jeg sover igennem om natten uden problemer. I september foretog Samuel en ny scanning af ryggen – min ryg har nu rettet sig fra skæv til normal.' },
    { name: 'Lisette Jespersen', date: '26. okt. 2026', title: 'Grundig information og scanning',
      text: 'Grundig information og scanning af rygsøjle gjorde mig tryg ved at investere i god madras.' },
    { name: 'Dan Madsen', date: '22. okt. 2026', title: 'Bedste investering',
      text: 'Det er den bedste investering, jeg har gjort i mange år. Er kun ked af, jeg ikke har købt den madras tidligere.' }
  ];

  var PAINS = ['Ondt i ryggen', 'Nakke & skuldre', 'Lænd', 'Søvnløshed', 'Gammel madras'];
  var FAQS = [
    ['Er rygtjekket virkelig gratis?', 'Ja. Rygtjekket er helt gratis og uforpligtende. Du betaler intet for besøget, scanningen eller rådgivningen.'],
    ['Skal jeg købe noget bagefter?', 'Nej. Du får vores vurdering og konkrete råd til en bedre nattesøvn. Om du vil handle noget, er helt op til dig.'],
    ['Hvor foregår rygtjekket?', 'Hjemme hos dig. Vi kommer ud, uanset hvor i landet du bor, så du sparer transporttiden og vi kan vurdere den madras, du sover på.'],
    ['Hvad sker der, når jeg har booket?', 'Vi ringer dig op inden for 24 timer på hverdage og aftaler et tidspunkt, der passer dig.']
  ];
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
  $$('[data-headline]').forEach(function (h) { h.hidden = h.dataset.headline !== cfg.headline; });
  if (!cfg.stats) $('[data-section="stats"]').remove();
  if (!cfg.press) $('[data-section="press"]').remove();
  $$('[data-two-step]').forEach(function (n) { n.hidden = !cfg.twoStep; });
  $$('[data-one-step]').forEach(function (n) { n.hidden = cfg.twoStep; });
  $('#primaryLabel').textContent = cfg.twoStep ? 'Næste: Vælg dine gener' : 'Book gratis rygtjek';

  // Specialistfoto (vises hvis {{RYGTJEK_ASSETS}}rygtjek-specialist.jpg findes)
  var photo = new Image();
  photo.onload = function () { var s = $('#photoSlot'); s.innerHTML = ''; s.appendChild(photo); };
  photo.alt = 'Specialist scanner en ryg hjemme hos en kunde';
  photo.src = '{{RYGTJEK_ASSETS}}rygtjek-specialist.jpg';

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
    set.forEach(function (r, i) { track.appendChild(card(r, false)); });
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
      // Videoen har indbrændte undertekster, så den starter uden lyd og kører i ring
      var v = document.createElement('video');
      v.src = VIDEO.src; v.muted = true; v.loop = true; v.playsInline = true; v.preload = 'metadata';
      v.setAttribute('playsinline', ''); v.setAttribute('aria-label', 'Simon fortæller om rygtjekket');
      if (VIDEO.poster) v.poster = VIDEO.poster;
      var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      if (reduce) v.controls = true; else v.autoplay = true;
      frame.appendChild(v);
      var snd = el('button', 'vid-sound', 'Slå lyd til');
      snd.type = 'button';
      snd.setAttribute('aria-pressed', 'false');
      snd.addEventListener('click', function () {
        v.muted = !v.muted;
        if (!v.muted) { v.currentTime = 0; v.play(); }
        snd.setAttribute('aria-pressed', !v.muted);
        snd.textContent = v.muted ? 'Slå lyd til' : 'Slå lyd fra';
      });
      frame.appendChild(snd);
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
      var a = el('a', 'mag'); a.href = m[2]; a.target = '_blank'; a.rel = 'noopener';
      var img = el('img'); img.src = '{{RYGTJEK_ASSETS}}rygtjek-mag' + i + '.webp'; img.alt = ''; img.loading = 'lazy';
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
RYGTJEK_JS_END;

		// Kun på WordPress: plugins lægger markup i wp_footer, men deres CSS er fjernet ovenfor.
		// Indkøbskurven (Side Cart) skjules, da siden ikke har en kurv.
		$wp_css = '.xoo-wsc-markup,.xoo-wsc-markup-notices{display:none!important}'
			. '.screen-reader-text{border:0;clip:rect(1px,1px,1px,1px);clip-path:inset(50%);height:1px;margin:-1px;overflow:hidden;padding:0;position:absolute!important;width:1px;word-wrap:normal!important}';

		$css  = str_replace( '{{RYGTJEK_ASSETS}}', $assets, $css );
		$body = str_replace( '{{RYGTJEK_ASSETS}}', $assets, $body );
		$js   = str_replace( '{{RYGTJEK_ASSETS}}', $assets, $js );

		status_header( 200 );
		nocache_headers();
		?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php if ( ! current_theme_supports( 'title-tag' ) ) : ?><title><?php echo esc_html( wp_get_document_title() ); ?></title><?php endif; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700;800&family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">
<?php wp_head(); ?>
<style id="carelax-rygtjek-css">
<?php echo $css; // phpcs:ignore WordPress.Security.EscapeOutput ?>
<?php echo $wp_css; // phpcs:ignore WordPress.Security.EscapeOutput ?>
</style>
</head>
<body class="carelax-rygtjek">
<?php
		if ( function_exists( 'wp_body_open' ) ) {
			wp_body_open();
		}
		echo $body; // phpcs:ignore WordPress.Security.EscapeOutput
?>
<script id="carelax-rygtjek-js">
<?php echo $js; // phpcs:ignore WordPress.Security.EscapeOutput ?>
</script>
<?php wp_footer(); ?>
</body>
</html>
<?php
		exit;
	} );
}
