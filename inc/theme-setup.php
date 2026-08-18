<?php
/**
 * Theme setup and presentation bootstrap.
 *
 * @package DrThai_Health
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register theme support.
 */
function drthai_health_setup() {
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'style.css' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	add_theme_support(
		'html5',
		array( 'comment-list', 'comment-form', 'search-form', 'gallery', 'caption', 'style', 'script' )
	);
}
add_action( 'after_setup_theme', 'drthai_health_setup' );

/**
 * Let Yoast remain the single document-title renderer when it is active.
 *
 * WordPress 7.0 retains the Core title callback alongside Yoast 28.3, which
 * otherwise produces two title elements. Native title rendering remains the
 * fallback whenever Yoast is inactive.
 */
function drthai_health_defer_document_title_to_yoast() {
	if ( defined( 'WPSEO_VERSION' ) ) {
		remove_action( 'wp_head', '_wp_render_title_tag', 1 );
		remove_action( 'wp_head', '_block_template_render_title_tag', 1 );
	}
}
add_action( 'wp_head', 'drthai_health_defer_document_title_to_yoast', 0 );

/**
 * Load the front-end stylesheet.
 */
function drthai_health_enqueue_styles() {
	$theme = wp_get_theme();

	wp_enqueue_style(
		'drthai-health-style',
		get_stylesheet_uri(),
		array(),
		$theme->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'drthai_health_enqueue_styles' );

/**
 * Register the theme pattern category.
 */
function drthai_health_register_pattern_category() {
	register_block_pattern_category(
		'drthai-health',
		array( 'label' => __( 'DrThai Health', 'drthai-health' ) )
	);
}
add_action( 'init', 'drthai_health_register_pattern_category' );
