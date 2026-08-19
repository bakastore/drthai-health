<?php
/**
 * Task-oriented operational overview for the native WordPress Dashboard.
 *
 * @package DrThai_Health
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const DRTHAI_DASHBOARD_QUEUE_LIMIT = 5;

/**
 * Count Booking requests created within the current seven site-calendar days.
 *
 * Booking currently stores requests as private drthai_callback posts. Access to
 * this count follows the post type's existing manage_options capability.
 *
 * @return int|null Null when the current user cannot view Booking.
 */
function drthai_health_dashboard_booking_count() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return null;
	}

	global $wpdb;
	$site_start = current_datetime()->setTime( 0, 0 )->modify( '-6 days' );
	$utc_start  = $site_start->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
	$utc_now    = current_datetime()->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );

	return (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(ID)
			FROM {$wpdb->posts}
			WHERE post_type = %s
			AND post_status = %s
			AND post_date_gmt >= %s
			AND post_date_gmt <= %s",
			'drthai_callback',
			'private',
			$utc_start,
			$utc_now
		)
	);
}

/**
 * Count Posts matching one existing database-side editorial predicate.
 *
 * @param string $predicate Trusted predicate built by the C2/C4 helpers.
 * @return int
 */
function drthai_health_dashboard_post_count( $predicate ) {
	global $wpdb;

	return (int) $wpdb->get_var(
		"SELECT COUNT(ID)
		FROM {$wpdb->posts}
		WHERE post_type = 'post'
		AND post_status NOT IN ('trash','auto-draft','inherit')
		AND ({$predicate})"
	);
}

/**
 * Return the four operational KPI values without writing application data.
 *
 * @return array
 */
function drthai_health_dashboard_metrics() {
	$lifecycle = drthai_health_content_lifecycle_sql_predicates();
	$editorial = drthai_health_editorial_admin_sql_predicates();
	$counts    = wp_count_posts( 'post' );

	return array(
		'booking'   => drthai_health_dashboard_booking_count(),
		'review'    => drthai_health_dashboard_post_count( $lifecycle['needs_action'] ),
		'editorial' => drthai_health_dashboard_post_count( "NOT ({$editorial['complete']})" ),
		'drafts'    => isset( $counts->draft ) ? (int) $counts->draft : 0,
	);
}

/**
 * Build a correlated SQL expression for the C4 review anchor.
 *
 * This expression is used only to sort overdue Posts by oldest due date. It
 * follows the same later-of-review-or-publication anchor as C4.
 *
 * @return string
 */
function drthai_health_dashboard_review_anchor_sql() {
	global $wpdb;
	$review_key = esc_sql( DRTHAI_REVIEWED_AT_META );

	return "GREATEST(
		{$wpdb->posts}.post_date_gmt,
		COALESCE(
			(SELECT MAX(STR_TO_DATE(LEFT(queue_review.meta_value, 19), '%Y-%m-%dT%H:%i:%s'))
			FROM {$wpdb->postmeta} queue_review
			WHERE queue_review.post_id = {$wpdb->posts}.ID
			AND queue_review.meta_key = '{$review_key}'),
			{$wpdb->posts}.post_date_gmt
		)
	)";
}

/**
 * Return a deterministic, deduplicated, bounded action queue.
 *
 * @param int $limit Maximum visible items; never more than five.
 * @return array<int,array{post:WP_Post,priority:int,state:string}>
 */
