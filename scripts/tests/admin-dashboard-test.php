<?php
/**
 * Targeted Local integration tests for Development 1.2.1 / B2a.
 *
 * All fixtures are synthetic, patient-free and removed after the run.
 */

declare(strict_types=1);

define( 'DISABLE_WP_CRON', true );

require '/var/www/html/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
require_once ABSPATH . 'wp-admin/includes/dashboard.php';
require_once ABSPATH . 'wp-admin/includes/screen.php';
require_once ABSPATH . 'wp-admin/includes/template.php';
require_once ABSPATH . 'wp-admin/includes/user.php';

$created_posts = array();
$created_users = array();
$tests_run     = 0;
$exit_code     = 0;

function drthai_b2a_assert( bool $condition, string $label ): void {
	global $tests_run;
	++$tests_run;
	if ( ! $condition ) {
		throw new RuntimeException( "FAIL {$label}" );
	}
	echo "PASS {$label}\n";
}

function drthai_b2a_create_post( string $type, string $status, string $title, int $author = 0 ): int {
	global $created_posts;
	$post_id = wp_insert_post(
		array(
			'post_type'    => $type,
			'post_status'  => $status,
			'post_title'   => $title,
			'post_content' => '<!-- wp:paragraph --><p>Synthetic editorial dashboard fixture.</p><!-- /wp:paragraph -->',
			'post_author'  => $author,
		),
		true
	);
	if ( is_wp_error( $post_id ) ) {
		throw new RuntimeException( 'Unable to create B2a fixture: ' . $post_id->get_error_code() );
	}
	$created_posts[] = (int) $post_id;
	return (int) $post_id;
}

function drthai_b2a_set_dates( int $post_id, string $created, string $modified ): void {
	global $wpdb;
	$wpdb->update(
		$wpdb->posts,
		array(
			'post_date'         => $created,
			'post_date_gmt'     => $created,
			'post_modified'     => $modified,
			'post_modified_gmt' => $modified,
		),
		array( 'ID' => $post_id )
	);
	clean_post_cache( $post_id );
}

function drthai_b2a_set_review( int $post_id, int $reviewer, string $reviewed_at ): void {
	update_post_meta( $post_id, DRTHAI_MEDICAL_REVIEWER_META, $reviewer );
	update_post_meta( $post_id, DRTHAI_REVIEWED_AT_META, $reviewed_at );
}

$baseline_posts    = get_posts( array( 'post_type' => array( 'post', 'page', 'drthai_callback' ), 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'orderby' => 'ID', 'order' => 'ASC' ) );
$baseline_meta     = array( (int) $GLOBALS['wpdb']->get_var( $GLOBALS['wpdb']->prepare( "SELECT COUNT(*) FROM {$GLOBALS['wpdb']->postmeta} WHERE meta_key IN (%s,%s)", DRTHAI_MEDICAL_REVIEWER_META, DRTHAI_REVIEWED_AT_META ) ) );
$baseline_metrics  = null;
$baseline_unique   = 0;
$admin_id          = 0;

