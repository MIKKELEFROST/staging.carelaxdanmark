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
