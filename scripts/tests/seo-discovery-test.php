<?php
/**
 * Local integration tests for Content Operations 1.2 / C5.
 *
 * The synthetic Post, user, term and image are removed after the run.
 */

declare(strict_types=1);

define( 'DISABLE_WP_CRON', true );

require '/var/www/html/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/user.php';

$created_posts = array();
$created_users = array();
$created_terms = array();
$created_attachments = array();
$tests_run = 0;
$exit_code = 0;

function drthai_c5_assert( bool $condition, string $label ): void {
	global $tests_run;
	++$tests_run;
	if ( ! $condition ) {
		throw new RuntimeException( "FAIL {$label}" );
	}
	echo "PASS {$label}\n";
}

function drthai_c5_get( string $url, int $redirection = 5 ): array {
	$request_url = preg_replace( '#^http://localhost:8888#', 'http://wordpress', $url );
	$response = wp_remote_get( $request_url, array( 'redirection' => $redirection, 'timeout' => 20, 'headers' => array( 'Host' => 'localhost:8888' ) ) );
	if ( is_wp_error( $response ) ) {
		throw new RuntimeException( 'HTTP request failed: ' . $response->get_error_code() );
	}
	return array(
		'status'  => wp_remote_retrieve_response_code( $response ),
		'body'    => wp_remote_retrieve_body( $response ),
		'headers' => wp_remote_retrieve_headers( $response ),
	);
}

function drthai_c5_count( string $pattern, string $html ): int {
	return preg_match_all( $pattern, $html, $matches );
}

function drthai_c5_attachment_snapshot(): array {
	$snapshot = array();
	foreach ( get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'posts_per_page' => -1, 'orderby' => 'ID', 'order' => 'ASC' ) ) as $attachment ) {
		$snapshot[ $attachment->ID ] = array(
			'file' => get_post_meta( $attachment->ID, '_wp_attached_file', true ),
			'alt'  => get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true ),
			'meta' => get_post_meta( $attachment->ID, '_wp_attachment_metadata', true ),
		);
	}
	return $snapshot;
}

$site_url = home_url( '/' );
$baseline_posts = get_posts( array( 'post_type' => array( 'post', 'page' ), 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'orderby' => 'ID', 'order' => 'ASC' ) );
$baseline_terms = get_terms( array( 'taxonomy' => array( 'category', 'post_tag' ), 'hide_empty' => false, 'fields' => 'ids' ) );
$baseline_attachments = drthai_c5_attachment_snapshot();
$baseline_review_meta = array();
$baseline_seo_meta = array();
foreach ( $baseline_posts as $existing_post_id ) {
	$baseline_review_meta[ $existing_post_id ] = array(
		get_post_meta( $existing_post_id, DRTHAI_MEDICAL_REVIEWER_META, true ),
		get_post_meta( $existing_post_id, DRTHAI_REVIEWED_AT_META, true ),
	);
	$baseline_seo_meta[ $existing_post_id ] = array_filter( get_post_meta( $existing_post_id ), static function ( $key ): bool {
		return 0 === strpos( (string) $key, '_yoast_wpseo_' );
	}, ARRAY_FILTER_USE_KEY );
}