try {
	$suffix   = strtolower( wp_generate_password( 8, false, false ) );
	$admin_id = wp_insert_user( array( 'user_login' => "drthai-b2a-admin-{$suffix}", 'user_pass' => wp_generate_password( 32, true, true ), 'user_email' => "drthai-b2a-admin-{$suffix}@example.invalid", 'role' => 'administrator' ) );
	$editor_id = wp_insert_user( array( 'user_login' => "drthai-b2a-editor-{$suffix}", 'user_pass' => wp_generate_password( 32, true, true ), 'user_email' => "drthai-b2a-editor-{$suffix}@example.invalid", 'role' => 'editor' ) );
	if ( is_wp_error( $admin_id ) || is_wp_error( $editor_id ) ) {
		throw new RuntimeException( 'Unable to create B2a users.' );
	}
	$created_users = array( (int) $admin_id, (int) $editor_id );
	wp_set_current_user( (int) $admin_id );
	$baseline_metrics = drthai_health_dashboard_metrics();
	$baseline_unique  = drthai_health_dashboard_unique_actionable_count();

	$recent_booking = drthai_b2a_create_post( 'drthai_callback', 'private', "B2a recent booking {$suffix}" );
	drthai_b2a_set_dates( $recent_booking, gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS ), gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS ) );
	$old_booking = drthai_b2a_create_post( 'drthai_callback', 'private', "B2a old booking {$suffix}" );
	drthai_b2a_set_dates( $old_booking, gmdate( 'Y-m-d H:i:s', time() - 10 * DAY_IN_SECONDS ), gmdate( 'Y-m-d H:i:s', time() - 10 * DAY_IN_SECONDS ) );

	$updated_oldest = drthai_b2a_create_post( 'post', 'draft', "B2a updated oldest {$suffix}", (int) $admin_id );
	$GLOBALS['wpdb']->update( $GLOBALS['wpdb']->posts, array( 'post_status' => 'publish' ), array( 'ID' => $updated_oldest ) );
	drthai_b2a_set_dates( $updated_oldest, '2020-01-01 00:00:00', '2022-01-01 00:00:00' );
	drthai_b2a_set_review( $updated_oldest, (int) $admin_id, '2021-01-01T00:00:00.000000Z' );

	$updated_later = drthai_b2a_create_post( 'post', 'draft', "B2a updated later {$suffix}", (int) $admin_id );
	$GLOBALS['wpdb']->update( $GLOBALS['wpdb']->posts, array( 'post_status' => 'publish' ), array( 'ID' => $updated_later ) );
	drthai_b2a_set_dates( $updated_later, '2020-01-01 00:00:00', '2023-01-01 00:00:00' );
	drthai_b2a_set_review( $updated_later, (int) $admin_id, '2021-01-01T00:00:00.000000Z' );

	$needs_review = drthai_b2a_create_post( 'post', 'draft', "B2a needs review {$suffix}", (int) $admin_id );
	$GLOBALS['wpdb']->update( $GLOBALS['wpdb']->posts, array( 'post_status' => 'publish' ), array( 'ID' => $needs_review ) );
	drthai_b2a_set_dates( $needs_review, '2018-01-01 00:00:00', '2018-01-01 00:00:30' );
	drthai_b2a_set_review( $needs_review, (int) $admin_id, '2017-01-01T00:00:00.000000Z' );

	$never_reviewed = drthai_b2a_create_post( 'post', 'draft', "B2a never reviewed {$suffix}", (int) $admin_id );
	$GLOBALS['wpdb']->update( $GLOBALS['wpdb']->posts, array( 'post_status' => 'publish' ), array( 'ID' => $never_reviewed ) );
	drthai_b2a_set_dates( $never_reviewed, '2019-01-01 00:00:00', '2019-01-01 00:00:00' );

	$health_only = array();
	for ( $index = 1; $index <= 6; ++$index ) {
		$post_id = drthai_b2a_create_post( 'post', 'draft', "B2a editorial {$suffix} {$index}", (int) $admin_id );
		drthai_b2a_set_dates( $post_id, "2024-01-0{$index} 00:00:00", "2024-01-0{$index} 00:00:00" );
		$health_only[] = $post_id;
	}

	$metrics = drthai_health_dashboard_metrics();
	drthai_b2a_assert( $baseline_metrics['booking'] + 1 === $metrics['booking'], 'Booking KPI counts only the recent private request inside seven site-calendar days' );
	drthai_b2a_assert( $baseline_metrics['review'] + 4 === $metrics['review'], 'Review KPI reuses the C4 needs_action union' );
	drthai_b2a_assert( $baseline_metrics['editorial'] + 10 === $metrics['editorial'], 'Editorial KPI reuses C2 attention semantics' );
	drthai_b2a_assert( $baseline_metrics['drafts'] + 6 === $metrics['drafts'], 'Draft KPI uses native Post counts' );

	$queue = drthai_health_dashboard_action_queue( 99 );
	drthai_b2a_assert( count( $queue ) <= DRTHAI_DASHBOARD_QUEUE_LIMIT, 'Action Queue hard limit is five' );
	$queue_ids = array_map( static function ( $item ) { return (int) $item['post']->ID; }, $queue );
	drthai_b2a_assert( count( $queue_ids ) === count( array_unique( $queue_ids ) ), 'Action Queue is deduplicated' );
	drthai_b2a_assert( array( $updated_oldest, $updated_later, $needs_review ) === array_slice( $queue_ids, 0, 3 ), 'priority is deterministic: updated oldest first, then oldest overdue due date' );
	drthai_b2a_assert( array( 1, 1, 2 ) === array_slice( array_column( $queue, 'priority' ), 0, 3 ), 'Updated Since Review outranks Needs Review' );
	drthai_b2a_assert( $baseline_unique + 10 === drthai_health_dashboard_unique_actionable_count(), 'unique actionable total counts the union rather than summing overlapping sets' );

	ob_start();
	drthai_health_render_dashboard_empty_state();
	$empty = (string) ob_get_clean();
	drthai_b2a_assert( false !== strpos( $empty, 'Không có bài viết cần xử lý.' ) && false !== strpos( $empty, 'không có bài quá hạn rà soát' ), 'accessible empty state is available' );

	$admin_actions = drthai_health_dashboard_quick_actions();
	drthai_b2a_assert( 4 === count( $admin_actions ) && in_array( 'Yêu cầu đặt lịch', array_column( $admin_actions, 'label' ), true ), 'Administrator receives exactly four capability-aware quick actions' );
	wp_set_current_user( (int) $editor_id );
	drthai_b2a_assert( null === drthai_health_dashboard_booking_count(), 'unauthorized user cannot read Booking KPI count' );
	drthai_b2a_assert( ! in_array( 'Yêu cầu đặt lịch', array_column( drthai_health_dashboard_quick_actions(), 'label' ), true ), 'unauthorized user does not receive Booking quick action' );
	ob_start();
	drthai_health_render_operational_dashboard();
	$editor_markup = (string) ob_get_clean();
	drthai_b2a_assert( false === strpos( $editor_markup, '7 ngày qua' ) && false === strpos( $editor_markup, 'post_type=drthai_callback' ), 'Dashboard does not expose Booking metric or link without capability' );

	wp_set_current_user( (int) $admin_id );
	ob_start();
	drthai_health_render_operational_dashboard();
	$admin_markup = (string) ob_get_clean();
	drthai_b2a_assert( 4 === substr_count( $admin_markup, 'drthai-dashboard__kpi drthai-dashboard__kpi--' ), 'Administrator overview renders exactly four KPI cards' );
	drthai_b2a_assert( substr_count( $admin_markup, '<li>' ) <= 9, 'rendered queue and quick actions remain bounded' );
	drthai_b2a_assert( false !== strpos( $admin_markup, 'screen-reader-text' ), 'queue retains accessible full context' );

	global $wp_meta_boxes;
	$wp_meta_boxes = array();
	set_current_screen( 'dashboard' );
	add_meta_box( 'dashboard_right_now', 'At a Glance', '__return_empty_string', 'dashboard', 'normal', 'core' );
	add_meta_box( 'dashboard_activity', 'Activity', '__return_empty_string', 'dashboard', 'normal', 'core' );
	add_meta_box( 'dashboard_site_health', 'Site Health', '__return_empty_string', 'dashboard', 'normal', 'core' );
	add_meta_box( 'dashboard_quick_press', 'Quick Draft', '__return_empty_string', 'dashboard', 'side', 'core' );
	add_meta_box( 'dashboard_primary', 'Events and News', '__return_empty_string', 'dashboard', 'side', 'core' );
	add_meta_box( 'wpseo-dashboard-overview', 'Yoast SEO Posts Overview', '__return_empty_string', 'dashboard', 'normal', 'core' );
	add_meta_box( 'wpseo-wincher-dashboard-overview', 'Yoast SEO / Wincher: Top Keyphrases', '__return_empty_string', 'dashboard', 'normal', 'core' );
	drthai_health_setup_operational_dashboard();
	drthai_b2a_assert( false === $wp_meta_boxes['dashboard']['normal']['core']['dashboard_right_now'] && false === $wp_meta_boxes['dashboard']['side']['core']['dashboard_primary'], 'At a Glance and WordPress Events/News are removed' );
	drthai_b2a_assert( false === $wp_meta_boxes['dashboard']['normal']['core']['dashboard_activity'] && false === $wp_meta_boxes['dashboard']['side']['core']['dashboard_quick_press'], 'Activity and Quick Draft are removed from the default Dashboard' );
	drthai_b2a_assert( false === $wp_meta_boxes['dashboard']['normal']['core']['wpseo-dashboard-overview'] && false === $wp_meta_boxes['dashboard']['normal']['core']['wpseo-wincher-dashboard-overview'], 'Yoast overview widgets are removed without disabling Yoast SEO' );
	drthai_b2a_assert( isset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_site_health'] ), 'Site Health is retained below the operational widget' );
	drthai_b2a_assert( isset( $wp_meta_boxes['dashboard']['normal']['high']['drthai_health_operational_dashboard'] ), 'operational widget is placed first in the native Dashboard' );
	drthai_b2a_assert( 'Tổng quan vận hành' === $wp_meta_boxes['dashboard']['normal']['high']['drthai_health_operational_dashboard']['title'], 'operational widget has one clear native title' );
} catch ( Throwable $error ) {
	fwrite( STDERR, $error->getMessage() . "\n" );
	$exit_code = 1;
} finally {
	wp_set_current_user( 0 );
	foreach ( array_reverse( $created_posts ) as $post_id ) {
		wp_delete_post( $post_id, true );
	}
	foreach ( array_reverse( $created_users ) as $user_id ) {
		wp_delete_user( $user_id );
	}
}

$final_posts = get_posts( array( 'post_type' => array( 'post', 'page', 'drthai_callback' ), 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'orderby' => 'ID', 'order' => 'ASC' ) );
$final_meta  = array( (int) $GLOBALS['wpdb']->get_var( $GLOBALS['wpdb']->prepare( "SELECT COUNT(*) FROM {$GLOBALS['wpdb']->postmeta} WHERE meta_key IN (%s,%s)", DRTHAI_MEDICAL_REVIEWER_META, DRTHAI_REVIEWED_AT_META ) ) );
drthai_b2a_assert( $baseline_posts === $final_posts, 'existing Post, Page and Booking inventories are restored exactly' );
drthai_b2a_assert( $baseline_meta === $final_meta, 'existing review metadata inventory is restored exactly' );

if ( $exit_code ) {
	exit( $exit_code );
}

echo "B2A_TESTS_RUN={$tests_run}\n";
echo "B2A_TEST_STATUS=PASS\n";