function drthai_health_dashboard_action_queue( $limit = DRTHAI_DASHBOARD_QUEUE_LIMIT ) {
	global $wpdb;
	$limit       = min( DRTHAI_DASHBOARD_QUEUE_LIMIT, max( 1, absint( $limit ) ) );
	$lifecycle   = drthai_health_content_lifecycle_sql_predicates();
	$editorial   = drthai_health_editorial_admin_sql_predicates();
	$health      = "NOT ({$editorial['complete']})";
	$actionable  = "({$lifecycle['needs_action']}) OR ({$health})";
	$anchor      = drthai_health_dashboard_review_anchor_sql();
	$priority    = "CASE
		WHEN ({$lifecycle['updated_since_review']}) THEN 1
		WHEN ({$lifecycle['needs_review']}) THEN 2
		WHEN ({$lifecycle['never_reviewed']}) THEN 3
		ELSE 4 END";
	$priority_at = "CASE
		WHEN ({$lifecycle['updated_since_review']}) THEN {$wpdb->posts}.post_modified_gmt
		WHEN ({$lifecycle['needs_review']}) THEN {$anchor}
		WHEN ({$lifecycle['never_reviewed']}) THEN {$wpdb->posts}.post_date_gmt
		ELSE {$wpdb->posts}.post_modified_gmt END";

	$rows = $wpdb->get_results(
		"SELECT ID, {$priority} AS priority
			FROM {$wpdb->posts}
			WHERE post_type = 'post'
			AND post_status NOT IN ('trash','auto-draft','inherit')
			AND ({$actionable})
			ORDER BY priority ASC, {$priority_at} ASC, ID ASC
			LIMIT {$limit}"
	);

	if ( ! $rows ) {
		return array();
	}

	$post_ids = array_map( 'absint', wp_list_pluck( $rows, 'ID' ) );
	_prime_post_caches( $post_ids, true, true );
	$posts_by_id = array();
	foreach ( $post_ids as $post_id ) {
		$post = get_post( $post_id );
		if ( $post ) {
			$posts_by_id[ $post->ID ] = $post;
		}
	}

	$queue = array();
	foreach ( $rows as $row ) {
		$post_id = absint( $row->ID );
		if ( ! isset( $posts_by_id[ $post_id ] ) ) {
			continue;
		}
		$states = array( 1 => 'updated_since_review', 2 => 'needs_review', 3 => 'never_reviewed', 4 => 'editorial_attention' );
		$queue[] = array(
			'post'     => $posts_by_id[ $post_id ],
			'priority' => (int) $row->priority,
			'state'    => $states[ (int) $row->priority ],
		);
	}

	return $queue;
}

/**
 * Count the unique union of lifecycle and editorial actions.
 *
 * @return int
 */
function drthai_health_dashboard_unique_actionable_count() {
	$lifecycle = drthai_health_content_lifecycle_sql_predicates();
	$editorial = drthai_health_editorial_admin_sql_predicates();

	return drthai_health_dashboard_post_count( "({$lifecycle['needs_action']}) OR NOT ({$editorial['complete']})" );
}

/**
 * Return concise visible and accessible context for one queue item.
 *
 * @param array{post:WP_Post,priority:int,state:string} $item Queue item.
 * @return array{visible:string,accessible:string}
 */
function drthai_health_dashboard_queue_context( $item ) {
	$post_id = $item['post']->ID;
	if ( 'updated_since_review' === $item['state'] ) {
		return array(
			'visible'    => __( 'Đã sửa sau review', 'drthai-health' ),
			'accessible' => __( 'Bài viết đã được sửa sau lần rà soát y khoa gần nhất.', 'drthai-health' ),
		);
	}
	if ( 'needs_review' === $item['state'] ) {
		$lifecycle = drthai_health_get_content_lifecycle( $post_id );
		$days      = $lifecycle['due'] ? max( 0, (int) $lifecycle['due']->diff( new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) ) )->format( '%a' ) ) : 0;
		return array(
			'visible'    => sprintf( __( 'Quá hạn rà soát · %d ngày', 'drthai-health' ), $days ),
			'accessible' => sprintf( __( 'Bài viết đã quá hạn rà soát %d ngày.', 'drthai-health' ), $days ),
		);
	}
	if ( 'never_reviewed' === $item['state'] ) {
		return array(
			'visible'    => __( 'Chưa từng rà soát', 'drthai-health' ),
			'accessible' => __( 'Bài viết chưa từng được rà soát y khoa.', 'drthai-health' ),
		);
	}

	$health  = drthai_health_get_editorial_health( $post_id );
	$reasons = $health['reasons'];
	return array(
		'visible'    => implode( ' · ', array_slice( $reasons, 0, 2 ) ),
		'accessible' => implode( '; ', $reasons ),
	);
}

/**
 * Return capability-aware Dashboard quick actions.
 *
 * @return array<int,array{label:string,url:string}>
 */
