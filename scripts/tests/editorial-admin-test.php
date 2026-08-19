<?php
/**
 * Local integration tests for Content Operations 1.2 / C2.
 *
 * Synthetic users, Posts, media and taxonomy are removed after the run.
 */

declare(strict_types=1);

define('DISABLE_WP_CRON', true);

require '/var/www/html/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/user.php';

$created_posts = array();
$created_users = array();
$created_attachments = array();
$created_terms = array();
$tests_run = 0;
$exit_code = 0;

function drthai_c2_assert(bool $condition, string $label): void
{
    global $tests_run;
    ++$tests_run;
    if (!$condition) {
        throw new RuntimeException("FAIL {$label}");
    }
    echo "PASS {$label}\n";
}

function drthai_c2_create_user(string $role, string $suffix): int
{
    global $created_users;
    $user_id = wp_insert_user(
        array(
            'user_login' => "drthai-c2-{$role}-{$suffix}",
            'user_pass'  => wp_generate_password(32, true, true),
            'user_email' => "drthai-c2-{$role}-{$suffix}@example.invalid",
            'role'       => $role,
        )
    );
    if (is_wp_error($user_id)) {
        throw new RuntimeException('Unable to create test user: ' . $user_id->get_error_code());
    }
    $created_users[] = (int) $user_id;
    return (int) $user_id;
}

function drthai_c2_create_post(int $author_id, string $suffix, string $excerpt = ''): int
{
    global $created_posts;
    $post_id = wp_insert_post(
        array(
            'post_type'    => 'post',
            'post_status'  => 'draft',
            'post_title'   => "C2 {$suffix}",
            'post_content' => '<!-- wp:paragraph --><p>Synthetic general health editorial fixture.</p><!-- /wp:paragraph -->',
            'post_excerpt' => $excerpt,
            'post_author'  => $author_id,
        ),
        true
    );
    if (is_wp_error($post_id)) {
        throw new RuntimeException('Unable to create test Post: ' . $post_id->get_error_code());
    }
    $created_posts[] = (int) $post_id;
    return (int) $post_id;
}

function drthai_c2_create_image(string $suffix): int
{
    global $created_attachments;
    $temporary_file = wp_tempnam("drthai-c2-general-health-{$suffix}.png");
    $image_bytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);
    if (false === $temporary_file || false === $image_bytes || false === file_put_contents($temporary_file, $image_bytes)) {
        throw new RuntimeException('Unable to create patient-free C2 image fixture.');
    }
    $attachment_id = media_handle_sideload(
        array(
            'name'     => "drthai-c2-general-health-{$suffix}.png",
            'type'     => 'image/png',
            'tmp_name' => $temporary_file,
            'error'    => 0,
            'size'     => filesize($temporary_file),
        ),
        0,
        'C2 synthetic general health illustration'
    );
    if (is_wp_error($attachment_id)) {
        @unlink($temporary_file);
        throw new RuntimeException('Unable to upload C2 image fixture: ' . $attachment_id->get_error_code());
    }
    $created_attachments[] = (int) $attachment_id;
    return (int) $attachment_id;
}

function drthai_c2_query(array $source, array $args): WP_Query
{
    $filters = drthai_health_editorial_admin_sanitize_filters($source);
    $args['drthai_c2_active'] = true;
    $args['drthai_c2_review_status'] = $filters['review_status'];
    $args['drthai_c2_reviewer_id'] = $filters['reviewer_id'];
    $args['drthai_c2_media_status'] = $filters['media_status'];
    $args['drthai_c2_health_status'] = $filters['health_status'];
    $args['drthai_c4_lifecycle'] = $filters['lifecycle'];
    if (isset($source['orderby']) && 'drthai_updated_date' === sanitize_key($source['orderby'])) {
        $args['orderby'] = 'modified';
    } elseif (isset($source['orderby']) && 'drthai_reviewed_date' === sanitize_key($source['orderby'])) {
        $args['orderby'] = 'drthai_reviewed_date';
    }
    $order = isset($source['order']) ? strtoupper(sanitize_key($source['order'])) : 'DESC';
    $args['order'] = in_array($order, array('ASC', 'DESC'), true) ? $order : 'DESC';
    return new WP_Query($args);
}

