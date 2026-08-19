<?php
/**
 * Derived medical content lifecycle for native WordPress Posts.
 *
 * @package DrThai_Health
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return the centrally configured periodic review interval.
 *
 * @return int Calendar months.
 */
function drthai_content_review_interval_months() {
	return max( 1, absint( apply_filters( 'drthai_content_review_interval_months', 12 ) ) );
}

/**
 * Return the maximum timestamp-noise grace after initial publication.
 *
 * @return int Seconds, never more than 60.
 */
function drthai_content_update_grace_seconds() {
	return min( 60, max( 0, absint( apply_filters( 'drthai_content_update_grace_seconds', 60 ) ) ) );
}

/**
 * Parse a stored C1 UTC timestamp.
 *
 * @param string $value Timestamp.
 * @return DateTimeImmutable|false
 */
function drthai_health_content_lifecycle_parse_utc( $value ) {
	if ( '' === $value || $value !== drthai_health_sanitize_reviewed_at( $value ) ) {
		return false;
	}

	try {
		return new DateTimeImmutable( $value, new DateTimeZone( 'UTC' ) );
	} catch ( Exception $exception ) {
		return false;
	}
}

/**
 * Derive lifecycle state and dates without writing any content data.
 *
 * @param int                    $post_id Post ID.
 * @param DateTimeImmutable|null $now     Optional deterministic UTC time.
 * @return array{state:string,label:string,anchor:?DateTimeImmutable,due:?DateTimeImmutable,modified:?DateTimeImmutable}
 */
function drthai_health_get_content_lifecycle( $post_id, $now = null ) {
	$post = get_post( $post_id );
	if ( ! $post || 'post' !== $post->post_type || ! in_array( $post->post_status, array( 'publish', 'future' ), true ) ) {
		return array(
			'state'    => 'pre_publication',
			'label'    => __( 'Pre-publication', 'drthai-health' ),
			'anchor'   => null,
			'due'      => null,
			'modified' => null,
		);
	}

	$reviewed_at = drthai_health_content_lifecycle_parse_utc( (string) get_post_meta( $post_id, DRTHAI_REVIEWED_AT_META, true ) );
	if ( ! drthai_health_post_has_valid_medical_review( $post_id ) || ! $reviewed_at ) {
		return array(
			'state'    => 'never_reviewed',
			'label'    => __( 'Never Reviewed', 'drthai-health' ),
			'anchor'   => null,
			'due'      => null,
			'modified' => null,
		);
	}

	$utc          = new DateTimeZone( 'UTC' );
	$published_at = new DateTimeImmutable( $post->post_date_gmt . ' UTC', $utc );
	$modified_at  = new DateTimeImmutable( $post->post_modified_gmt . ' UTC', $utc );
	$anchor       = $reviewed_at > $published_at ? $reviewed_at : $published_at;
	$due          = $anchor->modify( '+' . drthai_content_review_interval_months() . ' months' );
	$now          = $now instanceof DateTimeImmutable ? $now->setTimezone( $utc ) : new DateTimeImmutable( 'now', $utc );
	$grace_end    = $anchor->modify( '+' . drthai_content_update_grace_seconds() . ' seconds' );

	if ( $modified_at > $grace_end ) {
		$state = 'updated_since_review';
		$label = __( 'Updated Since Review', 'drthai-health' );
	} elseif ( $now >= $due ) {
		$state = 'needs_review';
		$label = __( 'Needs Review', 'drthai-health' );
	} else {
		$state = 'current';
		$label = __( 'Current', 'drthai-health' );
	}

	return array(
		'state'    => $state,
		'label'    => $label,
		'anchor'   => $anchor,
		'due'      => $due,
		'modified' => $modified_at,
	);
}

/**
 * Format a UTC lifecycle date using the site timezone.
 *
 * @param DateTimeImmutable|null $date Date to format.
 * @param bool                   $include_time Include time.
 * @return string
 */
