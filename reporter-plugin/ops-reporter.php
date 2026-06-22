<?php
/**
 * Plugin Name:       Ops Cockpit Reporter
 * Plugin URI:        https://dashboard.deineagentur.at
 * Description:        Read-only Telemetrie-Reporter. Sendet 2x täglich einen signierten, ausgehenden Statusbericht (Versionen, Plugins, Themes, Fingerprint-Signale) an das zentrale Ops-Cockpit. Nimmt KEINE eingehenden Befehle entgegen.
 * Version:           1.0.0
 * Requires at least: 5.5
 * Requires PHP:      7.4
 * Author:            Deine Agentur
 * License:           GPL-2.0-or-later
 * Update URI:        false
 *
 * SICHERHEITSMODELL
 * -----------------
 * - Outbound-only: das Plugin öffnet keinen eigenen Endpoint und nimmt keine Kommandos an.
 * - Pro Site ein eigenes Secret. Endpoint + Secret stehen in wp-config.php, NICHT in der DB
 *   und NICHT im Plugin-Code im Repo:
 *
 *       define( 'OPS_REPORTER_ENDPOINT', 'https://dashboard.deineagentur.at/api/ingest' );
 *       define( 'OPS_REPORTER_SITE_ID',  'ried-immobilien' );          // muss im Cockpit existieren
 *       define( 'OPS_REPORTER_SECRET',   '64-stelliger-zufalls-hex' ); // einmalig generiert, pro Site verschieden
 *
 * - Jeder Push ist HMAC-SHA256-signiert (Body + Timestamp) und enthält einen Zeitstempel
 *   gegen Replay. Vergleich serverseitig timing-safe.
 * - sslverify bleibt immer aktiv.
 *
 * Verlässliche Intervalle: Bei traffic-armen Seiten WP-Cron NICHT auf reinen Besucher-Traffic
 * verlassen. Echten Server-Cron auf wp-cron.php zeigen lassen und WP-Cron intern deaktivieren
 * (define('DISABLE_WP_CRON', true);), sonst drohen Fehlalarme durch den Dead-Man's-Switch.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Direktaufruf verhindern.
}

define( 'OPS_REPORTER_VERSION', '1.0.0' );
define( 'OPS_REPORTER_CRON_HOOK', 'ops_reporter_send_report' );
define( 'OPS_REPORTER_SCHEDULE', 'ops_twicedaily' );

/* -------------------------------------------------------------------------
 *  Aktivierung / Deaktivierung – Cron-Schedule sauber registrieren
 * ---------------------------------------------------------------------- */

register_activation_hook( __FILE__, 'ops_reporter_activate' );
register_deactivation_hook( __FILE__, 'ops_reporter_deactivate' );

function ops_reporter_activate() {
	if ( ! wp_next_scheduled( OPS_REPORTER_CRON_HOOK ) ) {
		// Erster Lauf in 5 Minuten, danach im definierten Intervall.
		wp_schedule_event( time() + 300, OPS_REPORTER_SCHEDULE, OPS_REPORTER_CRON_HOOK );
	}
}

function ops_reporter_deactivate() {
	$timestamp = wp_next_scheduled( OPS_REPORTER_CRON_HOOK );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, OPS_REPORTER_CRON_HOOK );
	}
	wp_clear_scheduled_hook( OPS_REPORTER_CRON_HOOK );
}

/**
 * Eigenes Intervall registrieren (Standard: alle 12h = 2x/Tag).
 * Über den Filter 'ops_reporter_interval_seconds' überschreibbar.
 */
add_filter( 'cron_schedules', function ( $schedules ) {
	$interval = (int) apply_filters( 'ops_reporter_interval_seconds', 12 * HOUR_IN_SECONDS );
	$schedules[ OPS_REPORTER_SCHEDULE ] = array(
		'interval' => max( HOUR_IN_SECONDS, $interval ),
		'display'  => __( 'Ops Cockpit Reporting-Intervall', 'ops-reporter' ),
	);
	return $schedules;
} );

add_action( OPS_REPORTER_CRON_HOOK, 'ops_reporter_run' );

/* -------------------------------------------------------------------------
 *  Datenerhebung
 * ---------------------------------------------------------------------- */

/**
 * Stellt den vollständigen Bericht zusammen.
 * Alles read-only; nichts wird verändert.
 *
 * @return array<string,mixed>
 */