function drthai_c2_ids(WP_Query $query): array
{
    return array_map('intval', wp_list_pluck($query->posts, 'ID'));
}

function drthai_c2_capture_column(string $column, int $post_id): string
{
    ob_start();
    drthai_health_render_editorial_admin_column($column, $post_id);
    return trim((string) ob_get_clean());
}

function drthai_c2_attachment_snapshot(): array
{
    $snapshot = array();
    foreach (get_posts(array('post_type' => 'attachment', 'post_status' => 'inherit', 'posts_per_page' => -1, 'orderby' => 'ID', 'order' => 'ASC')) as $attachment) {
        $snapshot[$attachment->ID] = array(
            'file' => get_post_meta($attachment->ID, '_wp_attached_file', true),
            'alt'  => get_post_meta($attachment->ID, '_wp_attachment_image_alt', true),
            'meta' => get_post_meta($attachment->ID, '_wp_attachment_metadata', true),
        );
    }
    return $snapshot;
}

$baseline_posts = get_posts(array('post_type' => array('post', 'page'), 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'orderby' => 'ID', 'order' => 'ASC'));
$baseline_attachments = drthai_c2_attachment_snapshot();
$baseline_categories = get_terms(array('taxonomy' => 'category', 'hide_empty' => false, 'fields' => 'ids'));
$baseline_tags = get_terms(array('taxonomy' => 'post_tag', 'hide_empty' => false, 'fields' => 'ids'));
$baseline_comments = array();
foreach ($baseline_posts as $baseline_post_id) {
    $baseline_comments[(int) $baseline_post_id] = get_post_field('comment_status', $baseline_post_id);
}
$baseline_admin_role_cap = (bool) get_role('administrator')->has_cap(DRTHAI_MEDICAL_REVIEW_CAPABILITY);

