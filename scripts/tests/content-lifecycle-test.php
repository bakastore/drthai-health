<?php
/**
 * Local integration tests for Content Operations 1.2 / C4.
 *
 * All fixtures are synthetic, patient-free and removed after the run.
 */

declare(strict_types=1);

define( 'DISABLE_WP_CRON', true );

require '/var/www/html/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/user.php';
require_once ABSPATH . 'wp-admin/includes/template.php';

$created_posts = array();
$created_users = array();
$created_terms = array();
$tests_run = 0;
$exit_code = 0;

function drthai_c4_assert( bool $condition, string $label ): void {
	global $tests_run;
	++$tests_run;
	if ( ! $condition ) {
		throw new RuntimeException( "FAIL {$label}" );
	}
	echo "PASS {$label}\n";
}

function drthai_c4_create_post( int $author, string $suffix, string $status = 'draft' ): int {
	global $created_posts;
	$post_id = wp_insert_post(
		array(
			'post_type'    => 'post',
			'post_status'  => $status,
			'post_title'   => "C4 synthetic general health {$suffix}",
			'post_content' => '<!-- wp:paragraph --><p>Synthetic general health editorial fixture.</p><!-- /wp:paragraph -->',
			'post_excerpt' => 'Synthetic editorial excerpt.',
			'post_author'  => $author,
		),
		true
	);
	if ( is_wp_error( $post_id ) ) {
		throw new RuntimeException( 'Unable to create C4 Post: ' . $post_id->get_error_code() );
	}
	$created_posts[] = (int) $post_id;
	return (int) $post_id;
}

function drthai_c4_set_post( int $post_id, string $status, string $published, string $modified ): void {
	global $wpdb;
	$wpdb->update(
		$wpdb->posts,
		array(
			'post_status'       => $status,
			'post_date'         => $published,
			'post_date_gmt'     => $published,
			'post_modified'     => $modified,
			'post_modified_gmt' => $modified,
		),
		array( 'ID' => $post_id )
	);
	clean_post_cache( $post_id );
}

function drthai_c4_set_review( int $post_id, int $reviewer, string $reviewed_at ): void {
	update_post_meta( $post_id, DRTHAI_MEDICAL_REVIEWER_META, $reviewer );
	update_post_meta( $post_id, DRTHAI_REVIEWED_AT_META, $reviewed_at );
}

function drthai_c4_query( array $source, array $args ): WP_Query {
	$filters = drthai_health_editorial_admin_sanitize_filters( $source );
	$args['drthai_c2_active'] = true;
	$args['drthai_c2_review_status'] = $filters['review_status'];
	$args['drthai_c2_reviewer_id'] = $filters['reviewer_id'];
	$args['drthai_c2_media_status'] = $filters['media_status'];
	$args['drthai_c2_health_status'] = $filters['health_status'];
	$args['drthai_c4_lifecycle'] = $filters['lifecycle'];
	return new WP_Query( $args );
}

function drthai_c4_ids( WP_Query $query ): array {
	return array_map( 'intval', wp_list_pluck( $query->posts, 'ID' ) );
}

function drthai_c4_column( int $post_id ): string {
	ob_start();
	drthai_health_render_editorial_admin_column( 'drthai_lifecycle', $post_id );
	return trim( (string) ob_get_clean() );
}

$baseline_posts = get_posts( array( 'post_type' => array( 'post', 'page', 'revision' ), 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'orderby' => 'ID', 'order' => 'ASC' ) );
$baseline_terms = get_terms( array( 'taxonomy' => 'category', 'hide_empty' => false, 'fields' => 'ids' ) );
$baseline_attachments = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'posts_per_page' => -1, 'fields' => 'ids', 'orderby' => 'ID', 'order' => 'ASC' ) );
$baseline_comments = get_comments( array( 'fields' => 'ids', 'orderby' => 'comment_ID', 'order' => 'ASC' ) );
$reference_id = 24;
$reference_meta = array(
	'reviewer' => get_post_meta( $reference_id, DRTHAI_MEDICAL_REVIEWER_META, true ),
	'reviewed' => get_post_meta( $reference_id, DRTHAI_REVIEWED_AT_META, true ),
);
$reference_dates = array( get_post_field( 'post_date_gmt', $reference_id ), get_post_field( 'post_modified_gmt', $reference_id ) );
$reference_revisions = wp_get_post_revisions( $reference_id, array( 'fields' => 'ids' ) );

