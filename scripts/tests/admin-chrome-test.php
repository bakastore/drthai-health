<?php
/**
 * Targeted Local integration tests for Development 1.2.1 / B2b.
 */

declare(strict_types=1);

define( 'DISABLE_WP_CRON', true );

require '/var/www/html/wp-load.php';
require_once ABSPATH . WPINC . '/class-wp-admin-bar.php';

$tests_run = 0;

function drthai_b2b_assert( bool $condition, string $label ): void {
	global $tests_run;
	++$tests_run;
	if ( ! $condition ) {
		throw new RuntimeException( "FAIL {$label}" );
	}
	echo "PASS {$label}\n";
}

$roles_before        = wp_roles()->roles;
$administrator_caps = get_role( 'administrator' )->capabilities;
$posts_before        = get_posts( array( 'post_type' => array( 'post', 'page', 'drthai_callback' ), 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'orderby' => 'ID', 'order' => 'ASC' ) );
$comments_before     = get_comments( array( 'status' => 'all', 'fields' => 'ids', 'orderby' => 'comment_ID', 'order' => 'ASC' ) );

try {
	drthai_b2b_assert( 'LOCAL' === drthai_health_environment_label( 'local' ), 'local maps to exact LOCAL label' );
	drthai_b2b_assert( 'DEVELOPMENT' === drthai_health_environment_label( 'development' ), 'development maps to exact DEVELOPMENT label' );
	drthai_b2b_assert( 'STAGING' === drthai_health_environment_label( 'staging' ), 'staging maps to exact STAGING label' );
	drthai_b2b_assert( 'PRODUCTION' === drthai_health_environment_label( 'production' ), 'production maps to exact PRODUCTION label' );
	drthai_b2b_assert( '' === drthai_health_environment_label( 'invalid' ), 'explicit invalid value does not create a misleading badge' );
	drthai_b2b_assert( 'DEVELOPMENT' === drthai_health_environment_label() && 'development' === wp_get_environment_type(), 'badge reads the current environment from WordPress Core' );

	$admin_bar = new WP_Admin_Bar();
	drthai_health_add_environment_badge( $admin_bar );
	$node = $admin_bar->get_node( 'drthai-environment' );
	drthai_b2b_assert( false !== $node && 'DEVELOPMENT' === $node->title, 'authenticated toolbar receives the visible DEVELOPMENT badge' );
	drthai_b2b_assert( false === $node->href, 'environment badge has no action URL' );
	drthai_b2b_assert( 'top-secondary' === $node->parent && false !== strpos( $node->meta['class'], 'drthai-environment--development' ), 'badge is a top-level environment-specific toolbar node' );

	$administrator = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
	wp_set_current_user( (int) $administrator[0] );
	show_admin_bar( true );
	wp_dequeue_style( 'drthai-health-admin-chrome' );
	drthai_health_enqueue_admin_chrome_frontend();
	drthai_b2b_assert( wp_style_is( 'drthai-health-admin-chrome', 'enqueued' ), 'authenticated frontend with visible toolbar receives badge CSS' );

	wp_set_current_user( 0 );
	show_admin_bar( false );
	wp_dequeue_style( 'drthai-health-admin-chrome' );
	drthai_health_enqueue_admin_chrome_frontend();
	drthai_b2b_assert( ! wp_style_is( 'drthai-health-admin-chrome', 'enqueued' ), 'logged-out frontend does not receive custom Admin Bar CSS' );

	drthai_b2b_assert( false === has_action( 'admin_menu', 'drthai_health_hide_comments_menu' ), 'Comments menu remains available after evidence-based audit' );
	drthai_b2b_assert( function_exists( 'drthai_health_setup_operational_dashboard' ), 'B2a Dashboard integration remains available' );
	drthai_b2b_assert( function_exists( 'drthai_health_editorial_admin_columns' ), 'B1 Posts List integration remains available' );
	drthai_b2b_assert( $administrator_caps === get_role( 'administrator' )->capabilities && $roles_before === wp_roles()->roles, 'roles and capabilities remain unchanged' );
} catch ( Throwable $error ) {
	fwrite( STDERR, $error->getMessage() . "\n" );
	exit( 1 );
}

$posts_after    = get_posts( array( 'post_type' => array( 'post', 'page', 'drthai_callback' ), 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'orderby' => 'ID', 'order' => 'ASC' ) );
$comments_after = get_comments( array( 'status' => 'all', 'fields' => 'ids', 'orderby' => 'comment_ID', 'order' => 'ASC' ) );
drthai_b2b_assert( $posts_before === $posts_after, 'Post, Page and Booking inventories remain unchanged' );
drthai_b2b_assert( $comments_before === $comments_after, 'Comments inventory remains unchanged' );

echo "B2B_TESTS_RUN={$tests_run}\n";
echo "B2B_TEST_STATUS=PASS\n";
