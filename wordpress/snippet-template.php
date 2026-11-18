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

	// Tracking. Temaet indsætter GTM direkte i header.php, som denne side ikke bruger, så GTM indsættes her.
	// Konverteringer ved en gennemført booking (trin 1): GA4-hændelsen rygtjek_lead (key event),
	// Google Ads-konverteringen "OMN | Rygtjek-lead" og Meta Lead + CompleteRegistration (som den gamle formular).
	// Sæt en konstant til '' i wp-config.php for at slå den del fra.
	if ( ! defined( 'CARELAX_RYGTJEK_GTM' ) ) {
		define( 'CARELAX_RYGTJEK_GTM', 'GTM-T3H8WGW' );
	}
	if ( ! defined( 'CARELAX_RYGTJEK_GA4' ) ) {
		define( 'CARELAX_RYGTJEK_GA4', 'G-B9TK6QR2DJ' );
	}
	if ( ! defined( 'CARELAX_RYGTJEK_ADS_CONVERSION' ) ) {
		define( 'CARELAX_RYGTJEK_ADS_CONVERSION', 'AW-623792239/yQ-oCIjJxccZEO-guakC' );
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

	// 3b) Bookinger sendes også til Pipedrive (pipeline "Rygtjek Danmark", fase Lead) via Make-scenariet
	//     "Carelax | Rygtjek-landingsside (hjemmeside) → Pipedrive". Mailen sendes altid først og er backup.
	//     Kaldet til Make sker, efter svaret er sendt til den besøgende, så bookingen ikke venter på Pipedrive.
	//     Webhook-adresse og nøgle står ikke i repoet (det er offentligt): build.py sætter dem ind fra
	//     wordpress/secrets.local.json, eller de sættes i wp-config.php. Tomme værdier = kun mail.
	if ( ! defined( 'CARELAX_RYGTJEK_MAKE_URL' ) ) {
		define( 'CARELAX_RYGTJEK_MAKE_URL', '/*__MAKE_URL__*/' );
	}
	if ( ! defined( 'CARELAX_RYGTJEK_MAKE_KEY' ) ) {
		define( 'CARELAX_RYGTJEK_MAKE_KEY', '/*__MAKE_KEY__*/' );
	}

	if ( ! function_exists( 'carelax_rygtjek_make' ) ) {
		// Sender data til Make. Returnerer Makes svar som array, eller null ved fejl.
		function carelax_rygtjek_make( $data ) {
			if ( ! CARELAX_RYGTJEK_MAKE_URL || ! CARELAX_RYGTJEK_MAKE_KEY ) {
				return null;
			}
			$res = wp_remote_post( CARELAX_RYGTJEK_MAKE_URL, array(
				'timeout' => 20,
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode( array( 'key' => CARELAX_RYGTJEK_MAKE_KEY ) + $data ),
			) );
			if ( is_wp_error( $res ) || 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
				return null;
			}
			$out = json_decode( (string) wp_remote_retrieve_body( $res ), true );
			return ( is_array( $out ) && ! empty( $out['ok'] ) ) ? $out : null;
		}
	}

	if ( ! function_exists( 'carelax_rygtjek_crm_data' ) ) {
		// Det, der sendes til Make: kontaktoplysninger, kilde (UTM fra sidens adresse) og en note til dealen.
		function carelax_rygtjek_crm_data( $type, $f ) {
			if ( 'extra' === $type ) {
				$data = array(
					'type' => 'extra',
					'note' => '<b>Ekstra oplysninger fra rygtjek-landingssiden</b><br>'
						. 'Gener: ' . esc_html( $f['gener'] ? implode( ', ', $f['gener'] ) : '(ingen valgt)' ) . '<br>'
						. ( $f['email'] ? 'E-mail: ' . esc_html( $f['email'] ) . '<br>' : '' )
						. 'Bemærkning: ' . ( '' !== $f['note'] ? nl2br( esc_html( $f['note'] ) ) : '(ingen)' ),
				);
				if ( $f['email'] ) {
					$data['email'] = $f['email'];
				}
				return $data;
			}
			$q = array();
			wp_parse_str( (string) wp_parse_url( $f['side'], PHP_URL_QUERY ), $q );
			$g = function ( $k ) use ( $q ) {
				return ( isset( $q[ $k ] ) && is_string( $q[ $k ] ) ) ? mb_substr( sanitize_text_field( $q[ $k ] ), 0, 150 ) : '';
			};
			$kilde = $g( 'utm_source' );
			if ( '' === $kilde && ( $g( 'gclid' ) || $g( 'gbraid' ) || $g( 'wbraid' ) ) ) {
				$kilde = 'Google Ads';
			} elseif ( '' === $kilde && $g( 'fbclid' ) ) {
				$kilde = 'Meta';
			}
			$kampagne = 'Hjemmeside | rygtjek-landingsside' . ( $kilde ? ' | ' . $kilde : '' ) . ( $g( 'utm_campaign' ) ? ' | ' . $g( 'utm_campaign' ) : '' );
			return array(
				'type'        => 'booking',
				'is_test'     => (bool) preg_match( '/^test\b/i', $f['navn'] ), // tildeles Mikkel i Make
				'navn'        => str_replace( array( '"', '\\' ), '', $f['navn'] ),
				'telefon'     => '+45' . $f['tlf'],
				'postnummer'  => $f['post'],
				'kampagne'    => $kampagne,
				'annoncesaet' => $g( 'utm_term' ),    // Metas skabelon: utm_term = annoncesæt
				'annoncenavn' => $g( 'utm_content' ), // og utm_content = annonce
				'gclid'       => $g( 'gclid' ),
				'note'        => '<b>Booking fra rygtjek-landingssiden</b><br>'
					. 'Kender du det? (tjekliste): ' . esc_html( $f['symptomer'] ? implode( ', ', $f['symptomer'] ) : '(intet valgt)' ) . '<br>'
					. 'Kilde: ' . esc_html( $kampagne ) . '<br>'
					. 'Side: ' . esc_html( $f['side'] ),
			);
		}
	}

	if ( ! function_exists( 'carelax_rygtjek_crm' ) ) {
		// Booking: opretter person + deal og husker deal-id'et under bookingens nøgle ($lead) i 2 timer.
		// Ekstra: lægger en note på den samme deal (og e-mailen på personen). Returnerer true ved succes.
		function carelax_rygtjek_crm( $type, $data, $lead ) {
			$slot = 'carelax_rygtjek_lead_' . $lead;
			if ( 'booking' === $type ) {
				$out = carelax_rygtjek_make( $data );
				if ( ! $out || empty( $out['deal_id'] ) ) {
					return false;
				}
				set_transient( $slot, array( 'deal_id' => (int) $out['deal_id'], 'person_id' => (int) $out['person_id'] ), 2 * HOUR_IN_SECONDS );
				return true;
			}
			if ( '' === $lead ) {
				return false;
			}
			// Trin 2 kan i sjældne tilfælde nå frem, før Pipedrive har svaret på trin 1: vent lidt.
			for ( $i = 0; $i < 4; $i++ ) {
				if ( $i ) {
					sleep( 3 );
				}
				if ( wp_using_ext_object_cache() ) {
					$ids = wp_cache_get( $slot, 'transient', true );
				} else {
					wp_cache_delete( 'notoptions', 'options' );
					wp_cache_delete( '_transient_' . $slot, 'options' );
					wp_cache_delete( '_transient_timeout_' . $slot, 'options' );
					$ids = get_transient( $slot );
				}
				if ( is_array( $ids ) && ! empty( $ids['deal_id'] ) ) {
					return null !== carelax_rygtjek_make( $data + $ids );
				}
			}
			return false;
		}
	}

	if ( ! function_exists( 'carelax_rygtjek_finish_request' ) ) {
		// Afslut svaret til browseren, så resten (Make-kaldet) kører i baggrunden.
		function carelax_rygtjek_finish_request() {
			ignore_user_abort( true );
			if ( function_exists( 'set_time_limit' ) ) {
				@set_time_limit( 60 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
			}
			if ( function_exists( 'fastcgi_finish_request' ) ) {
				fastcgi_finish_request();
			} elseif ( function_exists( 'litespeed_finish_request' ) ) {
				litespeed_finish_request();
			}
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
			$sent = wp_mail( $s['to'], $subject, $body, $headers );

			// Pipedrive. Bookingen får en tilfældig nøgle, som browseren sender med trin 2,
			// så de ekstra oplysninger lander på den samme deal.
			$lead = '';
			if ( CARELAX_RYGTJEK_MAKE_URL && CARELAX_RYGTJEK_MAKE_KEY ) {
				$lead = 'booking' === $type
					? wp_generate_password( 20, false )
					: substr( preg_replace( '/[^A-Za-z0-9]/', '', isset( $p['lead'] ) ? (string) $p['lead'] : '' ), 0, 40 );
				$crm  = carelax_rygtjek_crm_data( $type, compact( 'navn', 'tlf', 'post', 'email', 'note', 'side', 'symptomer', 'gener' ) );
				if ( $sent ) {
					add_action( 'shutdown', function () use ( $type, $crm, $lead ) {
						carelax_rygtjek_finish_request();
						carelax_rygtjek_crm( $type, $crm, $lead );
					}, 100 );
				} else {
					// Mailen fejlede: bookingen tæller stadig, hvis den kom i Pipedrive.
					$sent = carelax_rygtjek_crm( $type, $crm, $lead );
				}
			}
			if ( ! $sent ) {
				return new WP_REST_Response( array( 'ok' => false, 'error' => 'mail' ), 500 );
			}
			return new WP_REST_Response( array( 'ok' => true, 'lead' => 'booking' === $type ? $lead : '' ), 200 );
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
<?php if ( CARELAX_RYGTJEK_GTM ) : ?>
<!-- Google Tag Manager (samme container som resten af sitet) -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer',<?php echo wp_json_encode( CARELAX_RYGTJEK_GTM ); ?>);</script>
<!-- End Google Tag Manager -->
<?php endif; ?>
<style id="carelax-rygtjek-css">
<?php echo $css; // phpcs:ignore WordPress.Security.EscapeOutput ?>
<?php echo $wp_css; // phpcs:ignore WordPress.Security.EscapeOutput ?>
</style>
</head>
<body class="carelax-rygtjek">
<?php if ( CARELAX_RYGTJEK_GTM ) : ?>
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo esc_attr( CARELAX_RYGTJEK_GTM ); ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<?php endif; ?>
<?php
		if ( function_exists( 'wp_body_open' ) ) {
			wp_body_open();
		}
		echo $body; // phpcs:ignore WordPress.Security.EscapeOutput
?>
<script>window.CARELAX_RYGTJEK = <?php
		echo wp_json_encode( array(
			'endpoint' => esc_url_raw( rest_url( 'carelax/v1/rygtjek' ) ),
			'tracking' => array(
				'ga4'  => CARELAX_RYGTJEK_GA4,
				'ads'  => CARELAX_RYGTJEK_ADS_CONVERSION,
				'meta' => array( 'Lead', 'CompleteRegistration' ),
			),
		) );
		?>;</script>
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
