<?php
/**
 * Plugin Name:       Ops Cockpit Reporter
 * Plugin URI:        https://dashboard.deineagentur.at
 * Description:        Read-only Telemetrie-Reporter. Sendet 2x täglich einen signierten, ausgehenden Statusbericht (Versionen, Plugins, Themes, Fingerprint-Signale) an das zentrale Ops-Cockpit. Nimmt KEINE eingehenden Befehle entgegen. Konfiguration & Verbindungstest im Backend unter Einstellungen → Ops Cockpit Reporter.
 * Version:           1.1.0
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

define( 'OPS_REPORTER_VERSION', '1.1.0' );
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
 * Konfiguration auflösen. Reihenfolge: Konstanten (wp-config.php / mu-plugin) haben
 * Vorrang vor den im Backend gespeicherten Optionen. So bleibt der sichere Weg
 * (Secret in wp-config.php) möglich, während die Backend-Eingabe den bequemen Weg bietet.
 *
 * @return array{endpoint:string,site_id:string,secret:string,from_constants:bool}
 */
function ops_reporter_get_config() {
	$from_constants = defined( 'OPS_REPORTER_ENDPOINT' ) || defined( 'OPS_REPORTER_SITE_ID' ) || defined( 'OPS_REPORTER_SECRET' );

	return array(
		'endpoint'       => defined( 'OPS_REPORTER_ENDPOINT' ) ? OPS_REPORTER_ENDPOINT : (string) get_option( 'ops_reporter_endpoint', '' ),
		'site_id'        => defined( 'OPS_REPORTER_SITE_ID' )  ? OPS_REPORTER_SITE_ID  : (string) get_option( 'ops_reporter_site_id', '' ),
		'secret'         => defined( 'OPS_REPORTER_SECRET' )   ? OPS_REPORTER_SECRET   : (string) get_option( 'ops_reporter_secret', '' ),
		'from_constants' => $from_constants,
	);
}

/**
 * Hauptlauf: sammeln, signieren, pushen.
 *
 * @return array{ok:bool,message:string,code?:int}
 */