try {
	global $wpdb;
	$suffix = strtolower( wp_generate_password( 8, false, false ) );
	$user_id = wp_insert_user( array( 'user_login' => "drthai-c4-admin-{$suffix}", 'user_pass' => wp_generate_password( 32, true, true ), 'user_email' => "drthai-c4-{$suffix}@example.invalid", 'role' => 'administrator' ) );
	if ( is_wp_error( $user_id ) ) {
		throw new RuntimeException( 'Unable to create C4 reviewer.' );
	}
	$created_users[] = (int) $user_id;
	wp_set_current_user( (int) $user_id );
	$term = wp_insert_term( "C4 Synthetic {$suffix}", 'category', array( 'slug' => "c4-synthetic-{$suffix}" ) );
	if ( is_wp_error( $term ) ) {
		throw new RuntimeException( 'Unable to create C4 category.' );
	}
	$category_id = (int) $term['term_id'];
	$created_terms[] = $category_id;

	$draft = drthai_c4_create_post( (int) $user_id, "draft-{$suffix}" );
	$pending = drthai_c4_create_post( (int) $user_id, "pending-{$suffix}", 'pending' );
	$never = drthai_c4_create_post( (int) $user_id, "never-{$suffix}" );
	drthai_c4_set_post( $never, 'publish', '2026-01-01 00:00:00', '2026-01-01 00:00:00' );
	$missing_date = drthai_c4_create_post( (int) $user_id, "missing-date-{$suffix}" );
	drthai_c4_set_post( $missing_date, 'publish', '2026-01-01 00:00:00', '2026-01-01 00:00:00' );
	update_post_meta( $missing_date, DRTHAI_MEDICAL_REVIEWER_META, (int) $user_id );
	$current = drthai_c4_create_post( (int) $user_id, "current-search-{$suffix}" );
	drthai_c4_set_post( $current, 'publish', '2026-04-01 00:00:00', '2026-04-01 00:00:30' );
	drthai_c4_set_review( $current, (int) $user_id, '2026-03-01T00:00:00.000000Z' );
	$review_later = drthai_c4_create_post( (int) $user_id, "review-later-{$suffix}" );
	drthai_c4_set_post( $review_later, 'publish', '2025-01-01 00:00:00', '2026-02-01 00:00:30' );
	drthai_c4_set_review( $review_later, (int) $user_id, '2026-02-01T00:00:00.000000Z' );
	$overdue = drthai_c4_create_post( (int) $user_id, "overdue-{$suffix}" );
	drthai_c4_set_post( $overdue, 'publish', '2024-01-01 00:00:00', '2024-01-01 00:00:30' );
	drthai_c4_set_review( $overdue, (int) $user_id, '2023-12-01T00:00:00.000000Z' );
	$updated = drthai_c4_create_post( (int) $user_id, "updated-{$suffix}" );
	drthai_c4_set_post( $updated, 'publish', '2026-01-01 00:00:00', '2026-02-01 00:00:00' );
	drthai_c4_set_review( $updated, (int) $user_id, '2025-12-01T00:00:00.000000Z' );
	$scheduled = drthai_c4_create_post( (int) $user_id, "scheduled-{$suffix}" );
	drthai_c4_set_post( $scheduled, 'future', '2026-12-01 00:00:00', '2026-12-01 00:00:30' );
	drthai_c4_set_review( $scheduled, (int) $user_id, '2026-08-01T00:00:00.000000Z' );
	foreach ( array( $current, $review_later, $overdue, $updated ) as $post_id ) {
		wp_set_post_categories( $post_id, array( $category_id ) );
	}

	$now = new DateTimeImmutable( '2026-08-18T12:00:00Z' );
	drthai_c4_assert( 12 === drthai_content_review_interval_months(), 'review interval is centralized at 12 calendar months' );
	drthai_c4_assert( drthai_content_update_grace_seconds() <= 60, 'initial-publication grace never exceeds 60 seconds' );
	drthai_c4_assert( 'pre_publication' === drthai_health_get_content_lifecycle( $draft, $now )['state'], 'Draft Post is PRE-PUBLICATION' );
	drthai_c4_assert( 'pre_publication' === drthai_health_get_content_lifecycle( $pending, $now )['state'], 'Pending Post is PRE-PUBLICATION' );
	drthai_c4_assert( 'never_reviewed' === drthai_health_get_content_lifecycle( $never, $now )['state'], 'Published Post without reviewer is NEVER REVIEWED' );
	drthai_c4_assert( 'never_reviewed' === drthai_health_get_content_lifecycle( $missing_date, $now )['state'], 'Published Post without Reviewed At is NEVER REVIEWED' );
	$current_state = drthai_health_get_content_lifecycle( $current, $now );
	drthai_c4_assert( 'current' === $current_state['state'], 'valid reviewed Published Post within interval is CURRENT' );
	drthai_c4_assert( '2026-04-01 00:00:00' === $current_state['anchor']->format( 'Y-m-d H:i:s' ), 'publication time wins when later than review' );
	drthai_c4_assert( '2027-04-01 00:00:00' === $current_state['due']->format( 'Y-m-d H:i:s' ), 'Review Due is anchor plus 12 calendar months' );
	drthai_c4_assert( '2026-02-01 00:00:00' === drthai_health_get_content_lifecycle( $review_later, $now )['anchor']->format( 'Y-m-d H:i:s' ), 'Reviewed At wins when later than publication' );
	drthai_c4_assert( 'needs_review' === drthai_health_get_content_lifecycle( $overdue, $now )['state'], 'overdue reviewed Post is NEEDS REVIEW' );
	drthai_c4_assert( 'updated_since_review' === drthai_health_get_content_lifecycle( $updated, $now )['state'], 'Post modified after comparison anchor is UPDATED SINCE REVIEW' );
	drthai_c4_assert( 'current' === $current_state['state'], 'initial Publish timestamp noise does not become UPDATED SINCE REVIEW' );
	drthai_c4_assert( 'current' === drthai_health_get_content_lifecycle( $scheduled, $now )['state'], 'Scheduled transition does not create update false positive' );
	$meta_before = get_post_meta( $updated );
	$status_before = get_post_status( $updated );
	drthai_health_get_content_lifecycle( $updated, $now );
	drthai_c4_assert( $meta_before === get_post_meta( $updated ), 'lifecycle calculation does not write Post Meta' );
	drthai_c4_assert( $status_before === get_post_status( $updated ), 'lifecycle calculation does not modify Post status' );
	$scope = array( $draft, $pending, $never, $missing_date, $current, $review_later, $overdue, $updated, $scheduled );
	$args = array( 'post_type' => 'post', 'post_status' => 'any', 'post__in' => $scope, 'posts_per_page' => 50, 'orderby' => 'ID', 'order' => 'ASC' );
	$current_ids = drthai_c4_ids( drthai_c4_query( array( 'drthai_lifecycle' => 'current' ), $args ) );
	drthai_c4_assert( in_array( $current, $current_ids, true ), 'Current filter returns CURRENT Posts' );
	drthai_c4_assert( in_array( $overdue, drthai_c4_ids( drthai_c4_query( array( 'drthai_lifecycle' => 'needs_review' ), $args ) ), true ), 'Needs Review filter returns overdue Posts' );
	$never_ids = drthai_c4_ids( drthai_c4_query( array( 'drthai_lifecycle' => 'never_reviewed' ), $args ) );
	drthai_c4_assert( in_array( $never, $never_ids, true ) && in_array( $missing_date, $never_ids, true ), 'Never Reviewed filter returns legacy Published Posts' );
	$updated_ids = drthai_c4_ids( drthai_c4_query( array( 'drthai_lifecycle' => 'updated_since_review' ), $args ) );
	drthai_c4_assert( in_array( $updated, $updated_ids, true ), 'Updated Since Review filter returns modified Posts' );
	$action_ids = drthai_c4_ids( drthai_c4_query( array( 'drthai_lifecycle' => 'needs_action' ), $args ) );
	drthai_c4_assert( in_array( $overdue, $action_ids, true ) && in_array( $never, $action_ids, true ) && in_array( $updated, $action_ids, true ), 'Needs Action aggregates overdue, never-reviewed and updated Posts' );
	drthai_c4_assert( ! in_array( $draft, $action_ids, true ) && ! in_array( $pending, $action_ids, true ), 'Draft and Pending Posts do not leak into periodic filters' );
	drthai_c4_assert( true === drthai_health_mark_post_medically_reviewed( $updated ), 'C1 Medical Review action remains available for re-review' );
	drthai_c4_assert( 'current' === drthai_health_get_content_lifecycle( $updated, new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) ) )['state'], 're-review through C1 returns lifecycle to CURRENT' );
	drthai_c4_assert( ! in_array( $updated, drthai_c4_ids( drthai_c4_query( array( 'drthai_lifecycle' => 'updated_since_review' ), $args ) ), true ), 're-reviewed Post leaves Updated Since Review filter' );
	drthai_c4_assert( in_array( $current, drthai_c4_ids( drthai_c4_query( array( 'drthai_lifecycle' => 'current', 'drthai_reviewer' => (string) $user_id ), $args ) ), true ), 'Lifecycle filter cooperates with C2 Reviewer filter' );
	drthai_c4_assert( is_array( drthai_c4_ids( drthai_c4_query( array( 'drthai_lifecycle' => 'current', 'drthai_media_status' => 'missing_image' ), $args ) ) ), 'Lifecycle filter cooperates with C2 Media filter' );
	drthai_c4_assert( is_array( drthai_c4_ids( drthai_c4_query( array( 'drthai_lifecycle' => 'current', 'drthai_editorial_health' => 'attention' ), $args ) ) ), 'Lifecycle filter cooperates with C2 Editorial Health filter' );
	drthai_c4_assert( in_array( $current, drthai_c4_ids( drthai_c4_query( array( 'drthai_lifecycle' => 'current' ), array_merge( $args, array( 'cat' => $category_id ) ) ) ), true ), 'Lifecycle filter cooperates with native Category filter' );
	drthai_c4_assert( in_array( $current, drthai_c4_ids( drthai_c4_query( array( 'drthai_lifecycle' => 'current' ), array_merge( $args, array( 'post_status' => 'publish' ) ) ) ), true ), 'Lifecycle filter cooperates with native Status filter' );
	drthai_c4_assert( array( $current ) === drthai_c4_ids( drthai_c4_query( array( 'drthai_lifecycle' => 'current' ), array_merge( $args, array( 's' => "current-search-{$suffix}" ) ) ) ), 'Lifecycle filter cooperates with native search' );

	$columns = drthai_health_editorial_admin_columns( array( 'cb' => 'cb', 'title' => 'Title', 'date' => 'Date' ) );
	drthai_c4_assert( isset( $columns['drthai_lifecycle'] ), 'Lifecycle column exists' );
	drthai_c4_assert( false !== strpos( drthai_c4_column( $current ), 'Hiện hành' ), 'CURRENT displays compact Vietnamese label' );
	drthai_c4_assert( false !== strpos( drthai_c4_column( $never ), 'Chưa review' ), 'NEVER REVIEWED displays compact Vietnamese label' );
	drthai_c4_assert( false !== strpos( drthai_c4_column( $overdue ), 'Cần review' ), 'NEEDS REVIEW displays compact Vietnamese label' );
	drthai_c4_set_post( $updated, 'publish', '2026-01-01 00:00:00', gmdate( 'Y-m-d H:i:s', time() + 120 ) );
	drthai_c4_assert( false !== strpos( drthai_c4_column( $updated ), 'Đã sửa sau review' ), 'UPDATED SINCE REVIEW displays compact Vietnamese label' );
	drthai_c4_assert( false !== strpos( drthai_c4_column( $draft ), 'Tiền xuất bản' ), 'PRE-PUBLICATION displays compact Vietnamese label' );
	drthai_c4_assert( false !== strpos( drthai_c4_column( $current ), drthai_health_format_lifecycle_date( $current_state['due'] ) ), 'Review Due displays compactly for CURRENT' );
	drthai_c4_assert( false !== strpos( drthai_c4_column( $overdue ), drthai_health_format_lifecycle_date( drthai_health_get_content_lifecycle( $overdue )['due'] ) ), 'overdue date displays compactly for NEEDS REVIEW' );
	drthai_c4_assert( drthai_health_format_lifecycle_date( $current_state['due'] ) === wp_date( get_option( 'date_format' ), $current_state['due']->getTimestamp(), wp_timezone() ), 'site-local date formatting is deterministic' );
	drthai_c4_assert( false === strpos( drthai_c4_column( $current ), 'T00:00:00' ) && false === strpos( drthai_c4_column( $current ), 'Z' ), 'admin display omits raw internal timestamps' );

	$review_before = array( get_post_meta( $current, DRTHAI_MEDICAL_REVIEWER_META, true ), get_post_meta( $current, DRTHAI_REVIEWED_AT_META, true ) );
	drthai_c4_query( array( 'drthai_lifecycle' => 'current' ), $args );
	drthai_c4_assert( $review_before === array( get_post_meta( $current, DRTHAI_MEDICAL_REVIEWER_META, true ), get_post_meta( $current, DRTHAI_REVIEWED_AT_META, true ) ), 'visiting/filtering Posts list does not update review metadata' );
	$content_before = get_post_field( 'post_content', $current );
	drthai_c4_query( array( 'drthai_lifecycle' => 'current' ), $args );
	drthai_c4_assert( $content_before === get_post_field( 'post_content', $current ), 'filtering does not update content' );
	ob_start();
	drthai_health_render_medical_review_meta_box( get_post( $current ) );
	ob_end_clean();
	drthai_c4_assert( $review_before === array( get_post_meta( $current, DRTHAI_MEDICAL_REVIEWER_META, true ), get_post_meta( $current, DRTHAI_REVIEWED_AT_META, true ) ), 'opening Post edit status area does not update review metadata' );
	update_option( "drthai_c4_unrelated_{$suffix}", 'synthetic' );
	delete_option( "drthai_c4_unrelated_{$suffix}" );
	drthai_c4_assert( $review_before === array( get_post_meta( $current, DRTHAI_MEDICAL_REVIEWER_META, true ), get_post_meta( $current, DRTHAI_REVIEWED_AT_META, true ) ), 'saving unrelated setting does not update review metadata' );
	drthai_c4_assert( ! metadata_exists( 'post', $never, DRTHAI_REVIEWED_AT_META ), 'C4 does not fabricate review timestamps' );
	drthai_c4_assert( ! metadata_exists( 'post', $never, DRTHAI_MEDICAL_REVIEWER_META ), 'C4 does not fabricate reviewer metadata' );
	$revision_id = wp_save_post_revision( $current );
	drthai_c4_assert( ! is_wp_error( $revision_id ) && (int) $revision_id > 0, 'WordPress native Revisions remain available' );
	drthai_c4_assert( false === strpos( strtolower( get_post_field( 'post_content', $current ) ), 'patient' ), 'synthetic fixture contains no patient data' );

	$performance_ids = array();
	for ( $index = 1; $index <= 180; ++$index ) {
		$post_id = drthai_c4_create_post( (int) $user_id, "performance-{$suffix}-{$index}" );
		drthai_c4_set_post( $post_id, 'publish', '2026-01-01 00:00:00', '2026-01-01 00:00:00' );
		$performance_ids[] = $post_id;
	}
	$performance = drthai_c4_query( array( 'drthai_lifecycle' => 'never_reviewed' ), array( 'post_type' => 'post', 'post_status' => 'publish', 'post__in' => $performance_ids, 'posts_per_page' => 25, 'paged' => 2, 'orderby' => 'ID', 'order' => 'ASC' ) );
	drthai_c4_assert( 25 === $performance->post_count, 'performance sanity preserves bounded page size' );
	drthai_c4_assert( 180 === $performance->found_posts && 8 === $performance->max_num_pages, 'pagination works across 180 synthetic Posts' );
	drthai_c4_assert( false !== strpos( $performance->request, 'EXISTS' ) && false !== strpos( $performance->request, 'LIMIT 25, 25' ), 'lifecycle filtering is database-side and bounded' );
	drthai_c4_assert( count( $performance->posts ) === 25, 'bounded query avoids loading every Post into PHP' );
} catch ( Throwable $error ) {
	fwrite( STDERR, $error->getMessage() . "\n" );
	$exit_code = 1;
} finally {
	wp_set_current_user( 0 );
	foreach ( array_reverse( $created_posts ) as $post_id ) {
		wp_delete_post( $post_id, true );
	}
	foreach ( array_reverse( $created_terms ) as $term_id ) {
		wp_delete_term( $term_id, 'category' );
	}
	foreach ( array_reverse( $created_users ) as $created_user ) {
		wp_delete_user( $created_user );
	}
}

