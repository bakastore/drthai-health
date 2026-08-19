<?php
/**
 * Minimal environment context for the native WordPress Admin Bar.
 *
 * @package DrThai_Health
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return the exact visible label for a supported WordPress environment.
 *
 * @param string|null $environment Environment type, or null to read Core.
 * @return string Empty only when an explicitly supplied value is unsupported.
 */
function drthai_health_environment_label( $environment = null ) {
	$environment = null === $environment ? wp_get_environment_type() : (string) $environment;
	$labels      = array(
		'local'       => 'LOCAL',
		'development' => 'DEVELOPMENT',
		'staging'     => 'STAGING',
		'production'  => 'PRODUCTION',
	);

	return isset( $labels[ $environment ] ) ? $labels[ $environment ] : '';
}

/**
 * Add non-interactive environment context to the global Admin Bar.
 *
 * @param WP_Admin_Bar $admin_bar Current Admin Bar instance.
 */
function drthai_health_add_environment_badge( $admin_bar ) {
	$environment = wp_get_environment_type();
	$label       = drthai_health_environment_label( $environment );

	if ( '' === $label ) {
		return;
	}

	$admin_bar->add_node(
		array(
			'id'     => 'drthai-environment',
			'title'  => esc_html( $label ),
			'parent' => 'top-secondary',
			'meta'   => array(
				'class' => 'drthai-environment drthai-environment--' . sanitize_html_class( $environment ),
			),
		)
	);
}
add_action( 'admin_bar_menu', 'drthai_health_add_environment_badge', 20 );

/**
 * Register the badge stylesheet.
 */
function drthai_health_register_admin_chrome_style() {
	wp_register_style(
		'drthai-health-admin-chrome',
		get_theme_file_uri( '/assets/css/admin-chrome.css' ),
		array(),
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'init', 'drthai_health_register_admin_chrome_style' );

/**
 * Load environment context throughout wp-admin.
 */
function drthai_health_enqueue_admin_chrome_admin() {
	wp_enqueue_style( 'drthai-health-admin-chrome' );
}
add_action( 'admin_enqueue_scripts', 'drthai_health_enqueue_admin_chrome_admin' );

/**
 * Load environment context on the frontend only when the Admin Bar is visible.
 */
function drthai_health_enqueue_admin_chrome_frontend() {
	if ( is_admin_bar_showing() ) {
		wp_enqueue_style( 'drthai-health-admin-chrome' );
	}
}
add_action( 'wp_enqueue_scripts', 'drthai_health_enqueue_admin_chrome_frontend' );