try {
	$suffix = strtolower( wp_generate_password( 8, false, false ) );
	$active_plugins = (array) get_option( 'active_plugins', array() );
	$active_seo = array_values( array_filter( $active_plugins, static function ( $plugin ): bool {
		return false !== strpos( $plugin, 'wordpress-seo' ) || preg_match( '/rank-math|all-in-one-seo|wp-seopress|slim-seo|smartcrawl/i', $plugin );
	} ) );
	drthai_c5_assert( array( 'wordpress-seo/wp-seo.php' ) === $active_seo, 'exactly one approved SEO plugin is active' );
	drthai_c5_assert( defined( 'WPSEO_VERSION' ) && '28.3' === WPSEO_VERSION, 'Yoast SEO Free version 28.3 is recorded' );
	drthai_c5_assert( version_compare( get_bloginfo( 'version' ), '6.9', '>=' ), 'Local Core satisfies Yoast minimum WordPress requirement' );
	drthai_c5_assert( version_compare( PHP_VERSION, '7.4', '>=' ), 'Local PHP satisfies Yoast minimum PHP requirement' );
	drthai_c5_assert( class_exists( 'WPSEO_Options' ) && function_exists( 'YoastSEO' ), 'Yoast runtime loads without fatal error' );
	drthai_c5_assert( 0 === (int) get_option( 'blog_public' ), 'Local blog_public remains non-indexable' );

	$user_id = wp_insert_user( array( 'user_login' => "drthai-c5-admin-{$suffix}", 'user_pass' => wp_generate_password( 32, true, true ), 'user_email' => "drthai-c5-{$suffix}@example.invalid", 'role' => 'administrator' ) );
	if ( is_wp_error( $user_id ) ) {
		throw new RuntimeException( 'Unable to create C5 user.' );
	}
	$created_users[] = (int) $user_id;
	wp_set_current_user( (int) $user_id );

	$term = wp_insert_term( "C5 Discovery {$suffix}", 'category', array( 'slug' => "c5-discovery-{$suffix}" ) );
	if ( is_wp_error( $term ) ) {
		throw new RuntimeException( 'Unable to create C5 Category.' );
	}
	$created_terms[] = (int) $term['term_id'];

	$temporary_file = wp_tempnam( "drthai-c5-discovery-{$suffix}.png" );
	$image = base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true );
	if ( false === $temporary_file || false === $image || false === file_put_contents( $temporary_file, $image ) ) {
		throw new RuntimeException( 'Unable to create C5 image.' );
	}
	$attachment_id = media_handle_sideload( array( 'name' => "drthai-c5-discovery-{$suffix}.png", 'type' => 'image/png', 'tmp_name' => $temporary_file, 'error' => 0, 'size' => filesize( $temporary_file ) ), 0, 'C5 synthetic discovery illustration' );
	if ( is_wp_error( $attachment_id ) ) {
		throw new RuntimeException( 'Unable to upload C5 image.' );
	}
	$created_attachments[] = (int) $attachment_id;
	update_post_meta( $attachment_id, '_wp_attachment_image_alt', 'Minh họa nội dung sức khỏe tổng quát' );

	$post_id = wp_insert_post(
		array(
			'post_type'    => 'post',
			'post_status'  => 'draft',
			'post_title'   => "C5 synthetic discovery {$suffix}",
			'post_name'    => "c5-synthetic-discovery-{$suffix}",
			'post_content' => '<!-- wp:paragraph --><p>Synthetic general health discovery fixture with a <a href="/tin-tuc/">descriptive internal link</a>.</p><!-- /wp:paragraph -->',
			'post_excerpt' => 'Synthetic general health discovery excerpt.',
			'post_author'  => (int) $user_id,
		),
		true
	);
	if ( is_wp_error( $post_id ) ) {
		throw new RuntimeException( 'Unable to create C5 Post.' );
	}
	$created_posts[] = (int) $post_id;
	wp_set_post_categories( (int) $post_id, array( (int) $term['term_id'] ) );
	set_post_thumbnail( (int) $post_id, (int) $attachment_id );
	update_post_meta( (int) $post_id, '_yoast_wpseo_title', "C5 Custom SEO Title {$suffix}" );
	update_post_meta( (int) $post_id, '_yoast_wpseo_metadesc', "C5 custom meta description {$suffix} for Local verification." );
	drthai_c5_assert( true === drthai_health_mark_post_medically_reviewed( (int) $post_id ), 'C1 Medical Review action remains functional' );
	$published = wp_update_post( array( 'ID' => (int) $post_id, 'post_status' => 'publish' ), true );
	drthai_c5_assert( ! is_wp_error( $published ) && 'publish' === get_post_status( (int) $post_id ), 'C3-compliant synthetic Post publishes normally' );
	$post_url = get_permalink( (int) $post_id );
	$page = drthai_c5_get( $post_url );
	$html = $page['body'];
	$head = strstr( $html, '</head>', true );
	$head = false === $head ? $html : $head;
	drthai_c5_assert( 200 === $page['status'], 'synthetic Single Post returns HTTP 200' );
	drthai_c5_assert( false === stripos( $html, 'PHP Warning' ) && false === stripos( $html, 'Fatal error' ), 'Single output has no PHP warning or fatal' );
	drthai_c5_assert( 1 === drthai_c5_count( '/<title(?:\s[^>]*)?>/i', $head ), 'exactly one HTML title is emitted' );
	drthai_c5_assert( false !== strpos( $head, "C5 Custom SEO Title {$suffix}" ), 'custom SEO Title renders correctly' );
	drthai_c5_assert( 1 === drthai_c5_count( '/<meta\s+[^>]*name=["\']description["\'][^>]*>/i', $head ), 'custom Meta Description renders exactly once' );
	drthai_c5_assert( false !== strpos( $head, "C5 custom meta description {$suffix} for Local verification." ), 'custom Meta Description is escaped and rendered' );
	drthai_c5_assert( 1 === drthai_c5_count( '/<meta\s+[^>]*name=["\']robots["\'][^>]*>/i', $head ) && false !== stripos( $head, 'noindex'), 'Local Single output remains noindex' );
	drthai_c5_assert( 0 === drthai_c5_count( '/<link\s+[^>]*rel=["\']canonical["\'][^>]*>/i', $head ), 'Yoast intentionally suppresses canonical on Local noindex output' );
	drthai_c5_assert( 1 === drthai_c5_count( '/<meta\s+[^>]*property=["\']og:title["\'][^>]*>/i', $head ), 'exactly one Open Graph title is emitted' );
	drthai_c5_assert( 1 === drthai_c5_count( '/<meta\s+[^>]*property=["\']og:url["\'][^>]*>/i', $head ) && false !== strpos( $head, esc_url( $post_url ) ), 'Open Graph URL is absolute and semantic' );
	drthai_c5_assert( 1 === drthai_c5_count( '/<meta\s+[^>]*property=["\']og:type["\'][^>]*content=["\']article["\']/i', $head ), 'Open Graph type is Article' );
	drthai_c5_assert( 1 === drthai_c5_count( '/<meta\s+[^>]*property=["\']og:description["\'][^>]*>/i', $head ), 'Open Graph description is coherent and singular' );
	drthai_c5_assert( drthai_c5_count( '/<meta\s+[^>]*property=["\']og:image(?::[^"\']*)?["\'][^>]*>/i', $head ) >= 1, 'Featured Image produces social image metadata' );
	drthai_c5_assert( 1 === drthai_c5_count( '/<script\s+[^>]*type=["\']application\/ld\+json["\'][^>]*>/i', $head ), 'one Yoast JSON-LD graph exists' );
	preg_match( '/<script\s+[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $head, $schema_match );
	$schema = isset( $schema_match[1] ) ? json_decode( html_entity_decode( $schema_match[1] ), true ) : null;
	drthai_c5_assert( is_array( $schema ) && isset( $schema['@graph'] ), 'Schema graph is parseable JSON' );
	$schema_types = wp_list_pluck( $schema['@graph'], '@type' );
	drthai_c5_assert( in_array( 'Article', $schema_types, true ) && in_array( 'WebPage', $schema_types, true ), 'Schema graph contains coherent Article and WebPage pieces' );
	drthai_c5_assert( false !== strpos( wp_json_encode( $schema ), wp_json_encode( untrailingslashit( $post_url ) ) ) || false !== strpos( wp_json_encode( $schema ), wp_json_encode( $post_url ) ), 'Schema graph uses the semantic Post URL' );
	drthai_c5_assert( false === stripos( $html, 'MedicalWebPage' ), 'no unsupported medical Schema identity is introduced' );

	$sitemap = drthai_c5_get( home_url( '/sitemap_index.xml' ) );
	drthai_c5_assert( 200 === $sitemap['status'], 'Yoast sitemap index returns HTTP 200' );
	libxml_use_internal_errors( true );
	drthai_c5_assert( false !== simplexml_load_string( $sitemap['body'] ), 'Yoast sitemap index is valid XML' );
	drthai_c5_assert( false !== strpos( $sitemap['body'], home_url( '/post-sitemap.xml' ) ) && false !== strpos( $sitemap['body'], home_url( '/page-sitemap.xml' ) ), 'Post and Page child sitemaps are present' );
	drthai_c5_assert( false !== strpos( $sitemap['body'], home_url( '/category-sitemap.xml' ) ) && false !== strpos( $sitemap['body'], home_url( '/post_tag-sitemap.xml' ) ), 'Category and Tag sitemap behavior preserves current governance' );
	$post_sitemap = drthai_c5_get( home_url( '/post-sitemap.xml' ) );
	drthai_c5_assert( false !== strpos( $post_sitemap['body'], esc_url( $post_url ) ), 'Published synthetic content is dynamically discoverable' );
	$core_sitemap = drthai_c5_get( home_url( '/wp-sitemap.xml' ), 0 );
	$core_location = isset( $core_sitemap['headers']['location'] ) ? (string) $core_sitemap['headers']['location'] : '';
	drthai_c5_assert( 301 === $core_sitemap['status'] && home_url( '/sitemap_index.xml' ) === $core_location, 'Core sitemap endpoint redirects to the single Yoast layer' );
	$robots = drthai_c5_get( home_url( '/robots.txt' ) );
	drthai_c5_assert( false !== strpos( $robots['body'], 'Sitemap: ' . home_url( '/sitemap_index.xml' ) ), 'robots output points to Yoast sitemap' );

	drthai_c5_assert( class_exists( 'WPSEO_Meta_Columns' ), 'Yoast Posts-list integration is available without custom C2 duplication' );
	drthai_c5_assert( class_exists( 'WPSEO_Metabox' ) || class_exists( 'Yoast\WP\SEO\Integrations\Admin\Metabox_Integration' ), 'Yoast editor/admin integration is loaded' );
	drthai_c5_assert( false !== strpos( get_post_field( 'post_content', (int) $post_id ), 'descriptive internal link' ), 'native editor internal linking remains functional' );
	drthai_c5_assert( 'current' === drthai_health_get_content_lifecycle( (int) $post_id )['state'], 'C4 lifecycle remains functional' );
	$content_before = get_post_field( 'post_content', (int) $post_id );
	drthai_c5_get( $post_url );
	drthai_c5_assert( $content_before === get_post_field( 'post_content', (int) $post_id ), 'SEO rendering performs no automatic link or content mutation' );
} catch ( Throwable $error ) {
	fwrite( STDERR, $error->getMessage() . "\n" );
	$exit_code = 1;
} finally {
	wp_set_current_user( 0 );
	foreach ( array_reverse( $created_posts ) as $post_id ) {
		wp_delete_post( $post_id, true );
	}
	foreach ( array_reverse( $created_attachments ) as $attachment_id ) {
		wp_delete_attachment( $attachment_id, true );
	}
	foreach ( array_reverse( $created_terms ) as $term_id ) {
		wp_delete_term( $term_id, 'category' );
	}
	foreach ( array_reverse( $created_users ) as $user_id ) {
		wp_delete_user( $user_id );
	}
}

