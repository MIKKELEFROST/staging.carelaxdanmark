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

	// 3) Bookinger fra formularen sendes som mail – samme opsætning som den gamle rygtjek-formular
	//    (Contact Form 7 "Ergonomisk rygtjek", ID 1883). Modtagere og afsender hentes fra den formular,
	//    så de stadig styres under Kontakt → Ergonomisk rygtjek → Mail. Mailen logges af WP Mail Catcher.
	if ( ! defined( 'CARELAX_RYGTJEK_CF7_FORM' ) ) {
		define( 'CARELAX_RYGTJEK_CF7_FORM', 1883 );
	}

	if ( ! function_exists( 'carelax_rygtjek_mail_settings' ) ) {
		function carelax_rygtjek_mail_settings() {
			$to     = array( 'info@carelaxdanmark.dk', 'sam@carelaxdanmark.dk' );
			$sender = 'Carelax Danmark <wordpress@carelaxdanmark.dk>';
			if ( class_exists( 'WPCF7_ContactForm' ) ) {
				$cf7 = WPCF7_ContactForm::get_instance( CARELAX_RYGTJEK_CF7_FORM );
				if ( $cf7 ) {
					$mail = (array) $cf7->prop( 'mail' );
					$list = array_values( array_filter( array_map( 'trim', explode( ',', isset( $mail['recipient'] ) ? (string) $mail['recipient'] : '' ) ), 'is_email' ) );
					if ( $list ) {
						$to = $list;
					}
					if ( ! empty( $mail['sender'] ) && false === strpos( $mail['sender'], '[' ) ) {
						$sender = $mail['sender'];
					}
				}
			}
			return array( 'to' => $to, 'sender' => $sender );
		}
	}

	if ( ! function_exists( 'carelax_rygtjek_booking' ) ) {
		function carelax_rygtjek_booking( $request ) {
			$p  = (array) $request->get_json_params();
			$ok = new WP_REST_Response( array( 'ok' => true ), 200 );

			// Spam: skjult felt udfyldt eller sendt urealistisk hurtigt → svar "ok", men send intet.
			if ( ! empty( $p['website'] ) || ( isset( $p['t'] ) && (int) $p['t'] < 2500 ) ) {
				return $ok;
			}

			// "booking" = trin 1 (navn, telefon, postnummer, tjekliste), "extra" = valgfrit trin 2.
			$type  = ( isset( $p['type'] ) && 'extra' === $p['type'] ) ? 'extra' : 'booking';
			$navn  = sanitize_text_field( isset( $p['navn'] ) ? (string) $p['navn'] : '' );
			$tlf   = preg_replace( '/\D/', '', isset( $p['telefon'] ) ? (string) $p['telefon'] : '' );
			$post  = preg_replace( '/\D/', '', isset( $p['postnummer'] ) ? (string) $p['postnummer'] : '' );
			$email = sanitize_email( isset( $p['email'] ) ? (string) $p['email'] : '' );
			$note  = sanitize_textarea_field( isset( $p['bemaerkning'] ) ? (string) $p['bemaerkning'] : '' );
			$side  = esc_url_raw( isset( $p['side'] ) ? (string) $p['side'] : '' );
			// Kun kendte valg accepteres (samme lister som i app.js).
			$pick = function ( $key, $allowed ) use ( $p ) {
				return array_values( array_intersect( $allowed, array_map( 'strval', isset( $p[ $key ] ) ? (array) $p[ $key ] : array() ) ) );
			};
			$symptomer = $pick( 'symptomer', array( 'Stiv i lænden om morgenen', 'Ondt i nakke eller skuldre', 'Du sover på armen eller skulderen', 'Du vender dig hele natten', 'Madrassen er over 8–10 år gammel', 'Du vågner træt' ) );
			$gener     = $pick( 'gener', array( 'Ondt i ryggen', 'Nakke og skuldre', 'Lænd', 'Søvnløshed', 'Gammel madras' ) );

			if ( mb_strlen( $navn ) < 2 || mb_strlen( $navn ) > 100 || 8 !== strlen( $tlf ) || 4 !== strlen( $post ) ) {
				return new WP_REST_Response( array( 'ok' => false, 'error' => 'invalid' ), 400 );
			}
			if ( ! is_email( $email ) ) {
				$email = '';
			}
			$note = mb_substr( $note, 0, 2000 );
			// Ekstra oplysninger uden indhold: intet at sende.
			if ( 'extra' === $type && ! $gener && ! $email && '' === $note ) {
				return $ok;
			}

			// Højst 20 bookinger pr. IP-adresse pr. 10 minutter.
			$key = 'carelax_rygtjek_' . md5( isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '' );
			$n   = (int) get_transient( $key );
			if ( $n >= 20 ) {
				return new WP_REST_Response( array( 'ok' => false, 'error' => 'rate' ), 429 );
			}
			set_transient( $key, $n + 1, 10 * MINUTE_IN_SECONDS );

			$body = 'Fra: ' . $navn . "\n"
				. ( $email ? 'Email: ' . $email . "\n" : '' )
				. 'Telefon: ' . implode( ' ', str_split( $tlf, 2 ) ) . "\n"
				. 'Post-nr: ' . $post . "\n\n";
			if ( 'booking' === $type ) {
				$subject = 'Booking af gratis rygtjek';
				$body   .= 'Kender du det? (tjekliste): ' . ( $symptomer ? implode( ', ', $symptomer ) : '(intet valgt)' ) . "\n\n"
					. "Kunden kan tilføje gener, e-mail og en bemærkning bagefter. De kommer i så fald i en separat mail.\n\n";
			} else {
				$subject = 'Booking af gratis rygtjek – ekstra oplysninger';
				$body   .= 'Gener: ' . ( $gener ? implode( ', ', $gener ) : '(ingen valgt)' ) . "\n\n"
					. "Evt. bemærkning:\n" . ( '' !== $note ? $note : '(ingen)' ) . "\n\n"
					. "Ekstra oplysninger til en booking, der allerede er sendt.\n\n";
			}
			$body .= "-- \n"
				. 'Denne e-mail er afsendt fra rygtjek-landingssiden på ' . get_bloginfo( 'name' ) . "\n"
				. 'Side: ' . ( $side ? $side : home_url( '/' ) );

			$s       = carelax_rygtjek_mail_settings();
			$headers = array( 'From: ' . $s['sender'] );
			if ( $email ) {
				$headers[] = 'Reply-To: ' . $email;
			}
			if ( ! wp_mail( $s['to'], $subject, $body, $headers ) ) {
				return new WP_REST_Response( array( 'ok' => false, 'error' => 'mail' ), 500 );
			}
			return $ok;
		}
	}

	add_action( 'rest_api_init', function () {
		register_rest_route( 'carelax/v1', '/rygtjek', array(
			'methods'             => 'POST',
			'callback'            => 'carelax_rygtjek_booking',
			'permission_callback' => '__return_true',
		) );
	} );

	// 4) Udskriv landingssiden. wp_head()/wp_footer() bevares, så SEO (Rank Math),
	//    cookie-samtykke, Google Tag Manager og pixels virker som på resten af sitet.
	add_action( 'template_redirect', function () {
		if ( ! carelax_rygtjek_is_active() ) {
			return;
		}
		$assets = carelax_rygtjek_assets_url();

		$css = <<<'RYGTJEK_CSS_END'
:root{--ink:#1f2a24;--muted:#5a645e;--line:#e7e2d9;--cream:#faf8f4;--sand:#f3efe8;--mint:#e9f5eb;--green:#33b048;--green-d:#2b9a3e;--green-dd:#23803a;--mint-b:#cbe7d0;--err:#c0392b}
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
.nav a{white-space:nowrap}
.nav-active{border:2px solid #33b048;border-radius:3px;padding:6px 11px;font-weight:700}
.nav-tel{display:flex;align-items:center;gap:8px;border-left:1px solid var(--line);padding-left:22px}
/* Dropdowns i menuen (som på carelaxdanmark.dk) */
.nav-item{position:relative}
.sub-toggle{background:none;border:0;padding:0;margin:0 0 0 5px;cursor:pointer;color:inherit;display:inline-flex;align-items:center;justify-content:center}
.sub-toggle svg{transition:transform .2s}
.sub{display:none}
@media (min-width:1121px){
  .nav-item{display:flex;align-items:center;height:80px}
  .nav-item:hover>a,.nav-item:hover>.sub-toggle,.nav-item:focus-within>a,.nav-item:focus-within>.sub-toggle{color:#33b048}
  .sub{position:absolute;top:100%;left:0;z-index:60;min-width:250px;flex-direction:column;background:#fff;border-top:4px solid #33b048;box-shadow:0 18px 40px rgba(31,42,36,.14);padding:6px 0}
  .nav-item:hover>.sub,.nav-item:focus-within>.sub,.nav-item.open>.sub{display:flex}
  .nav-item:last-of-type .sub{left:auto;right:0}
  .sub a{padding:11px 20px;font-weight:400;font-size:14.5px;color:var(--muted);white-space:nowrap}
  .sub a:hover,.sub a:focus-visible{color:#33b048;background:var(--cream)}
}

/* Fælles (v2) */
.green{color:#33b048}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;background:var(--green);color:#fff;font-weight:800;font-size:16px;border:0;border-radius:8px;padding:15px 26px;cursor:pointer;text-decoration:none;box-shadow:0 8px 20px rgba(51,176,72,.3)}
.btn:hover{background:var(--green-d);color:#fff}
.btn:disabled{opacity:.7;cursor:wait}
.btn:focus-visible,.input:focus-visible,.sym:focus-visible,.chip:focus-visible,.faq summary:focus-visible{outline:3px solid #9fd8aa;outline-offset:2px}
.lead{margin-top:16px;font-size:17px;line-height:1.7;color:var(--muted);max-width:62ch}
.lead a{color:var(--ink)}
.sec{padding:100px 0;background:#fff}
.sec.alt{background:var(--sand)}
.sec-intro{max-width:760px}
.stars{display:inline-flex;gap:2px}
.stars i{width:16px;height:16px;background:#00b67a;display:flex;align-items:center;justify-content:center}
.stars i::after{content:"";width:10px;height:10px;background:#fff;clip-path:polygon(50% 0,61% 35%,98% 35%,68% 57%,79% 91%,50% 70%,21% 91%,32% 57%,2% 35%,39% 35%)}

/* Hero (v2) */
.hero{background:radial-gradient(ellipse at 12% 0%,#e3f2e5 0%,rgba(250,248,244,0) 55%),radial-gradient(ellipse at 100% 100%,#f1ebe1 0%,rgba(250,248,244,0) 50%),var(--cream);color:var(--ink)}
.hero .wrap{padding-top:64px;padding-bottom:80px;display:grid;grid-template-columns:minmax(0,1fr) 460px;gap:64px;align-items:start}
.pill{display:inline-flex;align-items:center;gap:10px;border:1px solid var(--mint-b);background:#fff;color:var(--green-dd);font-size:13px;font-weight:700;padding:7px 14px;border-radius:30px}
.pill-dot{width:8px;height:8px;border-radius:50%;background:var(--green)}
.hero h1{margin:22px 0 0;font-family:Raleway,sans-serif;font-weight:800;font-size:56px;line-height:1.08;letter-spacing:-.5px;text-wrap:balance}
.hero-lead{margin-top:20px;font-size:18px;line-height:1.7;color:var(--muted);max-width:580px;text-wrap:pretty}
.facts{display:flex;flex-wrap:wrap;gap:10px;margin-top:26px}
.fact{display:flex;align-items:center;gap:8px;background:#fff;border:1px solid var(--line);border-radius:10px;padding:10px 14px;font-weight:700;font-size:15px}
.fact svg{color:var(--green);flex:none}
.honest{margin-top:22px;display:flex;gap:12px;align-items:flex-start;color:var(--muted);font-size:15px;line-height:1.6;max-width:560px}
.honest svg{flex:none;color:var(--green);margin-top:3px}
.trust{margin-top:32px;padding-top:24px;border-top:1px solid var(--line);display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:20px}
.trust-item{display:flex;flex-direction:column;gap:2px}
.trust-item b{font-size:14.5px;line-height:1.4}
.trust-item span{font-size:13px;color:#6f7772}
.trust-item .stars{margin-bottom:6px}
.trust-item .stars i{width:15px;height:15px}
.trust-item .stars i::after{width:9px;height:9px}
a.trust-item:hover b{color:var(--green-d)}

/* Formular (v2): trin 1 sender bookingen, trin 2 er valgfrit */
.card{background:#fff;color:var(--ink);border-radius:16px;border:1px solid var(--line);box-shadow:0 30px 70px rgba(31,42,36,.14);overflow:hidden}
.card-body{padding:28px 30px 24px}
.card h2{margin:0;font-family:Raleway,sans-serif;font-weight:800;font-size:25px;line-height:1.2}
.card-intro{margin-top:8px;font-size:14.5px;color:var(--muted)}
.fields{display:flex;flex-direction:column;gap:14px;margin-top:20px}
.field{display:flex;flex-direction:column;gap:6px;font-size:13.5px;font-weight:700}
.opt{font-weight:400;color:#888}
.row2{display:grid;grid-template-columns:minmax(0,1.3fr) minmax(0,1fr);gap:12px}
.input{width:100%;min-width:0;height:50px;border:1.5px solid #d9d4ca;border-radius:9px;padding:0 14px;font-size:16px;outline:none;font-weight:400;color:var(--ink);background:#fff}
textarea.input{height:72px;padding:12px 14px;resize:none}
.input:focus{border-color:var(--green)}
.input.invalid{border-color:var(--err)}
.hint{font-size:12.5px;color:var(--muted);margin-top:-6px;line-height:1.4}
.symbox{margin-top:16px;background:var(--mint);border:1px solid var(--mint-b);border-radius:10px;padding:12px 14px}
.symbox p{font-size:13.5px;font-weight:700;color:var(--green-dd)}
.symbox ul{list-style:none;margin:8px 0 0;padding:0;display:flex;flex-wrap:wrap;gap:6px}
.symbox li{display:flex;align-items:center;gap:2px;background:#fff;border:1px solid var(--mint-b);border-radius:999px;padding:3px 4px 3px 12px;font-size:13px;font-weight:600}
.symbox li button{border:0;background:none;cursor:pointer;color:var(--muted);font-size:17px;line-height:1;width:24px;height:24px;border-radius:50%}
.symbox li button:hover{background:var(--sand)}
.form-err{margin-top:12px;font-size:13.5px;color:var(--err);font-weight:600}
.form-err a{text-decoration:underline;white-space:nowrap}
.hp{position:absolute!important;left:-9999px;width:1px;height:1px;overflow:hidden}
.btn-primary{width:100%;height:56px;margin-top:18px;font-size:17px}
.assure{display:flex;flex-wrap:wrap;justify-content:center;gap:6px 16px;margin-top:14px;font-size:12.5px;color:#555;font-weight:600}
.assure span{display:flex;align-items:center;gap:5px}
.assure svg{color:var(--green)}
.privacy{margin-top:12px;font-size:12px;color:var(--muted);text-align:center;line-height:1.45}
.privacy a{text-decoration:underline}
.done-ico{width:52px;height:52px;border-radius:50%;background:var(--mint);color:var(--green);display:flex;align-items:center;justify-content:center;margin-bottom:14px}
.extra-q{margin-top:20px;font-weight:700;font-size:15px}
.pains{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px}
.chip{border:1.5px solid #d9d4ca;background:#fff;color:var(--ink);font-size:13.5px;font-weight:600;padding:8px 14px;border-radius:30px;cursor:pointer}
.chip[aria-pressed="true"]{border-color:var(--green);background:var(--mint);color:var(--green-dd)}
.link-btn{margin-top:14px;border:0;background:none;color:var(--green-d);font-weight:700;font-size:14px;cursor:pointer;padding:0}
.card-foot{background:var(--sand);border-top:1px solid #ece7de;padding:14px 30px;display:flex;align-items:center;justify-content:space-between;gap:10px;font-size:14px;color:var(--muted)}
.card-foot a{font-weight:800;font-size:16px;color:var(--ink)}

/* Sådan foregår det + Simon (v2) */
.split{display:grid;grid-template-columns:minmax(0,5fr) minmax(0,6fr);gap:64px;align-items:start;margin-top:44px}
.person{background:#fff;border:1px solid var(--line);border-radius:18px;overflow:hidden;position:sticky;top:88px;box-shadow:0 20px 50px rgba(31,42,36,.08)}
.vid{position:relative;background:var(--ink);display:flex;justify-content:center}
.vid video{display:block;width:100%;max-width:380px;aspect-ratio:9/16;object-fit:cover;background:var(--ink)}
.vid-sound{position:absolute;left:16px;bottom:16px;display:flex;align-items:center;gap:8px;border:0;border-radius:30px;padding:9px 14px 9px 12px;background:rgba(255,255,255,.92);color:var(--ink);font-weight:700;font-size:13px;cursor:pointer;box-shadow:0 6px 18px rgba(0,0,0,.2)}
.vid-sound:hover{background:#fff}
.person-in{padding:24px 26px 26px}
.person h3{margin:0;font-family:Raleway,sans-serif;font-weight:800;font-size:24px}
.role{color:var(--green-dd);font-weight:700;font-size:14px;margin-top:4px}
.person-txt{margin-top:10px;color:var(--muted);font-size:15px}
.person blockquote{margin:16px 0 0;padding-left:16px;border-left:3px solid var(--green);color:var(--ink);font-style:italic;font-size:15.5px;line-height:1.65}
.btn-block{width:100%;margin-top:20px}
.steps{list-style:none;margin:0;padding:0}
.step{display:grid;grid-template-columns:52px minmax(0,1fr);gap:20px;padding-bottom:34px;position:relative}
.step:last-child{padding-bottom:0}
.step:not(:last-child)::before{content:"";position:absolute;left:25px;top:56px;bottom:6px;border-left:2px dashed #cfe6d3}
.num{width:52px;height:52px;border-radius:50%;border:2px solid var(--green);color:var(--green);display:flex;align-items:center;justify-content:center;font-family:Raleway,sans-serif;font-weight:800;font-size:20px;background:#fff}
.step h3{margin:10px 0 0;font-family:Raleway,sans-serif;font-weight:800;font-size:21px;line-height:1.25}
.time{display:inline-block;margin-top:8px;font-size:12.5px;font-weight:700;color:var(--green-dd);background:var(--mint);border-radius:999px;padding:3px 11px}
.step p{margin-top:8px;color:var(--muted);font-size:15.5px;line-height:1.7}

/* Kender du det? (v2) */
.symptoms{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-top:36px}
.sym{display:flex;gap:14px;align-items:flex-start;background:#fff;border:1.5px solid var(--line);border-radius:12px;padding:18px 20px;cursor:pointer;text-align:left;font:inherit;color:var(--ink);transition:border-color .15s,background .15s}
.sym:hover{border-color:#cfe6d3}
.sym .box{width:24px;height:24px;border:2px solid #cfc9bd;border-radius:6px;flex:none;display:flex;align-items:center;justify-content:center;margin-top:1px;background:#fff}
.sym[aria-pressed="true"]{border-color:var(--green);background:var(--mint)}
.sym[aria-pressed="true"] .box{background:var(--green);border-color:var(--green)}
.sym[aria-pressed="true"] .box::after{content:"";width:6px;height:11px;border:solid #fff;border-width:0 2.5px 2.5px 0;transform:rotate(45deg) translate(-1px,-1px)}
.sym b{display:block;font-size:16px;line-height:1.35}
.sym span span{display:block;margin-top:3px;font-size:14px;color:var(--muted)}
.sym-result{margin-top:28px;display:flex;flex-wrap:wrap;align-items:center;gap:16px 28px}
.sym-result p{font-weight:700;max-width:56ch}

/* Skal jeg købe noget? (v2) */
.straight{background:var(--mint);border:1px solid var(--mint-b);border-radius:20px;padding:48px 52px;display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.3fr);gap:48px;align-items:center}
.straight h2{margin:10px 0 0;font-family:Raleway,sans-serif;font-weight:800;font-size:38px;line-height:1.15}
.straight p{font-size:17px;color:#3f4a44;line-height:1.7}
.straight p+p{margin-top:14px}

/* Udvalgte Sund Forskning-artikler (v2) */
.sf-cards{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:20px;margin-top:48px}
.sf-card{display:block;background:#fff;border:1px solid var(--line);border-radius:12px;padding:24px;color:var(--ink);transition:border-color .15s,box-shadow .15s}
.sf-card:hover{color:var(--ink);border-color:var(--mint-b);box-shadow:0 10px 28px rgba(31,42,36,.06)}
.sf-label{display:block;font-family:Raleway,sans-serif;font-weight:800;font-size:13px;letter-spacing:2px;color:#b3261e}
.sf-card h3{margin:12px 0 0;font-family:Raleway,sans-serif;font-weight:800;font-size:18px;line-height:1.35}
.sf-card:hover h3{color:var(--green-d)}
.sf-date{display:block;margin-top:10px;font-size:13px;color:var(--muted)}

/* Børsen Gazelle (v2): gazeller.svg er 188.2×73 med 2022/2023 øverst og 2024/2025 nederst.
   Hver boks viser én række (188.2×29.4). Nederste række starter ved y=43.6, dvs. 43.6/188.2 af bredden. */
.gazelle{margin-top:64px;padding-top:48px;border-top:1px solid var(--line)}
.gazelle h3{margin:10px 0 0;font-family:Raleway,sans-serif;font-weight:800;font-size:28px;line-height:1.2}
.gazelle p{margin-top:12px;color:var(--muted);font-size:16px}
.gz-logos{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:24px 40px;margin-top:32px}
.gz{aspect-ratio:188.2/29.4;overflow:hidden}
.gz img{display:block;width:100%;height:auto}
.gz-b img{margin-top:-23.1668%}

/* FAQ (v2) */
.faq-wrap{display:grid;grid-template-columns:380px minmax(0,1fr);gap:72px}
.faq{border-top:1px solid #e0dacf}
.faq details{border-bottom:1px solid #e0dacf}
.faq summary{list-style:none;cursor:pointer;padding:22px 48px 22px 0;font-weight:700;font-size:18px;position:relative}
.faq summary::-webkit-details-marker{display:none}
.faq summary::after{content:"+";position:absolute;right:0;top:50%;transform:translateY(-50%);width:32px;height:32px;border-radius:50%;background:#fff;color:var(--green);display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:400}
.faq details[open] summary::after{content:"–"}
.faq details p{color:var(--muted);padding:0 48px 22px 0;font-size:15.5px;line-height:1.75}

/* Stats */
.stats{background:#33b048;color:#fff}
.stats .wrap{padding:40px 0;display:grid;grid-template-columns:auto auto 1fr;gap:64px;align-items:center}
.stat-n{font-family:Raleway,sans-serif;font-weight:800;font-size:52px;line-height:1}
.stat-l{margin-top:8px;font-size:14px;font-weight:600}
.stats p{font-size:16px;line-height:1.7;border-left:2px solid rgba(255,255,255,.4);padding-left:32px;text-wrap:pretty}

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
  .hero .wrap{grid-template-columns:minmax(0,1fr) 420px;gap:44px}
  .hero h1{font-size:48px}
  .stats .wrap{padding-left:24px;padding-right:24px;gap:48px}
  .split{gap:44px}
}

/* Header: mobilmenu (hamburger) under 1120px, så menuen med dropdowns aldrig bliver for trang */
@media (max-width:1120px){
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
  .nav{max-height:calc(100vh - 64px);overflow-y:auto}
  .nav-item{display:flex;flex-wrap:wrap;align-items:center;border-bottom:1px solid #f0ece5}
  .nav-item>a{flex:1;border-bottom:0}
  .sub-toggle{width:44px;height:44px;margin:0;border-radius:8px}
  .sub-toggle svg{width:14px;height:14px}
  .nav-item.open>.sub-toggle svg{transform:rotate(180deg)}
  .nav-item.open>.sub{display:flex;flex-direction:column;flex-basis:100%;padding:0 0 10px 14px}
  .nav .sub a{padding:10px 0;border-bottom:0;font-size:15px;font-weight:400;color:var(--muted)}
}

/* Tablet og mobil */
@media (max-width:900px){
  body{font-size:15px}
  .h2{font-size:32px;line-height:1.2}
  .eyebrow{font-size:12px}
  .lead{font-size:16px}

  /* Fast bjælke bliver en bund-bjælke med de to vigtigste handlinger */
  .sticky{top:auto;bottom:0;border-bottom:0;border-top:1px solid var(--line);box-shadow:0 -6px 24px rgba(31,42,36,.1);padding-bottom:env(safe-area-inset-bottom)}
  .sticky .wrap{height:auto;padding-top:10px;padding-bottom:10px}
  .sticky-l{display:none}
  .sticky-r{width:100%;gap:10px}
  .sticky-tel{flex:none;display:flex;align-items:center;justify-content:center;height:48px;padding:0 16px;border:1px solid var(--line);border-radius:6px}
  .sticky-cta{flex:1;text-align:center;height:48px;display:flex;align-items:center;justify-content:center;font-size:15px;border-radius:6px}

  /* Hero: overskrift → tekst → fakta → formular → ærlighed → tillid */
  .hero .wrap{display:flex;flex-direction:column;gap:0;padding-top:32px;padding-bottom:48px}
  .hero-copy{display:contents}
  .pill{order:1;align-self:flex-start;font-size:12px;padding:6px 12px}
  .hero h1{order:2;margin-top:18px;font-size:38px;line-height:1.1;letter-spacing:-.3px}
  .hero-lead{order:3;margin-top:16px;font-size:16px;line-height:1.65}
  .facts{order:4;margin-top:20px}
  .card{order:5;margin-top:26px;width:100%;max-width:560px;align-self:center}
  .honest{order:6;margin-top:28px}
  .trust{order:7;grid-template-columns:1fr 1fr;margin-top:24px}
  .trust-item:first-child{grid-column:1/-1}

  /* Formular */
  .card-body{padding:24px 20px 22px}
  .card h2{font-size:22px}
  .card-foot{padding:14px 20px;flex-wrap:wrap;gap:4px 12px}

  /* Tal */
  .stats .wrap{grid-template-columns:1fr 1fr;gap:28px 24px;padding:36px 24px}
  .stat-n{font-size:40px}
  .stats p{grid-column:1/-1;border-left:0;border-top:2px solid rgba(255,255,255,.4);padding-left:0;padding-top:20px;font-size:15px}

  /* Sektioner generelt */
  .sec,.reviews{padding:64px 0}
  .press{padding:64px 0 72px}
  .final{padding:56px 0}
  .sec-head{flex-direction:column;align-items:flex-start;gap:20px}

  /* Sådan foregår det */
  .split{grid-template-columns:minmax(0,1fr);margin-top:32px}
  .person{position:static;max-width:520px;width:100%;justify-self:center}
  .step h3{font-size:19px}
  .step p{font-size:15px}

  /* Kender du det? + Skal jeg købe noget? */
  .symptoms{grid-template-columns:repeat(2,minmax(0,1fr))}
  .straight{grid-template-columns:minmax(0,1fr);gap:20px;padding:32px 28px}
  .straight h2{font-size:30px}

  /* Anmeldelser */
  .reviews .h2{margin:10px 0 28px;line-height:1.2}

  /* Sund Forskning */
  .press-nav{display:none}
  .press-track{margin:32px -24px 0;padding:0 24px 4px;gap:16px;scroll-snap-type:x mandatory;scroll-padding-left:24px}
  .mag{width:180px;scroll-snap-align:start}
  .mag img{width:180px;height:242px}
  .mag-t{font-size:14px}

  /* Sund Forskning-kort + Børsen Gazelle */
  .sf-cards{grid-template-columns:minmax(0,1fr);gap:12px;margin-top:32px}
  .sf-card{padding:20px}
  .gazelle{margin-top:48px;padding-top:40px}
  .gazelle h3{font-size:22px}
  .gazelle p{font-size:15px}

  /* FAQ */
  .faq-wrap{grid-template-columns:minmax(0,1fr);gap:24px}
  .faq summary{font-size:17px;padding:20px 44px 20px 0}
  .faq details p{padding-right:0;font-size:15px}

  /* Afslutning */
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
  .hero .wrap{padding-top:24px;padding-bottom:40px}
  .hero h1{font-size:32px}
  .h2{font-size:28px}
  .fact{font-size:14px;padding:8px 12px}
  .card{margin-top:24px}
  .card-body{padding:22px 16px 20px}
  .card h2{font-size:21px}
  .card-foot{padding:14px 16px}
  .row2{grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:10px}
  .btn-primary{font-size:16px}
  .trust{grid-template-columns:minmax(0,1fr);gap:12px}
  .stat-n{font-size:34px}
  .stats .wrap{gap:24px 16px;padding-left:16px;padding-right:16px}
  .person-in{padding:20px}
  .symptoms{grid-template-columns:minmax(0,1fr)}
  .sym{padding:16px}
  .sym-result .btn{width:100%}
  .straight{padding:26px 20px}
  .straight h2{font-size:26px}
  .press-track{margin-left:-16px;margin-right:-16px;padding-left:16px;padding-right:16px;scroll-padding-left:16px}
  .gz-logos{grid-template-columns:minmax(0,1fr);max-width:320px}
  .final h2{font-size:28px}
  .sticky .wrap{padding-left:12px;padding-right:12px}
}

/* Trustpilot live-widget */
.tp-live{width:100%;min-height:240px}
@media (max-width:900px){.tp-live{min-height:300px}}

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
    <div class="sticky-r"><a href="tel:+4530554554" class="sticky-tel">30 554 554</a><a href="#book" class="sticky-cta" data-to-form>Book gratis rygtjek</a></div>
  </div>
</div>

<header class="header">
  <div class="wrap">
    <a href="https://carelaxdanmark.dk/" class="logo-link"><img class="logo" src="https://carelaxdanmark.dk/wp-content/uploads/carelaxdanmark-1-1.svg" alt="Carelax Danmark"></a>
    <!-- Menuen svarer til hovedmenuen på carelaxdanmark.dk -->
    <nav class="nav" aria-label="Hovedmenu">
      <div class="nav-item"><a href="https://carelaxdanmark.dk/">Massagestole</a><button type="button" class="sub-toggle" aria-expanded="false" aria-label="Vis undermenu: Massagestole"><svg width="10" height="10" viewBox="0 0 10 10" aria-hidden="true"><path d="M2 3.5 5 6.5 8 3.5" fill="none" stroke="currentColor" stroke-width="1.6"/></svg></button>
        <div class="sub"><a href="https://carelaxdanmark.dk/produkt-kategori/massagestole-pris/">Priser på nye massagestole</a><a href="https://carelaxdanmark.dk/produkt-kategori/brugt/">Brugte massagestole</a><a href="https://carelaxdanmark.dk/proev-gratis/">Prøv en stol</a></div></div>
      <div class="nav-item"><a href="https://carelaxdanmark.dk/produkt-kategori/massageapparater/">Massageapparater</a><button type="button" class="sub-toggle" aria-expanded="false" aria-label="Vis undermenu: Massageapparater"><svg width="10" height="10" viewBox="0 0 10 10" aria-hidden="true"><path d="M2 3.5 5 6.5 8 3.5" fill="none" stroke="currentColor" stroke-width="1.6"/></svg></button>
        <div class="sub"><a href="https://carelaxdanmark.dk/produkt-kategori/massageapparater/massagepistol/">Massagepistol</a><a href="https://carelaxdanmark.dk/produkt-kategori/massageapparater/fodmassageapparat/">Fodmassageapparat</a><a href="https://carelaxdanmark.dk/produkt-kategori/massageapparater/massagepuder/">Massagepuder</a></div></div>
      <div class="nav-item"><a href="https://carelaxdanmark.dk/produkt-kategori/senge/">Senge</a><button type="button" class="sub-toggle" aria-expanded="false" aria-label="Vis undermenu: Senge"><svg width="10" height="10" viewBox="0 0 10 10" aria-hidden="true"><path d="M2 3.5 5 6.5 8 3.5" fill="none" stroke="currentColor" stroke-width="1.6"/></svg></button>
        <div class="sub"><a href="https://carelaxdanmark.dk/produkt-kategori/senge/">Senge</a><a href="https://carelaxdanmark.dk/produkt-kategori/senge/sengerammer/">Sengerammer</a><a href="https://carelaxdanmark.dk/produkt-kategori/senge/lamel-og-elevationsbunde/">Lamel- og elevationsbunde</a><a href="https://carelaxdanmark.dk/produkt-kategori/senge/madrasser/">Madrasser</a><a href="https://carelaxdanmark.dk/ergonomisk-rygtjek/">Gratis ergonomisk rygtjek</a></div></div>
      <a href="https://carelaxdanmark.dk/ergonomisk-rygtjek/" class="nav-active" aria-current="page">Gratis ergonomisk rygtjek</a>
      <a href="https://carelaxdanmark.dk/erhverv/">Erhverv</a>
      <div class="nav-item"><a href="https://carelaxdanmark.dk/om-os/">Om os</a><button type="button" class="sub-toggle" aria-expanded="false" aria-label="Vis undermenu: Om os"><svg width="10" height="10" viewBox="0 0 10 10" aria-hidden="true"><path d="M2 3.5 5 6.5 8 3.5" fill="none" stroke="currentColor" stroke-width="1.6"/></svg></button>
        <div class="sub"><a href="https://carelaxdanmark.dk/om-os/">Om os</a><a href="https://carelaxdanmark.dk/vores-team/">Vores velværeteam</a><a href="https://carelaxdanmark.dk/kontakt/">Kontakt os</a><a href="https://carelaxdanmark.dk/bestil-tekniker/">Bestil tekniker</a></div></div>
      <div class="nav-item"><a href="https://carelaxdanmark.dk/blog/">Viden</a><button type="button" class="sub-toggle" aria-expanded="false" aria-label="Vis undermenu: Viden"><svg width="10" height="10" viewBox="0 0 10 10" aria-hidden="true"><path d="M2 3.5 5 6.5 8 3.5" fill="none" stroke="currentColor" stroke-width="1.6"/></svg></button>
        <div class="sub"><a href="https://carelaxdanmark.dk/blog/">Blog</a><a href="https://carelaxdanmark.dk/ordbog/">Ordbog</a></div></div>
      <a href="tel:+4530554554" class="nav-tel"><svg width="14" height="14" viewBox="0 0 24 24" fill="#33b048"><path d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1A17 17 0 0 1 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1z"/></svg>30 554 554</a>
    </nav>
    <div class="m-actions">
      <a href="tel:+4530554554" class="m-call" aria-label="Ring 30 554 554"><svg width="18" height="18" viewBox="0 0 24 24" fill="#33b048"><path d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1A17 17 0 0 1 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1z"/></svg></a>
      <button type="button" class="nav-toggle" id="navToggle" aria-label="Menu" aria-expanded="false"><span></span><span></span><span></span></button>
    </div>
  </div>
</header>

<section class="hero">
  <div class="wrap">
    <div class="hero-copy">
      <div class="pill"><span class="pill-dot"></span>Gratis · Uforpligtende · Vi kommer til dig</div>
      <h1>Vågner du med ondt i ryggen? Få et <span class="green">gratis rygtjek</span> hjemme hos dig.</h1>
      <p class="hero-lead">En fysioterapeut eller specialist i søvn kommer hjem til dig, laver en 3D-scanning af din ryg, vurderer din madras og giver dig konkrete råd til en bedre nattesøvn. Du skal ikke købe noget.</p>
      <div class="facts">
        <span class="fact"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>Ca. 45 min.</span>
        <span class="fact"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 11l9-7 9 7v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/></svg>Hjemme hos dig, hele landet</span>
        <span class="fact"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg>0 kr.</span>
      </div>
      <p class="honest"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 2l8 4v6c0 5-3.5 9-8 10-4.5-1-8-5-8-10V6z"/></svg><span>Vi er ærlige: Er din madras fin, siger vi det. Er den ikke, viser vi gerne, hvad der kan hjælpe. Du bestemmer selv.</span></p>
      <div class="trust">
        <a href="https://dk.trustpilot.com/review/carelaxdanmark.dk" target="_blank" rel="noopener" class="trust-item"><span class="stars" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></span><b>4,9 på Trustpilot</b><span>551 anmeldelser</span></a>
        <a href="https://borsen.dk/gazelle/" target="_blank" rel="noopener" class="trust-item"><b>Børsen Gazelle fire år i træk</b><span>2022, 2023, 2024 og 2025</span></a>
        <div class="trust-item"><b>Kendt fra Sund Forskning</b><span>11 artikler siden 2024</span></div>
      </div>
    </div>

    <div id="book" class="card">
      <form id="form" novalidate>
        <div class="card-body" id="step1">
          <h2>Book dit gratis rygtjek</h2>
          <p class="card-intro">Udfyld tre felter. Vi ringer dig op inden for 24 timer på hverdage og finder et tidspunkt, der passer dig.</p>
          <div class="fields">
            <label class="field">Navn<input class="input" name="navn" autocomplete="name" placeholder="Fornavn og efternavn"></label>
            <div class="row2">
              <label class="field">Telefon<input class="input" name="tlf" type="tel" autocomplete="tel-national" inputmode="tel" placeholder="12 34 56 78"></label>
              <label class="field">Postnummer<input class="input" name="post" autocomplete="postal-code" inputmode="numeric" maxlength="4" placeholder="2600"></label>
            </div>
            <p class="hint">Postnummeret bruger vi til at finde en specialist tæt på dig.</p>
            <!-- Spamfælde: skjult felt, som kun robotter udfylder -->
            <label class="hp" aria-hidden="true">Website<input name="website" tabindex="-1" autocomplete="off"></label>
          </div>
          <div class="symbox" id="symBox" hidden><p>Dine svar sendes med, så specialisten kan forberede sig:</p><ul id="symList"></ul></div>
          <p class="form-err" id="formErr" hidden>Tjek venligst navn, telefon (8 cifre) og postnummer (4 cifre).</p>
          <p class="form-err" id="sendErr" role="alert" hidden>Din booking blev ikke sendt. Prøv igen, eller ring på <a href="tel:+4530554554">30 554 554</a>.</p>
          <button type="submit" class="btn btn-primary" id="submit1">Book gratis rygtjek <span aria-hidden="true">→</span></button>
          <div class="assure"><span><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>Koster 0 kr.</span><span><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>Ingen købsforpligtelse</span><span><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>Hele landet</span></div>
          <p class="privacy">Vi bruger kun dine oplysninger til at kontakte dig om rygtjekket. <a href="https://carelaxdanmark.dk/privatlivspolitik/" target="_blank" rel="noopener">Privatlivspolitik</a></p>
        </div>

        <div class="card-body" id="step2" hidden>
          <div class="done-ico"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path d="M20 6L9 17l-5-5"/></svg></div>
          <h2>Tak, <span id="firstName"></span>. Din booking er modtaget.</h2>
          <p class="card-intro">Vi ringer på <b id="thanksTlf"></b> inden for 24 timer på hverdage.</p>
          <p class="extra-q">Vil du hjælpe specialisten med at forberede sig? <span class="opt">(valgfrit)</span></p>
          <div class="pains" id="pains"></div>
          <div class="fields">
            <label class="field"><span>E-mail <span class="opt">(valgfrit)</span></span><input class="input" name="mail" type="email" autocomplete="email" placeholder="navn@mail.dk"></label>
            <label class="field"><span>Bemærkning <span class="opt">(fx hvornår vi bedst kan ringe)</span></span><textarea class="input" name="note" rows="2"></textarea></label>
          </div>
          <p class="form-err" id="sendErr2" role="alert" hidden>Dine ekstra oplysninger blev ikke sendt. Prøv igen. Din booking er modtaget.</p>
          <button type="button" class="btn btn-primary" id="send2">Send ekstra oplysninger</button>
        </div>

        <div class="card-body" id="step3" hidden>
          <div class="done-ico"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path d="M20 6L9 17l-5-5"/></svg></div>
          <h2>Perfekt. Vi glæder os til at hjælpe dig.</h2>
          <p class="card-intro">Du hører fra os inden for 24 timer på hverdage.</p>
          <button type="button" class="link-btn" id="reset">Lav en ny booking</button>
        </div>
      </form>
      <div class="card-foot"><span>Hellere tale med os nu?</span><a href="tel:+4530554554">Ring 30 554 554</a></div>
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

<section class="sec process">
  <div class="wrap">
    <div class="sec-intro">
      <div class="eyebrow">SÅDAN FOREGÅR DET</div>
      <h2 class="h2">Fra booking til bedre søvn</h2>
      <p class="lead">Her er præcis, hvad der sker, så du ved, hvad du siger ja til.</p>
    </div>
    <div class="split">
      <aside class="person">
        <div class="vid">
          <video id="simonVideo" src="{{RYGTJEK_ASSETS}}rygtjek-simon.mp4" poster="{{RYGTJEK_ASSETS}}rygtjek-simon-poster.webp" autoplay muted loop playsinline preload="metadata" aria-label="Fysioterapeut Simon fortæller om rygtjekket"></video>
          <button type="button" class="vid-sound" id="vidSound" aria-pressed="false"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M11 5 6 9H2v6h4l5 4z"/><path class="sound-on" d="M15.5 8.5a5 5 0 0 1 0 7M19 5a10 10 0 0 1 0 14" style="display:none"/><path class="sound-off" d="m22 9-6 6M16 9l6 6"/></svg><span>Slå lyd til</span></button>
        </div>
        <div class="person-in">
          <h3>Mød Simon</h3>
          <div class="role">Fysioterapeut hos Carelax Danmark</div>
          <p class="person-txt">Simon laver fysioterapeutiske rygtjek, så madrassen bliver tilpasset dig og ikke bare er en standard.</p>
          <blockquote>"Næsten alle siger, at deres ryg er fin. Men du kan ikke mærke, hvordan din ryg ligger, mens du sover. Det kan vi se på en 3D-scanning. Og er din ryg fin, har du fået det bekræftet gratis."</blockquote>
          <a href="#book" class="btn btn-block" data-to-form>Book din gratis 3D-scanning</a>
        </div>
      </aside>
      <ol class="steps">
        <li class="step"><div class="num">1</div><div><h3>Vi ringer dig op</h3><span class="time">Inden for 24 timer på hverdage</span><p>Vi aftaler et tidspunkt, der passer dig, og hører kort om dine gener.</p></div></li>
        <li class="step"><div class="num">2</div><div><h3>Specialisten kommer hjem til dig</h3><span class="time">Uanset hvor i landet du bor</span><p>Du sparer transporttiden. Vi taler om, hvordan du sover, og hvor du mærker generne.</p></div></li>
        <li class="step"><div class="num">3</div><div><h3>3D-scanning af ryggen</h3><span class="time">Med tøj på, liggende og stående</span><p>Vi scanner din ryg, mens du står op, og igen mens du ligger på din egen madras. Så kan vi sammenligne de to. Ryggen skal helst have samme form døgnet rundt, også når du sover. Du har tøjet på, og det gør ikke ondt.</p></div></li>
        <li class="step"><div class="num">4</div><div><h3>Resultat og konkrete råd</h3><span class="time">Du bestemmer selv, hvad du vil bruge dem til</span><p>Vi gennemgår resultatet sammen og giver dig råd, du kan bruge med det samme, fx om sovestilling, pude eller madras.</p></div></li>
      </ol>
    </div>
  </div>
</section>

<section class="sec alt">
  <div class="wrap">
    <div class="sec-intro">
      <div class="eyebrow">KENDER DU DET?</div>
      <h2 class="h2">Sæt flueben ved det, der passer på dig</h2>
      <p class="lead">Den forkerte madras eller sovestilling kan give gener i nakke, ryg og lænd. Ofte starter løsningen med madrassen.</p>
    </div>
    <div class="symptoms" id="symptoms"></div>
    <div class="sym-result">
      <p id="symText">Kan du nikke genkendende til bare én af dem, er et rygtjek et godt sted at starte.</p>
      <a href="#book" class="btn" id="symBtn" data-to-form>Book gratis rygtjek</a>
    </div>
  </div>
</section>

<section class="sec">
  <div class="wrap">
    <div class="straight">
      <div>
        <div class="eyebrow">SKAL JEG KØBE NOGET?</div>
        <h2>Nej. Men vi siger det gerne ligeud.</h2>
      </div>
      <div>
        <p>Carelax sælger senge og madrasser. Det er derfor, vi kan tilbyde rygtjekket gratis.</p>
        <p>Er din madras fin, siger vi det, og så får du bare gode råd med. Er den ikke, viser vi dig gerne, hvad der kan hjælpe. Beslutningen er din, og et nej er helt i orden.</p>
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

    <!-- Udvalgte artikler (v2) -->
    <div class="sf-cards">
      <a class="sf-card" href="https://sund-forskning.dk/artikler/faa-scannet-din-ryg-derhjemme/" target="_blank" rel="noopener"><span class="sf-label">SUND FORSKNING</span><h3>Få scannet din ryg derhjemme</h3><span class="sf-date">26. maj 2025</span></a>
      <a class="sf-card" href="https://sund-forskning.dk/artikler/laendesmerter-taerer-pa-livskvaliteten/" target="_blank" rel="noopener"><span class="sf-label">SUND FORSKNING</span><h3>Lændesmerter tærer på livskvaliteten</h3><span class="sf-date">19. september 2024</span></a>
      <a class="sf-card" href="https://sund-forskning.dk/artikler/madrassen-en-forskel-ved-osteoporose-og-rygsmerter/" target="_blank" rel="noopener"><span class="sf-label">SUND FORSKNING</span><h3>Madrassen: en forskel ved osteoporose og rygsmerter</h3><span class="sf-date">18. februar 2026</span></a>
    </div>

    <!-- Børsen Gazelle (v2). Bruger sitets egen gazeller.svg (2×2) og viser de to rækker side om side -->
    <div class="gazelle">
      <div class="eyebrow">BØRSEN GAZELLE</div>
      <h3>Fire år i træk blandt Danmarks hurtigst voksende virksomheder</h3>
      <p>Tak til de mange kunder, der har valgt os. Det er jer, der har gjort væksten mulig.</p>
      <div class="gz-logos" role="img" aria-label="Børsen Gazelle 2022, 2023, 2024 og 2025">
        <div class="gz"><img src="https://carelaxdanmark.dk/wp-content/uploads/gazeller.svg" alt="" loading="lazy"></div>
        <div class="gz gz-b"><img src="https://carelaxdanmark.dk/wp-content/uploads/gazeller.svg" alt="" loading="lazy"></div>
      </div>
    </div>
  </div>
</section>

<section class="sec alt">
  <div class="wrap faq-wrap">
    <div class="sec-intro">
      <div class="eyebrow">SPØRGSMÅL &amp; SVAR</div>
      <h2 class="h2">Det spørger folk typisk om</h2>
      <p class="lead">Fandt du ikke svaret? Ring på <a href="tel:+4530554554"><b>30 554 554</b></a>.</p>
    </div>
    <div class="faq">
      <details open><summary>Er rygtjekket virkelig gratis?</summary><p>Ja. Rygtjekket er helt gratis og uforpligtende. Du betaler intet for besøget, scanningen eller rådgivningen.</p></details>
      <details><summary>Skal jeg købe noget bagefter?</summary><p>Nej. Vi sælger senge og madrasser, og hvis din madras ikke støtter dig godt nok, viser vi gerne, hvad der kan hjælpe. Men du bestemmer selv, og et nej er helt i orden.</p></details>
      <details><summary>Hvor lang tid tager det?</summary><p>Besøget tager typisk omkring 45 minutter, inklusive 3D-scanning og samtale.</p></details>
      <details><summary>Hvad er en 3D-scanning af ryggen?</summary><p>Vi scanner din ryg to gange: én gang mens du står op, og én gang mens du ligger i din egen seng. Du har almindeligt tøj på. Ved at sammenligne de to scanninger kan vi se, om din madras lader ryggen beholde sin naturlige form om natten. Ryggen skal helst ligne sig selv døgnet rundt.</p></details>
      <details><summary>Hvor foregår rygtjekket?</summary><p>Hjemme hos dig, uanset hvor i landet du bor. Så kan vi også se på den madras, du sover på til daglig.</p></details>
      <details><summary>Hvad sker der, når jeg har booket?</summary><p>Vi ringer dig op inden for 24 timer på hverdage og aftaler et tidspunkt, der passer dig.</p></details>
    </div>
  </div>
</section>

<section class="final">
  <div class="wrap">
    <div><h2>Klar til en bedre nattesøvn?</h2><p>Det tager 30 sekunder at booke. Vi ringer og aftaler resten.</p></div>
    <div class="final-r">
      <a href="tel:+4530554554" class="final-tel">eller ring 30 554 554</a>
      <a href="#book" class="btn-big" data-to-form>Book gratis rygtjek</a>
    </div>
  </div>
</section>

<div class="usp-bar"><div class="wrap"><div class="usp"><img src="{{RYGTJEK_ASSETS}}rygtjek-usp0.png" alt="">Rentefrit køb</div><div class="usp"><img src="{{RYGTJEK_ASSETS}}rygtjek-usp1.png" alt="">Høj kvalitet</div><div class="usp"><img src="{{RYGTJEK_ASSETS}}rygtjek-usp2.png" alt="">Prisgaranti</div><div class="usp"><img src="{{RYGTJEK_ASSETS}}rygtjek-usp3.png" alt="">Vi leverer</div><div class="usp"><img src="{{RYGTJEK_ASSETS}}rygtjek-usp4.png" alt="">Byt til nyt</div></div></div>
<footer class="footer">
  <div class="wrap footer-grid">
    <div>
      <h4>Carelax Danmark ApS</h4>
      <div class="f-contact"><div class="f-row"><svg width="13" height="13" viewBox="0 0 24 24" fill="#fff"><path d="M12 3 2 12h3v8h5v-6h4v6h5v-8h3z"/></svg><span>Naverland 25, 2600 Glostrup</span></div><div class="f-row"><svg width="13" height="13" viewBox="0 0 24 24" fill="#fff"><path d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1A17 17 0 0 1 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1z"/></svg><span><a href="tel:+4530554554">+45 30 554 554</a></span></div><div class="f-row"><svg width="13" height="13" viewBox="0 0 24 24" fill="#fff"><path d="M12 2a10 10 0 1 0 5 18.7l-1-1.7A8 8 0 1 1 20 12v1.5a1.5 1.5 0 0 1-3 0V12a5 5 0 1 0-1.5 3.5A3.5 3.5 0 0 0 22 13.5V12A10 10 0 0 0 12 2zm0 13a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/></svg><span><a href="mailto:info@carelaxdanmark.dk">Skriv til os her</a></span></div><div class="f-row"><svg width="13" height="13" viewBox="0 0 24 24" fill="#fff"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm1 15h-2v-6h2zm0-8h-2V7h2z"/></svg><span>CVR: 38219499</span></div></div>
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
      <form class="f-news" onsubmit="return false"><input type="email" placeholder="Indtast din e-mail" aria-label="E-mail"><div><button type="submit">TILMELD NYHEDSBREV</button></div></form>
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
  // Varianter styres via URL, fx ?sticky=0&stats=0&press=0
  var q = new URLSearchParams(location.search);
  var cfg = {
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
  if (!cfg.stats) $('[data-section="stats"]').remove();
  if (!cfg.press) $('[data-section="press"]').remove();

  // På WordPress sætter kodestykket window.CARELAX_RYGTJEK.endpoint (mail til samme modtagere som den
  // gamle rygtjek-formular). Uden endpoint (fx Vercel-demoen) virker formularen, men intet sendes.
  var form = $('#form');
  var endpoint = form.dataset.endpoint || (window.CARELAX_RYGTJEK && window.CARELAX_RYGTJEK.endpoint);
  var loadedAt = Date.now();
  function post(payload) {
    if (!endpoint) return Promise.resolve();
    payload.side = location.href;
    payload.website = form.website ? form.website.value : ''; // spamfælde
    payload.t = Date.now() - loadedAt;
    return fetch(endpoint, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) })
      .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); });
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
      .then(function () {
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
    post({ type: 'extra', navn: booking.navn, telefon: booking.telefon, postnummer: booking.postnummer,
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

  renderSym(); paint();
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
<script>window.CARELAX_RYGTJEK = { endpoint: <?php echo wp_json_encode( esc_url_raw( rest_url( 'carelax/v1/rygtjek' ) ) ); ?> };</script>
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