function drthai_health_format_lifecycle_date( $date, $include_time = false ) {
	if ( ! $date instanceof DateTimeImmutable ) {
		return '';
	}
	$format = get_option( 'date_format' );
	if ( $include_time ) {
		$format .= ' ' . get_option( 'time_format' );
	}

	return wp_date( $format, $date->getTimestamp(), wp_timezone() );
}

/**
 * Show read-only lifecycle context in the existing C1 review box.
 *
 * @param WP_Post $post Current Post.
 */
function drthai_health_render_lifecycle_review_context( $post ) {
	$lifecycle = drthai_health_get_content_lifecycle( $post->ID );
	echo '<hr>';
	echo '<p><strong>' . esc_html__( 'Lifecycle:', 'drthai-health' ) . '</strong> ' . esc_html( $lifecycle['label'] ) . '</p>';
	if ( $lifecycle['due'] ) {
		echo '<p><strong>' . esc_html__( 'Review due:', 'drthai-health' ) . '</strong> ' . esc_html( drthai_health_format_lifecycle_date( $lifecycle['due'] ) ) . '</p>';
	}
	if ( 'updated_since_review' === $lifecycle['state'] ) {
		echo '<p>' . esc_html__( 'This Post was updated after its review anchor. Re-review it when medically appropriate.', 'drthai-health' ) . '</p>';
	}
}
add_action( 'drthai_health_medical_review_status', 'drthai_health_render_lifecycle_review_context' );

/**
 * Build database-side predicates matching the derived lifecycle states.
 *
 * @return array
 */
function drthai_health_content_lifecycle_sql_predicates() {
	global $wpdb;
	$c2          = drthai_health_editorial_admin_sql_predicates();
	$published   = "{$wpdb->posts}.post_status IN ('publish','future')";
	$review_sql  = "({$c2['review']})";
	$review_key  = esc_sql( DRTHAI_REVIEWED_AT_META );
	$now         = esc_sql( current_time( 'mysql', true ) );
	$interval    = drthai_content_review_interval_months();
	$grace       = drthai_content_update_grace_seconds();
	$review_time = "STR_TO_DATE(LEFT(review_time.meta_value, 19), '%Y-%m-%dT%H:%i:%s')";
	$anchor      = "GREATEST({$review_time}, {$wpdb->posts}.post_date_gmt)";
	$current_condition = "DATE_ADD({$anchor}, INTERVAL {$interval} MONTH) > '{$now}' AND {$wpdb->posts}.post_modified_gmt <= DATE_ADD({$anchor}, INTERVAL {$grace} SECOND)";
	$due_condition = "DATE_ADD({$anchor}, INTERVAL {$interval} MONTH) <= '{$now}' AND {$wpdb->posts}.post_modified_gmt <= DATE_ADD({$anchor}, INTERVAL {$grace} SECOND)";
	$updated_condition = "{$wpdb->posts}.post_modified_gmt > DATE_ADD({$anchor}, INTERVAL {$grace} SECOND)";
	$wrap = static function ( $condition ) use ( $wpdb, $review_key ) {
		return "EXISTS (SELECT 1 FROM {$wpdb->postmeta} review_time WHERE review_time.post_id = {$wpdb->posts}.ID AND review_time.meta_key = '{$review_key}' AND {$condition})";
	};
	$current = "{$published} AND {$review_sql} AND " . $wrap( $current_condition );
	$needs = "{$published} AND {$review_sql} AND " . $wrap( $due_condition );
	$updated = "{$published} AND {$review_sql} AND " . $wrap( $updated_condition );
	$never = "{$published} AND NOT ({$review_sql})";

	return array(
		'current'              => $current,
		'needs_review'         => $needs,
		'never_reviewed'       => $never,
		'updated_since_review' => $updated,
		'needs_action'         => "({$never}) OR ({$needs}) OR ({$updated})",
	);
}