function ops_reporter_collect() {
	global $wp_version, $wpdb;

	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	if ( ! function_exists( 'get_plugin_updates' ) ) {
		require_once ABSPATH . 'wp-admin/includes/update.php';
	}

	$report = array(
		'reporter_version' => OPS_REPORTER_VERSION,
		'collected_at'     => gmdate( 'c' ),
		'site'             => ops_reporter_core_info( $wp_version, $wpdb ),
		'plugins'          => ops_reporter_plugins_info(),
		'themes'           => ops_reporter_themes_info(),
		'fingerprint'      => ops_reporter_fingerprint(),
	);

	/**
	 * Letzte Möglichkeit, den Bericht zu erweitern/zu kürzen, ohne den Core anzufassen.
	 */
	return apply_filters( 'ops_reporter_report', $report );
}

/**
 * Kern-Versionsstände und Erreichbarkeits-relevante Eckdaten.
 */
function ops_reporter_core_info( $wp_version, $wpdb ) {
	$updates       = function_exists( 'get_core_updates' ) ? get_core_updates() : array();
	$core_update   = ( is_array( $updates ) && ! empty( $updates[0] ) && isset( $updates[0]->response ) && 'upgrade' === $updates[0]->response )
		? $updates[0]->current
		: null;

	return array(
		'url'                => home_url(),
		'name'               => get_bloginfo( 'name' ),
		'wp_version'         => $wp_version,
		'wp_update'          => $core_update,                       // verfügbare Core-Version oder null
		'php_version'        => PHP_VERSION,
		'mysql_version'      => is_object( $wpdb ) ? $wpdb->db_version() : null,
		'is_multisite'       => is_multisite(),
		'https'              => ( 0 === strpos( home_url(), 'https://' ) ),
		'locale'             => get_locale(),
		'timezone'           => wp_timezone_string(),
		'server_software'    => isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : null,
	);
}

/**
 * Alle Plugins inkl. Update-Verfügbarkeit (read-only).
 *
 * @return array<int,array<string,mixed>>
 */
function ops_reporter_plugins_info() {
	$all     = get_plugins();
	$active  = (array) get_option( 'active_plugins', array() );
	$updates = get_plugin_updates(); // keyed by plugin file

	$out = array();
	foreach ( $all as $file => $data ) {
		$update_to = isset( $updates[ $file ]->update->new_version )
			? $updates[ $file ]->update->new_version
			: null;

		$out[] = array(
			'slug'             => dirname( $file ) !== '.' ? dirname( $file ) : basename( $file, '.php' ),
			'file'             => $file,
			'name'             => isset( $data['Name'] ) ? $data['Name'] : $file,
			'version'          => isset( $data['Version'] ) ? $data['Version'] : null,
			'active'           => in_array( $file, $active, true ) || ( is_multisite() && is_plugin_active_for_network( $file ) ),
			'update_available' => null !== $update_to,
			'update_version'   => $update_to,
		);
	}
	return $out;
}

/**
 * Themes inkl. aktivem Theme und Updates.
 */
function ops_reporter_themes_info() {
	if ( ! function_exists( 'wp_get_themes' ) ) {
		require_once ABSPATH . 'wp-includes/theme.php';
	}
	if ( ! function_exists( 'get_theme_updates' ) ) {
		require_once ABSPATH . 'wp-admin/includes/update.php';
	}

	$current = wp_get_theme();
	$updates = function_exists( 'get_theme_updates' ) ? get_theme_updates() : array();

	$out = array();
	foreach ( wp_get_themes() as $stylesheet => $theme ) {
		$update_to = isset( $updates[ $stylesheet ]->update['new_version'] )
			? $updates[ $stylesheet ]->update['new_version']
			: null;

		$out[] = array(
			'slug'             => $stylesheet,
			'name'             => $theme->get( 'Name' ),
			'version'          => $theme->get( 'Version' ),
			'active'           => ( $current->get_stylesheet() === $stylesheet ),
			'update_available' => null !== $update_to,
			'update_version'   => $update_to,
		);
	}
	return $out;
}

/* -------------------------------------------------------------------------
 *  Fingerprint-Signale (für Compliance-Auto-Matching im Cockpit)
 * ---------------------------------------------------------------------- */

/**
 * Leichtgewichtige Erkennung relevanter Eigenschaften – ohne Frontend-Scan.
 * Liefert boolesche/listenartige Signale, die das Dashboard Pflichten zuordnet
 * (DSGVO, EAA/Barrierefreiheit, Cookie-Consent, Impressum, lokale Fonts …).
 *
 * @return array<string,mixed>
 */
