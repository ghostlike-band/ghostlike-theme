<?php
/**
 * Updates per Klick: fragt das neueste GitHub-Release ab und meldet es
 * WordPress wie ein normales Theme-Update (Dashboard → Aktualisierungen).
 *
 * Erwartet am Release eine Datei „ghostlike.zip“ mit dem Ordner „ghostlike/“.
 * Die baut die GitHub Action automatisch, sobald ein Tag „v1.2.3“ gepusht wird.
 */

defined( 'ABSPATH' ) || exit;

const GL_RELEASE_CACHE = 'gl_github_release';

function gl_latest_release( $force = false ) {
	if ( ! $force ) {
		$cached = get_site_transient( GL_RELEASE_CACHE );
		if ( $cached !== false ) {
			return $cached ?: null;
		}
	}

	// 1. Versionsdatei über den normalen Download-Weg (kein API-Limit, wichtig bei geteilter IP)
	$release = null;
	$res     = wp_remote_get( 'https://github.com/' . GL_GITHUB_REPO . '/releases/latest/download/release.json', array(
		'timeout'     => 15,
		'redirection' => 5,
		'headers'     => array( 'Accept' => 'application/json', 'User-Agent' => 'ghostlike-theme/' . GL_VERSION ),
	) );
	if ( ! is_wp_error( $res ) && wp_remote_retrieve_response_code( $res ) === 200 ) {
		$d = json_decode( wp_remote_retrieve_body( $res ), true );
		if ( ! empty( $d['version'] ) && ! empty( $d['package'] ) ) {
			$release = array(
				'version' => ltrim( (string) $d['version'], 'vV' ),
				'package' => (string) $d['package'],
				'url'     => (string) ( $d['url'] ?? '' ),
				'notes'   => (string) ( $d['notes'] ?? '' ),
				'date'    => (string) ( $d['date'] ?? '' ),
			);
		}
	}

	// 2. Ersatzweg: GitHub-API (max. 60 Anfragen/Stunde pro IP)
	if ( ! $release ) {
		$res = wp_remote_get( 'https://api.github.com/repos/' . GL_GITHUB_REPO . '/releases/latest', array(
			'timeout' => 10,
			'headers' => array( 'Accept' => 'application/vnd.github+json', 'User-Agent' => 'ghostlike-theme/' . GL_VERSION ),
		) );
		$code = is_wp_error( $res ) ? 0 : wp_remote_retrieve_response_code( $res );
		if ( $code === 200 ) {
			$data    = json_decode( wp_remote_retrieve_body( $res ), true );
			$package = '';
			foreach ( (array) ( $data['assets'] ?? array() ) as $a ) {
				if ( ( $a['name'] ?? '' ) === 'ghostlike.zip' ) {
					$package = $a['browser_download_url'];
				}
			}
			if ( ! empty( $data['tag_name'] ) && $package ) {
				$release = array(
					'version' => ltrim( $data['tag_name'], 'vV' ),
					'package' => $package,
					'url'     => $data['html_url'] ?? '',
					'notes'   => $data['body'] ?? '',
					'date'    => $data['published_at'] ?? '',
				);
			}
		}
		if ( ! $release ) {
			$why = is_wp_error( $res ) ? $res->get_error_message() : ( $code === 403 ? 'GitHub-Abfragelimit erreicht, später erneut versuchen' : 'GitHub antwortet mit ' . $code );
			update_option( 'gl_update_error', $why . ' (' . wp_date( 'd.m. H:i' ) . ')', false );
			set_site_transient( GL_RELEASE_CACHE, 0, 15 * MINUTE_IN_SECONDS );
			return null;
		}
	}

	delete_option( 'gl_update_error' );
	set_site_transient( GL_RELEASE_CACHE, $release, 6 * HOUR_IN_SECONDS );
	return $release;
}

add_filter( 'pre_set_site_transient_update_themes', function ( $transient ) {
	if ( ! is_object( $transient ) ) {
		return $transient;
	}
	$slug    = get_template();
	$release = gl_latest_release();
	if ( ! $release ) {
		return $transient;
	}
	$item = array(
		'theme'        => $slug,
		'new_version'  => $release['version'],
		'url'          => $release['url'],
		'package'      => $release['package'],
		'requires'     => '6.4',
		'requires_php' => '8.0',
	);
	if ( version_compare( $release['version'], GL_VERSION, '>' ) ) {
		$transient->response[ $slug ] = $item;
	} else {
		unset( $transient->response[ $slug ] );
		$transient->no_update[ $slug ] = $item;
	}
	return $transient;
} );

// Falls der Theme-Ordner anders heißt als im ZIP: beim Update auf den bestehenden Namen umbenennen.
add_filter( 'upgrader_source_selection', function ( $source, $remote_source, $upgrader, $hook_extra = array() ) {
	if ( empty( $hook_extra['theme'] ) || $hook_extra['theme'] !== get_template() ) {
		return $source;
	}
	$desired = trailingslashit( $remote_source ) . $hook_extra['theme'] . '/';
	if ( untrailingslashit( $source ) === untrailingslashit( $desired ) ) {
		return $source;
	}
	global $wp_filesystem;
	if ( $wp_filesystem && $wp_filesystem->move( $source, $desired, true ) ) {
		return $desired;
	}
	return $source;
}, 10, 4 );

// Nach einem Update den Cache leeren.
add_action( 'upgrader_process_complete', function () {
	delete_site_transient( GL_RELEASE_CACHE );
} );