function ops_reporter_run() {
	$cfg = ops_reporter_get_config();

	if ( '' === $cfg['endpoint'] || '' === $cfg['site_id'] || '' === $cfg['secret'] ) {
		return ops_reporter_log_result( false, 'Konfiguration unvollständig: Endpoint, Site-ID und Secret setzen (Einstellungen → Ops Cockpit Reporter, oder in wp-config.php).' );
	}

	$report = ops_reporter_collect();

	$payload = array(
		'site_id' => $cfg['site_id'],
		'sent_at' => time(),                 // Unix-Timestamp (UTC) für Replay-Fenster
		'nonce'   => wp_generate_password( 24, false, false ),
		'report'  => $report,
	);

	$body      = wp_json_encode( $payload );
	$timestamp = (string) $payload['sent_at'];
	$signature = hash_hmac( 'sha256', $timestamp . '.' . $body, $cfg['secret'] );

	$response = wp_remote_post(
		$cfg['endpoint'],
		array(
			'timeout'     => 20,
			'redirection' => 0,
			'sslverify'   => true, // NIEMALS abschalten
			'blocking'    => true,
			'headers'     => array(
				'Content-Type'    => 'application/json',
				'X-Ops-Site'      => $cfg['site_id'],
				'X-Ops-Timestamp' => $timestamp,
				'X-Ops-Signature' => $signature,
				'X-Ops-Reporter'  => OPS_REPORTER_VERSION,
				'User-Agent'      => 'OpsReporter/' . OPS_REPORTER_VERSION,
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
	$cfg        = ops_reporter_get_config();
	$configured = '' !== $cfg['endpoint'] && '' !== $cfg['site_id'] && '' !== $cfg['secret'];
	$url        = esc_url( admin_url( 'options-general.php?page=ops-reporter' ) );

	if ( ! $configured ) {
		echo '<div class="notice notice-warning"><p><strong>Ops Cockpit Reporter:</strong> Konfiguration unvollständig. Bitte unter <a href="' . $url . '">Einstellungen → Ops Cockpit Reporter</a> Endpoint, Site-ID und Secret eintragen.</p></div>';
		return;
	}
	$last = get_option( 'ops_reporter_last_result' );
	if ( is_array( $last ) && empty( $last['ok'] ) ) {
		echo '<div class="notice notice-error"><p><strong>Ops Cockpit Reporter:</strong> Letzter Bericht fehlgeschlagen – ' . esc_html( $last['message'] ) . ' (<a href="' . $url . '">Einstellungen</a>)</p></div>';
	}
} );

/* -------------------------------------------------------------------------
 *  Einstellungsseite (Einstellungen → Ops Cockpit Reporter) + Verbindungstest
 * ---------------------------------------------------------------------- */

add_action( 'admin_menu', function () {
	add_options_page(
		'Ops Cockpit Reporter',
		'Ops Cockpit Reporter',
		'manage_options',
		'ops-reporter',
		'ops_reporter_render_settings_page'
	);
} );

/**
 * Rendert die Einstellungsseite und verarbeitet Speichern + Verbindungstest.
 * Sicherheit: Capability-Check, Nonce, Sanitization der Eingaben, Escaping der Ausgaben.
 * Das Secret wird nie zurück ins Feld geschrieben (write-only).
 */
function ops_reporter_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$notice = '';
	$cfg    = ops_reporter_get_config();

	if ( isset( $_POST['ops_reporter_action'] ) && check_admin_referer( 'ops_reporter_settings' ) ) {
		$action = sanitize_text_field( wp_unslash( $_POST['ops_reporter_action'] ) );

		// Speichern nur, wenn nicht über Konstanten vorgegeben.
		if ( ! $cfg['from_constants'] ) {
			update_option( 'ops_reporter_endpoint', esc_url_raw( wp_unslash( $_POST['ops_reporter_endpoint'] ?? '' ) ) );
			update_option( 'ops_reporter_site_id', sanitize_text_field( wp_unslash( $_POST['ops_reporter_site_id'] ?? '' ) ) );

			$secret_in = trim( (string) wp_unslash( $_POST['ops_reporter_secret'] ?? '' ) );
			if ( '' !== $secret_in ) {
				update_option( 'ops_reporter_secret', $secret_in );
			}
			$cfg = ops_reporter_get_config();
		}

		if ( 'test' === $action ) {
			$res = ops_reporter_run();
			$notice = $res['ok']
				? '<div class="notice notice-success is-dismissible"><p><strong>Test erfolgreich:</strong> ' . esc_html( $res['message'] ) . ( isset( $res['code'] ) ? ' (HTTP ' . (int) $res['code'] . ')' : '' ) . '</p></div>'
				: '<div class="notice notice-error is-dismissible"><p><strong>Test fehlgeschlagen:</strong> ' . esc_html( $res['message'] ) . '</p></div>';
		} else {
			$notice = '<div class="notice notice-success is-dismissible"><p>Einstellungen gespeichert.</p></div>';
		}
	}

	$endpoint   = $cfg['endpoint'];
	$site_id    = $cfg['site_id'];
	$has_secret = '' !== $cfg['secret'];
	$locked     = $cfg['from_constants'];
	?>
	<div class="wrap">
		<h1>Ops Cockpit Reporter</h1>
		<p>Verbindet diese Website read-only mit dem zentralen Ops Cockpit. Trage Endpoint, Site-ID und Secret aus dem Cockpit ein und teste die Verbindung.</p>
		<?php echo $notice; // bereits escaped ?>
		<?php if ( $locked ) : ?>
			<div class="notice notice-info inline"><p>Die Konfiguration wird über <code>wp-config.php</code> (oder ein mu-plugin) vorgegeben und ist hier schreibgeschützt. Der Verbindungstest funktioniert trotzdem.</p></div>
		<?php endif; ?>
		<form method="post">
			<?php wp_nonce_field( 'ops_reporter_settings' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="ops_reporter_endpoint">Cockpit-Endpoint</label></th>
					<td>
						<input name="ops_reporter_endpoint" id="ops_reporter_endpoint" type="url" class="regular-text" value="<?php echo esc_attr( $endpoint ); ?>" placeholder="https://webmonitor.hammerer.at/api/ingest" <?php disabled( $locked ); ?>>
						<p class="description">Die Ingest-URL deines Cockpits (endet auf <code>/api/ingest</code>).</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="ops_reporter_site_id">Site-ID</label></th>
					<td>
						<input name="ops_reporter_site_id" id="ops_reporter_site_id" type="text" class="regular-text" value="<?php echo esc_attr( $site_id ); ?>" placeholder="z. B. dev-vorlage" <?php disabled( $locked ); ?>>
						<p class="description">Muss exakt der Site-ID im Cockpit entsprechen.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="ops_reporter_secret">Secret</label></th>
					<td>
						<input name="ops_reporter_secret" id="ops_reporter_secret" type="password" class="regular-text" autocomplete="new-password" placeholder="<?php echo $has_secret ? '•••••••• (unverändert lassen)' : 'Secret aus dem Cockpit einfügen'; ?>" <?php disabled( $locked ); ?>>
						<p class="description"><?php echo $has_secret ? 'Ein Secret ist gespeichert. Feld leer lassen, um es zu behalten.' : 'Noch kein Secret gespeichert.'; ?></p>
					</td>
				</tr>
			</table>
			<p class="submit">
				<?php if ( ! $locked ) : ?>
					<button type="submit" name="ops_reporter_action" value="save" class="button button-primary">Speichern</button>
					<button type="submit" name="ops_reporter_action" value="test" class="button button-secondary">Speichern &amp; Verbindung testen</button>
				<?php else : ?>
					<button type="submit" name="ops_reporter_action" value="test" class="button button-primary">Verbindung testen</button>
				<?php endif; ?>
			</p>
		</form>
		<?php
		$last = get_option( 'ops_reporter_last_result' );
		if ( is_array( $last ) ) :
			?>
			<h2>Letzter Lauf</h2>
			<p>
				<?php echo ! empty( $last['ok'] ) ? '✅' : '❌'; ?>
				<?php echo esc_html( (string) ( $last['message'] ?? '' ) ); ?>
				<?php if ( ! empty( $last['time'] ) ) : ?>
					<em>(<?php echo esc_html( (string) $last['time'] ); ?> UTC)</em>
				<?php endif; ?>
			</p>
		<?php endif; ?>
	</div>
	<?php
}
