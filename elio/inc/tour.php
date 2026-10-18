<?php
/**
 * POST /wp-json/elio/v1/tour — validates a tour booking and forwards it to the Google Apps
 * Script web app that appends a row to the school's Google Sheet (google-apps-script/Code.gs
 * in the original project). The webhook secret never leaves the server.
 *
 * @package elio
 */

defined( 'ABSPATH' ) || exit;

const ELIO_AGE_VALUES = [ '18-24m', '2-3y', '4-6y' ];

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'elio/v1',
			'/tour',
			[
				'methods'             => 'POST',
				'callback'            => 'elio_tour_submit',
				'permission_callback' => '__return_true',
			]
		);
	}
);

/** Same rules as the form's own checks. Returns field => error code. */
function elio_tour_validate( array $r ) {
	$errors = [];
	if ( '' === trim( $r['name'] ) ) {
		$errors['name'] = 'required';
	}
	if ( '' === $r['phone'] ) {
		$errors['phone'] = 'required';
	} elseif ( ! preg_match( '/^\d{9,12}$/', $r['phone'] ) ) {
		$errors['phone'] = 'phone';
	}
	if ( ! in_array( $r['age'], ELIO_AGE_VALUES, true ) ) {
		$errors['age'] = 'required';
	}
	// One day of slack for visitors in time zones ahead of the server.
	$yesterday = wp_date( 'Y-m-d', time() - DAY_IN_SECONDS );
	if ( '' === $r['date'] ) {
		$errors['date'] = 'required';
	} elseif ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $r['date'] ) || $r['date'] < $yesterday ) {
		$errors['date'] = 'date';
	}
	return $errors;
}

function elio_tour_submit( WP_REST_Request $request ) {
	$body = $request->get_json_params();
	if ( ! is_array( $body ) ) {
		return new WP_REST_Response( [ 'ok' => false, 'error' => 'invalid_json' ], 400 );
	}

	// Honeypot filled in: answer like a success so bots learn nothing, save nothing.
	if ( ! empty( $body['website'] ) ) {
		return new WP_REST_Response( [ 'ok' => true ], 200 );
	}

	// At most 5 bookings per visitor IP every 10 minutes.
	$ip_key = 'elio_tour_' . md5( $_SERVER['REMOTE_ADDR'] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$count  = (int) get_transient( $ip_key );
	if ( $count >= 5 ) {
		return new WP_REST_Response( [ 'ok' => false, 'error' => 'too_many' ], 429 );
	}

	$req = [
		'name'    => mb_substr( trim( sanitize_text_field( (string) ( $body['name'] ?? '' ) ) ), 0, 200 ),
		'phone'   => mb_substr( (string) ( $body['phone'] ?? '' ), 0, 40 ),
		'age'     => (string) ( $body['age'] ?? '' ),
		'date'    => (string) ( $body['date'] ?? '' ),
		'message' => mb_substr( trim( sanitize_textarea_field( (string) ( $body['message'] ?? '' ) ) ), 0, 2000 ),
	];
	$errors = elio_tour_validate( $req );
	if ( $errors ) {
		return new WP_REST_Response( [ 'ok' => false, 'errors' => $errors ], 422 );
	}

	$s = elio_settings();
	if ( ! $s['webhook_url'] || ! $s['secret'] ) {
		error_log( '[elio tour] Google Sheet webhook / secret not set (Settings → ELIO) — booking NOT saved' );
		return new WP_REST_Response( [ 'ok' => false, 'error' => 'not_configured' ], 503 );
	}

	$age_labels = [ '18-24m' => '18–24 tháng', '2-3y' => '2–3 tuổi', '4-6y' => '4–6 tuổi' ];
	list( $y, $m, $d ) = explode( '-', $req['date'] );
	$payload = wp_json_encode(
		[
			'secret'  => $s['secret'],
			'name'    => $req['name'],
			'phone'   => $req['phone'],
			'age'     => $age_labels[ $req['age'] ],
			'date'    => "$d/$m/$y",
			'message' => $req['message'],
			'lang'    => 'en' === ( $body['lang'] ?? '' ) ? 'English' : 'Tiếng Việt',
		]
	);

	// Apps Script answers a POST with a 302 to googleusercontent.com, which must be fetched
	// with GET; WordPress would re-POST it, so follow that one redirect by hand.
	$res = wp_remote_post(
		$s['webhook_url'],
		[
			'headers'     => [ 'Content-Type' => 'application/json' ],
			'body'        => $payload,
			'timeout'     => 15,
			'redirection' => 0,
		]
	);
	$code = wp_remote_retrieve_response_code( $res );
	if ( ! is_wp_error( $res ) && in_array( $code, [ 301, 302, 303, 307, 308 ], true ) ) {
		$location = wp_remote_retrieve_header( $res, 'location' );
		$res      = $location ? wp_remote_get( WP_Http::make_absolute_url( $location, $s['webhook_url'] ), [ 'timeout' => 15 ] ) : $res;
		$code     = wp_remote_retrieve_response_code( $res );
	}
	$out = is_wp_error( $res ) ? null : json_decode( wp_remote_retrieve_body( $res ), true );

	if ( is_wp_error( $res ) || 200 !== $code || empty( $out['ok'] ) ) {
		// No personal details in logs.
		$why = is_wp_error( $res ) ? $res->get_error_message() : ( $code . ' ' . ( $out['error'] ?? '' ) );
		error_log( '[elio tour] could not save booking to Google Sheet: ' . trim( $why ) );
		return new WP_REST_Response( [ 'ok' => false, 'error' => 'save_failed' ], 502 );
	}

	if ( isset( $out['mailed'] ) && false === $out['mailed'] ) {
		error_log( '[elio tour] booking saved, but the email notification failed: ' . ( $out['mailError'] ?? '' ) );
	}
	set_transient( $ip_key, $count + 1, 10 * MINUTE_IN_SECONDS );
	return new WP_REST_Response( [ 'ok' => true, 'saved' => true, 'mailed' => $out['mailed'] ?? null ], 200 );
}