function drthai_health_dashboard_quick_actions() {
	$actions = array();
	if ( current_user_can( 'edit_posts' ) ) {
		$actions[] = array( 'label' => __( 'Viết bài mới', 'drthai-health' ), 'url' => admin_url( 'post-new.php' ) );
	}
	if ( current_user_can( 'upload_files' ) ) {
		$actions[] = array( 'label' => __( 'Thư viện', 'drthai-health' ), 'url' => admin_url( 'upload.php' ) );
	}
	if ( current_user_can( 'manage_options' ) ) {
		$actions[] = array( 'label' => __( 'Yêu cầu đặt lịch', 'drthai-health' ), 'url' => admin_url( 'edit.php?post_type=drthai_callback' ) );
	}
	if ( current_user_can( 'manage_categories' ) ) {
		$actions[] = array( 'label' => __( 'Danh mục', 'drthai-health' ), 'url' => admin_url( 'edit-tags.php?taxonomy=category' ) );
	}

	return array_slice( $actions, 0, 4 );
}

/**
 * Render the accessible action-queue empty state.
 */
function drthai_health_render_dashboard_empty_state() {
	?>
	<div class="drthai-dashboard__empty">
		<p><strong><?php esc_html_e( 'Không có bài viết cần xử lý.', 'drthai-health' ); ?></strong></p>
		<p><?php esc_html_e( 'Nội dung hiện tại không có bài quá hạn rà soát hoặc cảnh báo biên tập.', 'drthai-health' ); ?></p>
	</div>
	<?php
}

/**
 * Render the operational Dashboard widget.
 */
function drthai_health_render_operational_dashboard() {
	$metrics = drthai_health_dashboard_metrics();
	$queue   = drthai_health_dashboard_action_queue();
	$total   = drthai_health_dashboard_unique_actionable_count();
	$cards   = array();
	if ( null !== $metrics['booking'] ) {
		$cards[] = array( 'label' => __( 'Yêu cầu đặt lịch', 'drthai-health' ), 'context' => __( '7 ngày qua', 'drthai-health' ), 'value' => $metrics['booking'], 'state' => 'neutral', 'status' => __( 'Thông tin', 'drthai-health' ), 'link' => admin_url( 'edit.php?post_type=drthai_callback' ), 'action' => __( 'Xem yêu cầu', 'drthai-health' ) );
	}
	$cards[] = array( 'label' => __( 'Cần rà soát', 'drthai-health' ), 'context' => '', 'value' => $metrics['review'], 'state' => $metrics['review'] ? 'attention' : 'ok', 'status' => $metrics['review'] ? __( 'Cần chú ý', 'drthai-health' ) : __( 'Ổn', 'drthai-health' ), 'link' => admin_url( 'edit.php?post_type=post&drthai_lifecycle=needs_action' ), 'action' => __( 'Xem bài cần rà soát', 'drthai-health' ) );
	$cards[] = array( 'label' => __( 'Cần biên tập', 'drthai-health' ), 'context' => '', 'value' => $metrics['editorial'], 'state' => $metrics['editorial'] ? 'attention' : 'ok', 'status' => $metrics['editorial'] ? __( 'Cần chú ý', 'drthai-health' ) : __( 'Ổn', 'drthai-health' ), 'link' => admin_url( 'edit.php?post_type=post&drthai_editorial_health=attention' ), 'action' => __( 'Xem bài cần xử lý', 'drthai-health' ) );
	$cards[] = array( 'label' => __( 'Bản nháp', 'drthai-health' ), 'context' => '', 'value' => $metrics['drafts'], 'state' => 'neutral', 'status' => __( 'Thông tin', 'drthai-health' ), 'link' => admin_url( 'edit.php?post_status=draft&post_type=post' ), 'action' => __( 'Xem bản nháp', 'drthai-health' ) );
	?>
	<div class="drthai-dashboard">
		<p class="drthai-dashboard__eyebrow"><?php esc_html_e( 'DRTHAI HEALTH', 'drthai-health' ); ?></p>
		<h2><?php esc_html_e( 'Tổng quan vận hành', 'drthai-health' ); ?></h2>
		<div class="drthai-dashboard__kpis">
			<?php foreach ( $cards as $card ) : ?>
				<section class="drthai-dashboard__kpi drthai-dashboard__kpi--<?php echo esc_attr( $card['state'] ); ?>">
					<p class="drthai-dashboard__kpi-label"><?php echo esc_html( $card['label'] ); ?></p>
					<?php if ( $card['context'] ) : ?><p class="drthai-dashboard__kpi-context"><?php echo esc_html( $card['context'] ); ?></p><?php endif; ?>
					<p class="drthai-dashboard__kpi-value"><?php echo esc_html( number_format_i18n( $card['value'] ) ); ?></p>
					<p class="drthai-dashboard__kpi-status"><span aria-hidden="true"></span><?php echo esc_html( $card['status'] ); ?></p>
					<a href="<?php echo esc_url( $card['link'] ); ?>"><?php echo esc_html( $card['action'] ); ?></a>
				</section>
			<?php endforeach; ?>
		</div>

		<div class="drthai-dashboard__workspace">
			<section aria-labelledby="drthai-dashboard-queue-title">
				<h3 id="drthai-dashboard-queue-title"><?php esc_html_e( 'Việc cần xử lý', 'drthai-health' ); ?></h3>
				<?php if ( $queue ) : ?>
					<ul class="drthai-dashboard__queue">
						<?php foreach ( $queue as $item ) : $context = drthai_health_dashboard_queue_context( $item ); ?>
							<li>
								<?php if ( current_user_can( 'edit_post', $item['post']->ID ) ) : ?>
									<a href="<?php echo esc_url( get_edit_post_link( $item['post']->ID ) ); ?>"><?php echo esc_html( get_the_title( $item['post'] ) ); ?></a>
								<?php else : ?>
									<strong><?php echo esc_html( get_the_title( $item['post'] ) ); ?></strong>
								<?php endif; ?>
								<span><?php echo esc_html( $context['visible'] ); ?></span>
								<?php if ( $context['accessible'] !== $context['visible'] ) : ?><span class="screen-reader-text"> <?php echo esc_html( $context['accessible'] ); ?></span><?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
					<?php if ( $total > count( $queue ) ) : ?><a class="drthai-dashboard__more" href="<?php echo esc_url( admin_url( 'edit.php?post_type=post' ) ); ?>"><?php echo esc_html( sprintf( __( '+ %d việc khác', 'drthai-health' ), $total - count( $queue ) ) ); ?></a><?php endif; ?>
				<?php else : ?>
					<?php drthai_health_render_dashboard_empty_state(); ?>
				<?php endif; ?>
			</section>

			<section aria-labelledby="drthai-dashboard-actions-title">
				<h3 id="drthai-dashboard-actions-title"><?php esc_html_e( 'Thao tác nhanh', 'drthai-health' ); ?></h3>
				<ul class="drthai-dashboard__actions">
					<?php foreach ( drthai_health_dashboard_quick_actions() as $action ) : ?><li><a href="<?php echo esc_url( $action['url'] ); ?>"><?php echo esc_html( $action['label'] ); ?></a></li><?php endforeach; ?>
				</ul>
			</section>
		</div>
	</div>
	<?php
}

