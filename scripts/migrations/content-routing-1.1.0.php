<?php
/**
 * Apply the Content Engine 1.1.0 routing options.
 *
 * Run directly with PHP inside the WordPress container. WordPress must not be
 * bootstrapped before this file defines DISABLE_WP_CRON.
 */

declare(strict_types=1);

define('DISABLE_WP_CRON', true);

require '/var/www/html/wp-load.php';

/**
 * Stop the migration with a diagnostic that contains no secret data.
 */
function drthai_routing_fail(string $message): void
{
    fwrite(STDERR, "MIGRATION_ERROR={$message}\n");
    exit(1);
}

$option_names = array(
    'show_on_front',
    'page_on_front',
    'page_for_posts',
    'permalink_structure',
    'category_base',
    'tag_base',
);

$old_options = array();
foreach ($option_names as $option_name) {
    $old_options[$option_name] = (string) get_option($option_name, '');
    echo 'OLD_' . strtoupper($option_name) . '=' . $old_options[$option_name] . PHP_EOL;
}

global $wpdb;
$matching_ids = $wpdb->get_col(
    $wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'page' AND post_name = %s ORDER BY ID",
        'tin-tuc'
    )
);

if (1 !== count($matching_ids)) {
    drthai_routing_fail('tin-tuc must resolve to exactly one Page');
}

$posts_page = get_post((int) $matching_ids[0]);
if (!$posts_page instanceof WP_Post || 'page' !== $posts_page->post_type) {
    drthai_routing_fail('tin-tuc did not resolve to a Page');
}
if ('publish' !== $posts_page->post_status) {
    drthai_routing_fail('tin-tuc Page is not published');
}

$front_page_id = (int) $old_options['page_on_front'];
if ($posts_page->ID === $front_page_id) {
    drthai_routing_fail('tin-tuc Page cannot also be the front page');
}

$target_options = array(
    'show_on_front'       => 'page',
    'page_for_posts'      => (string) $posts_page->ID,
    'permalink_structure' => '/tin-tuc/%postname%/',
    'category_base'       => 'chuyen-muc',
    'tag_base'            => 'tu-khoa',
);

update_option('show_on_front', $target_options['show_on_front']);
update_option('page_for_posts', $target_options['page_for_posts']);
global $wp_rewrite;
$wp_rewrite->set_permalink_structure($target_options['permalink_structure']);
$wp_rewrite->set_category_base($target_options['category_base']);
$wp_rewrite->set_tag_base($target_options['tag_base']);
create_initial_taxonomies();
flush_rewrite_rules(false);

$expected_options = $target_options;
$expected_options['page_on_front'] = $old_options['page_on_front'];

foreach ($expected_options as $option_name => $expected_value) {
    $actual_value = (string) get_option($option_name, '');
    echo 'NEW_' . strtoupper($option_name) . '=' . $actual_value . PHP_EOL;
    if ((string) $expected_value !== $actual_value) {
        drthai_routing_fail("verification failed for {$option_name}");
    }
}

echo 'TIN_TUC_RESOLVED_ID=' . $posts_page->ID . PHP_EOL;
echo 'TIN_TUC_STATUS=' . $posts_page->post_status . PHP_EOL;
echo "SOFT_FLUSH=PASS\n";
echo "MIGRATION_STATUS=PASS\n";
