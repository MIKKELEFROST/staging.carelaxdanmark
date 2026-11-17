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
			$symptomer = $pick( 'symptomer', array( 'Stiv i lænden om morgenen', 'Ondt i nakke eller skuldre', 'Du sover arm eller skulder', 'Du vender dig hele natten', 'Madrassen er over 8-10 år gammel', 'Du vågner træt' ) );
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
/*__CSS__*/
RYGTJEK_CSS_END;

		$body = <<<'RYGTJEK_BODY_END'
/*__BODY__*/
RYGTJEK_BODY_END;

		$js = <<<'RYGTJEK_JS_END'
/*__JS__*/
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