/**
 * Register the operational widget and remove two low-value defaults.
 */
function drthai_health_setup_operational_dashboard() {
	global $wp_meta_boxes;

	remove_meta_box( 'dashboard_right_now', 'dashboard', 'normal' );
	remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
	wp_add_dashboard_widget( 'drthai_health_operational_dashboard', __( 'DRTHAI HEALTH', 'drthai-health' ), 'drthai_health_render_operational_dashboard' );

	if ( isset( $wp_meta_boxes['dashboard']['normal']['core']['drthai_health_operational_dashboard'] ) ) {
		$widget = $wp_meta_boxes['dashboard']['normal']['core']['drthai_health_operational_dashboard'];
		unset( $wp_meta_boxes['dashboard']['normal']['core']['drthai_health_operational_dashboard'] );
		$wp_meta_boxes['dashboard']['normal']['high'] = array(
			'drthai_health_operational_dashboard' => $widget,
		) + ( isset( $wp_meta_boxes['dashboard']['normal']['high'] ) ? $wp_meta_boxes['dashboard']['normal']['high'] : array() );
	}
}
add_action( 'wp_dashboard_setup', 'drthai_health_setup_operational_dashboard', 20 );

/**
 * Enqueue styles only for Dashboard home.
 *
 * @param string $hook_suffix Current admin page hook.
 */
function drthai_health_enqueue_dashboard_styles( $hook_suffix ) {
	if ( 'index.php' !== $hook_suffix ) {
		return;
	}
	wp_enqueue_style( 'drthai-health-admin-dashboard', get_theme_file_uri( '/assets/css/admin-dashboard.css' ), array(), wp_get_theme()->get( 'Version' ) );
}
add_action( 'admin_enqueue_scripts', 'drthai_health_enqueue_dashboard_styles' );