$final_posts = get_posts( array( 'post_type' => array( 'post', 'page', 'revision' ), 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'orderby' => 'ID', 'order' => 'ASC' ) );
drthai_c4_assert( $baseline_posts === $final_posts, 'existing Posts and revisions are not deleted or recreated' );
drthai_c4_assert( $baseline_terms === get_terms( array( 'taxonomy' => 'category', 'hide_empty' => false, 'fields' => 'ids' ) ), 'existing taxonomy is unchanged' );
drthai_c4_assert( $baseline_attachments === get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'posts_per_page' => -1, 'fields' => 'ids', 'orderby' => 'ID', 'order' => 'ASC' ) ), 'existing attachments are unchanged' );
drthai_c4_assert( $baseline_comments === get_comments( array( 'fields' => 'ids', 'orderby' => 'comment_ID', 'order' => 'ASC' ) ), 'existing comments are unchanged' );
drthai_c4_assert( $reference_meta === array( 'reviewer' => get_post_meta( $reference_id, DRTHAI_MEDICAL_REVIEWER_META, true ), 'reviewed' => get_post_meta( $reference_id, DRTHAI_REVIEWED_AT_META, true ) ), 'existing review metadata is unchanged' );
drthai_c4_assert( $reference_dates === array( get_post_field( 'post_date_gmt', $reference_id ), get_post_field( 'post_modified_gmt', $reference_id ) ), 'existing publication dates are unchanged' );
drthai_c4_assert( $reference_revisions === wp_get_post_revisions( $reference_id, array( 'fields' => 'ids' ) ), 'existing revisions are unchanged' );

if ( $exit_code ) {
	exit( $exit_code );
}

echo "C4_SYNTHETIC_SCALE=180\n";
echo "C4_TESTS_RUN={$tests_run}\n";
echo "C4_TEST_STATUS=PASS\n";