$final_posts = get_posts( array( 'post_type' => array( 'post', 'page' ), 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'orderby' => 'ID', 'order' => 'ASC' ) );
drthai_c5_assert( $baseline_posts === $final_posts, 'existing Post and Page inventory is restored exactly' );
drthai_c5_assert( $baseline_terms === get_terms( array( 'taxonomy' => array( 'category', 'post_tag' ), 'hide_empty' => false, 'fields' => 'ids' ) ), 'existing taxonomy is unchanged' );
drthai_c5_assert( $baseline_attachments === drthai_c5_attachment_snapshot(), 'existing Attachment metadata is unchanged' );
foreach ( $baseline_review_meta as $existing_post_id => $review_meta ) {
	drthai_c5_assert( $review_meta === array( get_post_meta( $existing_post_id, DRTHAI_MEDICAL_REVIEWER_META, true ), get_post_meta( $existing_post_id, DRTHAI_REVIEWED_AT_META, true ) ), "existing review metadata is unchanged for {$existing_post_id}" );
	drthai_c5_assert( $baseline_seo_meta[ $existing_post_id ] === array_filter( get_post_meta( $existing_post_id ), static function ( $key ): bool { return 0 === strpos( (string) $key, '_yoast_wpseo_' ); }, ARRAY_FILTER_USE_KEY ), "existing SEO metadata is not fabricated for {$existing_post_id}" );
}
$deleted_sitemap = drthai_c5_get( home_url( '/post-sitemap.xml' ) );
drthai_c5_assert( ! isset( $post_url ) || false === strpos( $deleted_sitemap['body'], esc_url( $post_url ) ), 'sitemap dynamically removes the cleaned synthetic Post' );
drthai_c5_assert( 0 === (int) get_option( 'blog_public' ), 'Local indexing remains disabled after cleanup' );

if ( $exit_code ) {
	exit( $exit_code );
}

echo "C5_TESTS_RUN={$tests_run}\n";
echo "C5_TEST_STATUS=PASS\n";