function ops_reporter_fingerprint() {
	$active_slugs = ops_reporter_active_plugin_slugs();

	$has_any = function ( array $needles ) use ( $active_slugs ) {
		foreach ( $needles as $n ) {
			foreach ( $active_slugs as $slug ) {
				if ( false !== strpos( $slug, $n ) ) {
					return true;
				}
			}
		}
		return false;
	};

	$form_plugins = array(
		'contact-form-7' => 'contact-form-7',
		'wpforms'        => 'wpforms',
		'fluentform'     => 'fluentform',
		'gravityforms'   => 'gravityforms',
		'ninja-forms'    => 'ninja-forms',
		'forminator'     => 'forminator',
	);
	$detected_forms = array();
	foreach ( $form_plugins as $label => $needle ) {
		if ( $has_any( array( $needle ) ) ) {
			$detected_forms[] = $label;
		}
	}

	$consent_plugins = array( 'borlabs-cookie', 'complianz', 'cookie-notice', 'cookiebot', 'real-cookie-banner', 'gdpr-cookie-compliance', 'iubenda' );
	$tracking_plugins = array( 'google-site-kit', 'googleanalytics', 'gtm', 'google-tag-manager', 'matomo', 'pixelyoursite', 'facebook-for-woocommerce' );

	return array(
		// Shop / E-Commerce
		'has_woocommerce'   => $has_any( array( 'woocommerce' ) ),
		'has_shop'          => $has_any( array( 'woocommerce', 'easy-digital-downloads', 'wp-ecommerce' ) ),

		// Formulare
		'has_forms'         => ! empty( $detected_forms ),
		'form_plugins'      => $detected_forms,

		// Tracking / Consent
		'has_tracking'      => $has_any( $tracking_plugins ),
		'has_consent'       => $has_any( $consent_plugins ),
		'consent_plugins'   => array_values( array_filter( $consent_plugins, function ( $p ) use ( $has_any ) {
			return $has_any( array( $p ) );
		} ) ),

		// Pflichtseiten erkannt? (Slug-/Titel-Heuristik)
		'pages'             => ops_reporter_required_pages(),

		// Eingebettete Drittdienste (Heuristik über aktive Plugins + bekannte Embeds)
		'third_parties'     => ops_reporter_third_party_hints( $active_slugs ),

		// Roh-Liste aktiver Slugs (erlaubt dem Cockpit eigene Regeln serverseitig)
		'active_plugin_slugs' => $active_slugs,
	);
}

/**
 * Aktive Plugin-Slugs als flache Liste.
 *
 * @return array<int,string>
 */
function ops_reporter_active_plugin_slugs() {
	$active = (array) get_option( 'active_plugins', array() );
	if ( is_multisite() ) {
		$network = array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) );
		$active  = array_merge( $active, $network );
	}
	$slugs = array();
	foreach ( $active as $file ) {
		$slugs[] = ( dirname( $file ) !== '.' ) ? dirname( $file ) : basename( $file, '.php' );
	}
	return array_values( array_unique( $slugs ) );
}

/**
 * Heuristische Erkennung von Pflichtseiten (Impressum, Datenschutz, Barrierefreiheit).
 * Sucht veröffentlichte Seiten mit typischen Slugs/Titeln. Nur Existenz wird gemeldet,
 * kein Seiteninhalt.
 *
 * @return array<string,bool>
 */
function ops_reporter_required_pages() {
	$patterns = array(
		'impressum'             => array( 'impressum', 'imprint', 'legal-notice' ),
		'datenschutz'           => array( 'datenschutz', 'privacy', 'datenschutzerklaerung' ),
		'barrierefreiheit'      => array( 'barrierefreiheit', 'accessibility', 'barrierefreiheitserklaerung' ),
		'agb'                   => array( 'agb', 'terms', 'geschaeftsbedingungen' ),
	);

	$found = array();
	foreach ( $patterns as $key => $slugs ) {
		$found[ $key ] = false;
		foreach ( $slugs as $slug ) {
			$page = get_page_by_path( $slug );
			if ( $page instanceof WP_Post && 'publish' === $page->post_status ) {
				$found[ $key ] = true;
				break;
			}
		}
	}

	// WP-eigene Datenschutzseite zusätzlich berücksichtigen.
	$privacy_id = (int) get_option( 'wp_page_for_privacy_policy', 0 );
	if ( $privacy_id > 0 && 'publish' === get_post_status( $privacy_id ) ) {
		$found['datenschutz'] = true;
	}

	return $found;
}

/**
 * Hinweise auf eingebettete Drittdienste anhand bekannter Integrations-Plugins.
 * Ein optionaler, tieferer Frontend-Scan (welche Hosts lädt die Startseite?) ist
 * bewusst NICHT Teil dieses leichten Reporters und gehört dashboard-seitig gemacht.
 *
 * @return array<int,string>
 */
function ops_reporter_third_party_hints( array $active_slugs ) {
	$map = array(
		'google-maps' => array( 'google-maps', 'wp-google-maps', 'maps-widget' ),
		'youtube'     => array( 'youtube', 'wp-youtube-lyte' ),
		'fonts'       => array( 'google-fonts', 'easy-google-fonts', 'olympus-google-fonts' ),
		'recaptcha'   => array( 'recaptcha', 'google-captcha' ),
	);
	$hits = array();
	foreach ( $map as $service => $needles ) {
		foreach ( $needles as $needle ) {
			foreach ( $active_slugs as $slug ) {
				if ( false !== strpos( $slug, $needle ) ) {
					$hits[] = $service;
					break 2;
				}
			}
		}
	}
	return array_values( array_unique( $hits ) );
}