try {
    global $wpdb;

    $suffix = strtolower(wp_generate_password(8, false, false));
    $admin_one = drthai_c2_create_user('administrator', $suffix . 'a');
    $admin_two = drthai_c2_create_user('administrator', $suffix . 'b');
    $editor_id = drthai_c2_create_user('editor', $suffix);
    $editor_caps_before = get_user_by('id', $editor_id)->caps;
    wp_set_current_user($admin_one);

    $term = wp_insert_term("C2 Editorial {$suffix}", 'category', array('slug' => "c2-editorial-{$suffix}"));
    if (is_wp_error($term)) {
        throw new RuntimeException('Unable to create C2 category: ' . $term->get_error_code());
    }
    $category_id = (int) $term['term_id'];
    $created_terms[] = $category_id;

    $image_id = drthai_c2_create_image($suffix);
    $missing_alt_post = drthai_c2_create_post($admin_one, "missing-alt-{$suffix}", 'Excerpt kiểm thử media');
    wp_set_post_categories($missing_alt_post, array($category_id));
    set_post_thumbnail($missing_alt_post, $image_id);
    drthai_health_mark_post_medically_reviewed($missing_alt_post);

    $unreviewed_post = drthai_c2_create_post($admin_one, "unreviewed-{$suffix}");
    $missing_image_post = drthai_c2_create_post($admin_one, "missing-image-{$suffix}", 'Excerpt kiểm thử review');
    wp_set_post_categories($missing_image_post, array($category_id));
    drthai_health_mark_post_medically_reviewed($missing_image_post);

    $complete_image_id = drthai_c2_create_image($suffix . '-complete');
    update_post_meta($complete_image_id, '_wp_attachment_image_alt', 'Minh họa sức khỏe tổng quát cho kiểm thử quản trị');
    $ready_post = drthai_c2_create_post($admin_one, "ready-searchable-{$suffix}", 'Excerpt hoàn chỉnh cho kiểm thử');
    wp_set_post_categories($ready_post, array($category_id));
    set_post_thumbnail($ready_post, $complete_image_id);
    drthai_health_mark_post_medically_reviewed($ready_post);

    wp_set_current_user($admin_two);
    $second_ready_post = drthai_c2_create_post($admin_two, "second-ready-{$suffix}", 'Excerpt hoàn chỉnh thứ hai');
    wp_set_post_categories($second_ready_post, array($category_id));
    set_post_thumbnail($second_ready_post, $complete_image_id);
    drthai_health_mark_post_medically_reviewed($second_ready_post);
    wp_set_current_user($admin_one);

    $published_post = drthai_c2_create_post($admin_one, "published-ok-{$suffix}", 'Excerpt hoàn chỉnh đã xuất bản');
    wp_set_post_categories($published_post, array($category_id));
    set_post_thumbnail($published_post, $complete_image_id);
    drthai_health_mark_post_medically_reviewed($published_post);
    wp_update_post(array('ID' => $published_post, 'post_status' => 'publish'));

    $original_get = $_GET;
    $_GET = array('drthai_reviewer' => (string) $admin_one);
    ob_start();
    drthai_health_render_editorial_admin_filters('post', 'top');
    $filter_markup = (string) ob_get_clean();
    $_GET = $original_get;
    drthai_c2_assert(
        false !== strpos($filter_markup, 'drthai-review-status-filter')
        && false !== strpos($filter_markup, 'drthai-reviewer-filter')
        && false !== strpos($filter_markup, 'drthai-media-status-filter')
        && false !== strpos($filter_markup, 'drthai-health-filter')
        && false !== strpos($filter_markup, 'drthai-lifecycle-filter')
        && false !== strpos($filter_markup, get_user_by('id', $admin_one)->display_name),
        'native filter controls render with accessible labels and relevant reviewers'
    );

    $columns = drthai_health_editorial_admin_columns(array('cb' => 'cb', 'title' => 'Title', 'author' => 'Author', 'categories' => 'Categories', 'date' => 'Date'));
    drthai_c2_assert(isset($columns['drthai_medical_review']), 'Posts List contains Medical Review column');
    drthai_c2_assert(isset($columns['drthai_reviewed_date']), 'Posts List contains Reviewed Date column');
    drthai_c2_assert(isset($columns['drthai_media_status']), 'Posts List contains Media column');
    drthai_c2_assert(isset($columns['drthai_updated_date']), 'Posts List contains Updated Date column');
    drthai_c2_assert(isset($columns['drthai_editorial_health']), 'Posts List contains Editorial Health column');
    drthai_c2_assert('Review' === $columns['drthai_medical_review'], 'Medical Review column uses compact Review heading');
    drthai_c2_assert('Cập nhật' === $columns['drthai_updated_date'] && 'Chất lượng' === $columns['drthai_editorial_health'] && 'Vòng đời' === $columns['drthai_lifecycle'], 'primary editorial columns use compact Vietnamese headings');
    $default_hidden = drthai_health_editorial_admin_default_hidden_columns(array('existing-preference'), (object) array('id' => 'edit-post', 'post_type' => 'post'));
    drthai_c2_assert(!array_diff(array('existing-preference', 'tags', 'comments', 'drthai_reviewed_date', 'drthai_media_status', 'wpseo-score', 'wpseo-score-readability', 'wpseo-links', 'wpseo-linked'), $default_hidden), 'secondary and Yoast auxiliary columns are hidden by default without removing existing defaults');
    drthai_c2_assert(array('unchanged') === drthai_health_editorial_admin_default_hidden_columns(array('unchanged'), (object) array('id' => 'edit-page', 'post_type' => 'page')), 'default hidden columns are scoped away from other screens');
    $review_output = drthai_c2_capture_column('drthai_medical_review', $ready_post);
    drthai_c2_assert(false !== strpos($review_output, 'Đã rà soát') && false !== strpos($review_output, get_user_by('id', $admin_one)->display_name), 'reviewed Post shows compact state and reviewer name');
    $reviewed_at = get_post_meta($ready_post, DRTHAI_REVIEWED_AT_META, true);
    drthai_c2_assert(drthai_health_format_reviewed_at($reviewed_at) === drthai_c2_capture_column('drthai_reviewed_date', $ready_post), 'Reviewed Date uses deterministic site-local formatting');
    drthai_c2_assert('Chưa rà soát' === drthai_c2_capture_column('drthai_medical_review', $unreviewed_post), 'unreviewed Post is clearly identified');
    drthai_c2_assert('Thiếu ảnh' === drthai_c2_capture_column('drthai_media_status', $missing_image_post), 'missing Featured Image is identified');
    drthai_c2_assert('Thiếu Alt' === drthai_c2_capture_column('drthai_media_status', $missing_alt_post), 'missing Alt Text is identified');
    drthai_c2_assert('OK' === drthai_c2_capture_column('drthai_media_status', $ready_post), 'media-complete Post is identified');

    $unreviewed_health = drthai_health_get_editorial_health($unreviewed_post);
    drthai_c2_assert(in_array('Thiếu excerpt', $unreviewed_health['reasons'], true), 'empty Excerpt produces quality warning');
    drthai_c2_assert(in_array('Chưa phân loại', $unreviewed_health['reasons'], true), 'default Category produces quality warning');
    $ready_health = drthai_health_get_editorial_health($ready_post);
    drthai_c2_assert('ready' === $ready_health['state'] && !$ready_health['reasons'], 'fully compliant Draft receives READY health state');
    $published_health = drthai_health_get_editorial_health($published_post);
    drthai_c2_assert('ok' === $published_health['state'] && !$published_health['reasons'], 'fully compliant Published Post receives OK health state');
    $health_output = drthai_c2_capture_column('drthai_editorial_health', $unreviewed_post);
    drthai_c2_assert(false !== strpos($health_output, 'Cần xử lý · 4'), 'Needs Attention row shows compact issue count');
    drthai_c2_assert(false !== strpos($health_output, 'screen-reader-text') && false !== strpos($health_output, 'Thiếu review'), 'full Editorial Health reasons remain available to assistive technology');

    $scope_ids = array($missing_alt_post, $unreviewed_post, $missing_image_post, $ready_post, $second_ready_post, $published_post);
    $base_args = array('post_type' => 'post', 'post_status' => 'any', 'post__in' => $scope_ids, 'posts_per_page' => 50, 'orderby' => 'ID', 'order' => 'ASC');
    $reviewed = drthai_c2_ids(drthai_c2_query(array('drthai_review_status' => 'reviewed'), $base_args));
    drthai_c2_assert(in_array($ready_post, $reviewed, true) && !in_array($unreviewed_post, $reviewed, true), 'Reviewed filter returns reviewed Posts');
    $unreviewed = drthai_c2_ids(drthai_c2_query(array('drthai_review_status' => 'unreviewed'), $base_args));
    drthai_c2_assert(in_array($unreviewed_post, $unreviewed, true) && !in_array($ready_post, $unreviewed, true), 'Unreviewed filter returns unreviewed Posts');
    $reviewer_one = drthai_c2_ids(drthai_c2_query(array('drthai_reviewer' => (string) $admin_one), $base_args));
    drthai_c2_assert(in_array($ready_post, $reviewer_one, true) && !in_array($second_ready_post, $reviewer_one, true), 'Reviewer filter returns only selected reviewer Posts');
    $missing_image = drthai_c2_ids(drthai_c2_query(array('drthai_media_status' => 'missing_image'), $base_args));
    drthai_c2_assert(in_array($missing_image_post, $missing_image, true) && !in_array($ready_post, $missing_image, true), 'Missing Featured Image filter works');
    $missing_alt = drthai_c2_ids(drthai_c2_query(array('drthai_media_status' => 'missing_alt'), $base_args));
    drthai_c2_assert(in_array($missing_alt_post, $missing_alt, true) && !in_array($ready_post, $missing_alt, true), 'Missing Alt Text filter works');
    $media_complete = drthai_c2_ids(drthai_c2_query(array('drthai_media_status' => 'complete'), $base_args));
    drthai_c2_assert(in_array($ready_post, $media_complete, true) && !in_array($missing_alt_post, $media_complete, true), 'Media Complete filter works');
    $attention = drthai_c2_ids(drthai_c2_query(array('drthai_editorial_health' => 'attention'), $base_args));
    drthai_c2_assert(in_array($unreviewed_post, $attention, true) && !in_array($ready_post, $attention, true), 'Needs Attention filter works');
    $healthy = drthai_c2_ids(drthai_c2_query(array('drthai_editorial_health' => 'healthy'), $base_args));
    drthai_c2_assert(in_array($ready_post, $healthy, true) && !in_array($unreviewed_post, $healthy, true), 'OK or Ready filter works');

    $category_query = drthai_c2_query(array('drthai_editorial_health' => 'healthy'), array_merge($base_args, array('cat' => $category_id)));
    drthai_c2_assert(in_array($ready_post, drthai_c2_ids($category_query), true), 'C2 filter cooperates with native Category filtering');
    $status_query = drthai_c2_query(array('drthai_media_status' => 'complete'), array_merge($base_args, array('post_status' => 'publish')));
    drthai_c2_assert(array($published_post) === drthai_c2_ids($status_query), 'C2 filter cooperates with native Status filtering');
    $search_query = drthai_c2_query(array('drthai_editorial_health' => 'healthy'), array_merge($base_args, array('s' => "ready-searchable-{$suffix}")));
    drthai_c2_assert(array($ready_post) === drthai_c2_ids($search_query), 'C2 filter cooperates with native Posts search');

    $wpdb->update($wpdb->posts, array('post_modified' => '2026-01-01 01:00:00', 'post_modified_gmt' => '2025-12-31 18:00:00'), array('ID' => $ready_post));
    $wpdb->update($wpdb->posts, array('post_modified' => '2026-02-01 01:00:00', 'post_modified_gmt' => '2026-01-31 18:00:00'), array('ID' => $second_ready_post));
    clean_post_cache($ready_post);
    clean_post_cache($second_ready_post);
    $sort_args = array('post_type' => 'post', 'post_status' => 'any', 'post__in' => array($ready_post, $second_ready_post), 'posts_per_page' => 10);
    drthai_c2_assert(array($ready_post, $second_ready_post) === drthai_c2_ids(drthai_c2_query(array('orderby' => 'drthai_updated_date', 'order' => 'ASC'), $sort_args)), 'Updated Date ascending works');
    drthai_c2_assert(array($second_ready_post, $ready_post) === drthai_c2_ids(drthai_c2_query(array('orderby' => 'drthai_updated_date', 'order' => 'DESC'), $sort_args)), 'Updated Date descending works');
    $review_order = array($ready_post, $second_ready_post);
    usort($review_order, static function ($left, $right): int {
        return strcmp((string) get_post_meta($left, DRTHAI_REVIEWED_AT_META, true), (string) get_post_meta($right, DRTHAI_REVIEWED_AT_META, true));
    });
    drthai_c2_assert($review_order === drthai_c2_ids(drthai_c2_query(array('orderby' => 'drthai_reviewed_date', 'order' => 'ASC'), $sort_args)), 'Reviewed Date ascending works');
    drthai_c2_assert(array_reverse($review_order) === drthai_c2_ids(drthai_c2_query(array('orderby' => 'drthai_reviewed_date', 'order' => 'DESC'), $sort_args)), 'Reviewed Date descending works');
    drthai_c2_assert($review_order === drthai_c2_ids(drthai_c2_query(array('orderby' => 'drthai_reviewed_date', 'order' => 'ASC', 'drthai_editorial_health' => 'healthy'), $sort_args)), 'Reviewed Date sorting works with a C2 filter');

    $invalid = drthai_health_editorial_admin_sanitize_filters(array(
        'drthai_review_status' => 'reviewed OR 1=1',
        'drthai_reviewer' => '-5 UNION SELECT',
        'drthai_media_status' => array('complete'),
        'drthai_editorial_health' => 'anything',
    ));
    drthai_c2_assert(array('review_status' => '', 'reviewer_id' => 0, 'media_status' => '', 'health_status' => '', 'lifecycle' => '') === $invalid, 'query parameters are sanitized and allowlisted');
    drthai_c2_assert(!current_user_can('edit_post_meta', $ready_post, DRTHAI_MEDICAL_REVIEWER_META), 'C2 cannot write protected review metadata');
    ob_start();
    do_action('quick_edit_custom_box', 'drthai_editorial_health', 'post');
    $quick_edit = (string) ob_get_clean();
    drthai_c2_assert(false === strpos($quick_edit, DRTHAI_MEDICAL_REVIEWER_META), 'Quick Edit cannot forge Medical Reviewer');
    ob_start();
    do_action('bulk_edit_custom_box', 'drthai_editorial_health', 'post');
    $bulk_edit = (string) ob_get_clean();
    drthai_c2_assert(false === strpos($bulk_edit, DRTHAI_MEDICAL_REVIEWER_META), 'Bulk Edit cannot forge Medical Reviewer');

    $content_before_sorting = array();
    foreach ($scope_ids as $post_id) {
        $content_before_sorting[$post_id] = get_post_field('post_content', $post_id);
    }
    drthai_c2_query(array('drthai_editorial_health' => 'attention'), $base_args);
    foreach ($content_before_sorting as $post_id => $content) {
        drthai_c2_assert($content === get_post_field('post_content', $post_id), "list filtering does not change synthetic Post content: {$post_id}");
    }

    $performance_ids = array();
    for ($index = 1; $index <= 120; ++$index) {
        $performance_ids[] = drthai_c2_create_post($admin_one, "performance-{$suffix}-{$index}");
    }
    $performance_query = drthai_c2_query(
        array('drthai_editorial_health' => 'attention'),
        array('post_type' => 'post', 'post_status' => 'draft', 'post__in' => $performance_ids, 'posts_per_page' => 20, 'paged' => 2, 'orderby' => 'ID', 'order' => 'ASC')
    );
    drthai_c2_assert(20 === $performance_query->post_count, 'performance sanity preserves bounded page size');
    drthai_c2_assert(6 === $performance_query->max_num_pages && 120 === $performance_query->found_posts, 'performance sanity preserves pagination across 120 Posts');
    drthai_c2_assert(false !== strpos($performance_query->request, 'EXISTS') && false !== strpos($performance_query->request, 'LIMIT 20, 20'), 'filtering is SQL-bounded and paginated instead of scanning results in PHP');

    $reviewers = drthai_health_editorial_admin_reviewers();
    drthai_c2_assert(count($reviewers) <= 200, 'reviewer dropdown query is bounded');
    drthai_c2_assert($editor_caps_before === get_user_by('id', $editor_id)->caps, 'C2 grants no capability to an Editor');
    drthai_c2_assert($baseline_admin_role_cap === (bool) get_role('administrator')->has_cap(DRTHAI_MEDICAL_REVIEW_CAPABILITY), 'C1 Administrator capability semantics remain unchanged');
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    $exit_code = 1;
} finally {
    wp_set_current_user(0);
    foreach (array_reverse($created_posts) as $post_id) {
        wp_delete_post($post_id, true);
    }
    foreach (array_reverse($created_attachments) as $attachment_id) {
        wp_delete_attachment($attachment_id, true);
    }
    foreach (array_reverse($created_terms) as $term_id) {
        wp_delete_term($term_id, 'category');
    }
    foreach (array_reverse($created_users) as $user_id) {
        wp_delete_user($user_id);
    }
}

$final_posts = get_posts(array('post_type' => array('post', 'page'), 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'orderby' => 'ID', 'order' => 'ASC'));
drthai_c2_assert($baseline_posts === $final_posts, 'existing Post and Page inventory is restored exactly');
drthai_c2_assert($baseline_attachments === drthai_c2_attachment_snapshot(), 'existing Attachment filenames, Alt Text and metadata are unchanged');
drthai_c2_assert($baseline_categories === get_terms(array('taxonomy' => 'category', 'hide_empty' => false, 'fields' => 'ids')), 'existing Categories are unchanged');
drthai_c2_assert($baseline_tags === get_terms(array('taxonomy' => 'post_tag', 'hide_empty' => false, 'fields' => 'ids')), 'existing Tags are unchanged');
foreach ($baseline_comments as $post_id => $comment_status) {
    drthai_c2_assert($comment_status === get_post_field('comment_status', $post_id), "existing comment status is preserved for {$post_id}");
}

if ($exit_code) {
    exit($exit_code);
}

echo "C2_SYNTHETIC_SCALE=120\n";
echo "C2_TESTS_RUN={$tests_run}\n";
echo "C2_TEST_STATUS=PASS\n";