/* -------------------------------------------------------------------------
 *  Versand (signiert, ausgehend)
 * ---------------------------------------------------------------------- */

/**
 * Hauptlauf: sammeln, signieren, pushen.
 *
 * @return array{ok:bool,message:string,code?:int}
 */
function ops_reporter_run() {
	if ( ! defined( 'OPS_REPORTER_ENDPOINT' ) || ! defined( 'OPS_REPORTER_SITE_ID' ) || ! defined( 'OPS_REPORTER_SECRET' ) ) {
		return ops_reporter_log_result( false, 'Konfiguration fehlt: OPS_REPORTER_ENDPOINT / _SITE_ID / _SECRET in wp-config.php definieren.' );
	}

	$report = ops_reporter_collect();

	$payload = array(
		'site_id'      => OPS_REPORTER_SITE_ID,
		'sent_at'      => time(),                 // Unix-Timestamp (UTC) für Replay-Fenster
		'nonce'        => wp_generate_password( 24, false, false ),
		'report'       => $report,
	);

	$body      = wp_json_encode( $payload );
	$timestamp = (string) $payload['sent_at'];
	$signature = hash_hmac( 'sha256', $timestamp . '.' . $body, OPS_REPORTER_SECRET );

	$response = wp_remote_post(
		OPS_REPORTER_ENDPOINT,
		array(
			'timeout'     => 20,
			'redirection' => 0,
			'sslverify'   => true, // NIEMALS abschalten
			'blocking'    => true,
			'headers'     => array(
				'Content-Type'       => 'application/json',
				'X-Ops-Site'         => OPS_REPORTER_SITE_ID,
				'X-Ops-Timestamp'    => $timestamp,
				'X-Ops-Signature'    => $signature,
				'X-Ops-Reporter'     => OPS_REPORTER_VERSION,
				'User-Agent'         => 'OpsReporter/' . OPS_REPORTER_VERSION,
			),
			'body'        => $body,
		)
	);

	if ( is_wp_error( $response ) ) {
		return ops_reporter_log_result( false, 'Transportfehler: ' . $response->get_error_message() );
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	if ( $code >= 200 && $code < 300 ) {
		return ops_reporter_log_result( true, 'Bericht gesendet.', $code );
	}

	return ops_reporter_log_result( false, 'Cockpit antwortete mit HTTP ' . $code . '.', $code );
}

/**
 * Schreibt das Ergebnis des letzten Laufs in die Optionen (für Admin-Anzeige/Debug).
 */
function ops_reporter_log_result( $ok, $message, $code = null ) {
	$result = array(
		'ok'      => (bool) $ok,
		'message' => (string) $message,
		'code'    => $code,
		'time'    => gmdate( 'c' ),
	);
	update_option( 'ops_reporter_last_result', $result, false );
	return $result;
}

/* -------------------------------------------------------------------------
 *  WP-CLI: manueller Sofort-Lauf  ->  wp ops-reporter send
 * ---------------------------------------------------------------------- */

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'ops-reporter send', function () {
		$res = ops_reporter_run();
		if ( $res['ok'] ) {
			WP_CLI::success( $res['message'] );
		} else {
			WP_CLI::error( $res['message'] );
		}
	} );
}

/* -------------------------------------------------------------------------
 *  Minimaler Admin-Hinweis: letzter Lauf + fehlende Konfiguration
 * ---------------------------------------------------------------------- */

add_action( 'admin_notices', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$configured = defined( 'OPS_REPORTER_ENDPOINT' ) && defined( 'OPS_REPORTER_SITE_ID' ) && defined( 'OPS_REPORTER_SECRET' );
	if ( ! $configured ) {
		echo '<div class="notice notice-warning"><p><strong>Ops Cockpit Reporter:</strong> Konfiguration unvollständig. Bitte <code>OPS_REPORTER_ENDPOINT</code>, <code>OPS_REPORTER_SITE_ID</code> und <code>OPS_REPORTER_SECRET</code> in <code>wp-config.php</code> setzen.</p></div>';
		return;
	}
	$last = get_option( 'ops_reporter_last_result' );
	if ( is_array( $last ) && empty( $last['ok'] ) ) {
		echo '<div class="notice notice-error"><p><strong>Ops Cockpit Reporter:</strong> Letzter Bericht fehlgeschlagen – ' . esc_html( $last['message'] ) . '</p></div>';
	}
} );
