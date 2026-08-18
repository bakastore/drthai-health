<?php
/**
 * Local integration tests for Content Operations 1.2 / C3.
 *
 * Run explicitly inside wp-env. Synthetic Posts, users and media are removed.
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
$tests_run = 0;
$exit_code = 0;

function drthai_c3_assert(bool $condition, string $label): void
{
    global $tests_run;
    ++$tests_run;
    if (!$condition) {
        throw new RuntimeException("FAIL {$label}");
    }
    echo "PASS {$label}\n";
}

function drthai_c3_create_user(string $suffix): int
{
    global $created_users;
    $user_id = wp_insert_user(
        array(
            'user_login' => "drthai-c3-admin-{$suffix}",
            'user_pass'  => wp_generate_password(32, true, true),
            'user_email' => "drthai-c3-admin-{$suffix}@example.invalid",
            'role'       => 'administrator',
        )
    );
    if (is_wp_error($user_id)) {
        throw new RuntimeException('Unable to create test user: ' . $user_id->get_error_code());
    }
    $created_users[] = (int) $user_id;
    return (int) $user_id;
}

function drthai_c3_create_post(int $author_id, string $status = 'draft'): int
{
    global $created_posts;
    $post_id = wp_insert_post(
        array(
            'post_type'    => 'post',
            'post_status'  => $status,
            'post_title'   => 'C3 synthetic media governance test',
            'post_content' => '<!-- wp:paragraph --><p>Synthetic general health illustration test.</p><!-- /wp:paragraph -->',
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

function drthai_c3_create_image(string $suffix, string $alt_text = ''): int
{
    global $created_attachments;
    $temporary_file = wp_tempnam("drthai-c3-general-health-illustration-{$suffix}.png");
    $image_bytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);
    if (false === $temporary_file || false === $image_bytes || false === file_put_contents($temporary_file, $image_bytes)) {
        throw new RuntimeException('Unable to create patient-free synthetic image fixture.');
    }

    $attachment_id = media_handle_sideload(
        array(
            'name'     => "drthai-c3-general-health-illustration-{$suffix}.png",
            'type'     => 'image/png',
            'tmp_name' => $temporary_file,
            'error'    => 0,
            'size'     => filesize($temporary_file),
        ),
        0,
        'C3 synthetic general health illustration'
    );
    if (is_wp_error($attachment_id)) {
        @unlink($temporary_file);
        throw new RuntimeException('Unable to upload synthetic image: ' . $attachment_id->get_error_code());
    }

    $created_attachments[] = (int) $attachment_id;
    if ('' !== $alt_text) {
        update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt_text);
    }
    return (int) $attachment_id;
}

function drthai_c3_schedule_post(int $post_id): void
{
    wp_update_post(
        array(
            'ID'            => $post_id,
            'post_status'   => 'future',
            'edit_date'     => true,
            'post_date'     => wp_date('Y-m-d H:i:s', time() + DAY_IN_SECONDS, wp_timezone()),
            'post_date_gmt' => gmdate('Y-m-d H:i:s', time() + DAY_IN_SECONDS),
        )
    );
}

function drthai_c3_attachment_snapshot(): array
{
    $snapshot = array();
    $attachments = get_posts(
        array(
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => -1,
            'orderby'        => 'ID',
            'order'          => 'ASC',
        )
    );
    foreach ($attachments as $attachment) {
        $snapshot[$attachment->ID] = array(
            'file'     => get_post_meta($attachment->ID, '_wp_attached_file', true),
            'alt'      => get_post_meta($attachment->ID, '_wp_attachment_image_alt', true),
            'metadata' => get_post_meta($attachment->ID, '_wp_attachment_metadata', true),
        );
    }
    return $snapshot;
}

$baseline_posts = get_posts(
    array(
        'post_type'      => array('post', 'page'),
        'post_status'    => 'any',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'orderby'        => 'ID',
        'order'          => 'ASC',
    )
);
$baseline_thumbnails = array();
foreach ($baseline_posts as $baseline_post_id) {
    $baseline_thumbnails[(int) $baseline_post_id] = get_post_thumbnail_id($baseline_post_id);
}
$baseline_attachments = drthai_c3_attachment_snapshot();
$legacy_post_id = 24;
$legacy_status = get_post_status($legacy_post_id);
$legacy_thumbnail = get_post_thumbnail_id($legacy_post_id);
$theme_svg_hashes = array();
foreach (glob('/var/www/html/wp-content/themes/drthai-health/assets/images/*.svg') as $svg_path) {
    $theme_svg_hashes[basename($svg_path)] = hash_file('sha256', $svg_path);
}

try {
    $suffix = strtolower(wp_generate_password(8, false, false));
    $admin_id = drthai_c3_create_user($suffix);
    wp_set_current_user($admin_id);

    drthai_c3_assert(function_exists('drthai_health_validate_publication_media'), 'C3 media validator is available');
    drthai_c3_assert(false !== has_action('admin_notices', 'drthai_health_media_safety_notice'), 'Media Library safety guidance is registered');
    drthai_c3_assert(!empty($baseline_attachments), 'existing Media Library contains an Attachment for compatibility testing');
    foreach (array_keys($baseline_attachments) as $existing_attachment_id) {
        drthai_c3_assert(wp_attachment_is_image($existing_attachment_id) && false !== wp_get_attachment_url($existing_attachment_id), "existing image Attachment remains usable: {$existing_attachment_id}");
    }

    $image_id = drthai_c3_create_image($suffix);
    drthai_c3_assert(wp_attachment_is_image($image_id), 'standard WordPress image upload remains functional');
    drthai_c3_assert(false !== wp_get_attachment_url($image_id), 'uploaded Attachment remains usable');
    drthai_c3_assert('' === get_post_meta($image_id, '_wp_attachment_image_alt', true), 'synthetic image begins with empty Alt Text');

    $draft_no_image = drthai_c3_create_post($admin_id);
    drthai_c3_assert(true === drthai_health_mark_post_medically_reviewed($draft_no_image), 'Draft without image is medically reviewed for isolation');
    wp_update_post(array('ID' => $draft_no_image, 'post_status' => 'publish'));
    drthai_c3_assert('draft' === get_post_status($draft_no_image), 'Draft without Featured Image cannot Publish');
    drthai_c3_schedule_post($draft_no_image);
    drthai_c3_assert('draft' === get_post_status($draft_no_image), 'Draft without Featured Image cannot Schedule');

    $pending_no_image = drthai_c3_create_post($admin_id, 'pending');
    drthai_c3_assert(true === drthai_health_mark_post_medically_reviewed($pending_no_image), 'Pending Post without image is medically reviewed for isolation');
    wp_update_post(array('ID' => $pending_no_image, 'post_status' => 'publish'));
    drthai_c3_assert('pending' === get_post_status($pending_no_image), 'Pending Post without Featured Image cannot Publish');
    drthai_c3_schedule_post($pending_no_image);
    drthai_c3_assert('pending' === get_post_status($pending_no_image), 'Pending Post without Featured Image cannot Schedule');

    $empty_alt_post = drthai_c3_create_post($admin_id);
    drthai_c3_assert(true === drthai_health_mark_post_medically_reviewed($empty_alt_post), 'empty-Alt Post is medically reviewed for isolation');
    set_post_thumbnail($empty_alt_post, $image_id);
    wp_update_post(array('ID' => $empty_alt_post, 'post_status' => 'publish'));
    drthai_c3_assert('draft' === get_post_status($empty_alt_post), 'Featured Image with empty Alt Text cannot Publish');
    drthai_c3_schedule_post($empty_alt_post);
    drthai_c3_assert('draft' === get_post_status($empty_alt_post), 'Featured Image with empty Alt Text cannot Schedule');

    update_post_meta($image_id, '_wp_attachment_image_alt', 'Minh họa sức khỏe tổng quát dùng cho kiểm thử');
    drthai_c3_assert(true === drthai_health_validate_publication_media($empty_alt_post), 'Featured Image with valid Alt Text satisfies media validation');

    $unreviewed_compliant = drthai_c3_create_post($admin_id);
    set_post_thumbnail($unreviewed_compliant, $image_id);
    wp_update_post(array('ID' => $unreviewed_compliant, 'post_status' => 'publish'));
    drthai_c3_assert('draft' === get_post_status($unreviewed_compliant), 'media-compliant medically unreviewed Post remains blocked');

    $compliant_publish = drthai_c3_create_post($admin_id);
    set_post_thumbnail($compliant_publish, $image_id);
    drthai_health_mark_post_medically_reviewed($compliant_publish);
    wp_update_post(
        array(
            'ID'            => $compliant_publish,
            'post_status'   => 'publish',
            'post_date'     => current_time('mysql'),
            'post_date_gmt' => current_time('mysql', true),
        )
    );
    drthai_c3_assert('publish' === get_post_status($compliant_publish), 'fully C1 and C3 compliant Post can Publish');
    $rendered_thumbnail = get_the_post_thumbnail($compliant_publish, 'large');
    drthai_c3_assert(false !== strpos($rendered_thumbnail, 'Minh họa sức khỏe tổng quát dùng cho kiểm thử'), 'valid Featured Image renders with Alt Text');

    $compliant_schedule = drthai_c3_create_post($admin_id);
    set_post_thumbnail($compliant_schedule, $image_id);
    drthai_health_mark_post_medically_reviewed($compliant_schedule);
    drthai_c3_schedule_post($compliant_schedule);
    drthai_c3_assert('future' === get_post_status($compliant_schedule), 'fully compliant Post can Schedule');

    $rest_no_image = drthai_c3_create_post($admin_id);
    drthai_health_mark_post_medically_reviewed($rest_no_image);
    $rest_request = new WP_REST_Request('POST', '/wp/v2/posts/' . $rest_no_image);
    $rest_request->set_param('id', $rest_no_image);
    $rest_request->set_param('status', 'publish');
    $rest_response = rest_do_request($rest_request);
    drthai_c3_assert($rest_response->is_error() && 'drthai_featured_image_required' === $rest_response->as_error()->get_error_code(), 'REST cannot bypass Featured Image requirement');

    update_post_meta($image_id, '_wp_attachment_image_alt', '');
    $rest_empty_alt = drthai_c3_create_post($admin_id);
    drthai_health_mark_post_medically_reviewed($rest_empty_alt);
    $rest_request = new WP_REST_Request('POST', '/wp/v2/posts/' . $rest_empty_alt);
    $rest_request->set_param('id', $rest_empty_alt);
    $rest_request->set_param('status', 'publish');
    $rest_request->set_param('featured_media', $image_id);
    $rest_response = rest_do_request($rest_request);
    drthai_c3_assert($rest_response->is_error() && 'drthai_featured_image_alt_required' === $rest_response->as_error()->get_error_code(), 'REST cannot bypass Featured Image Alt Text requirement');

    update_post_meta($image_id, '_wp_attachment_image_alt', 'Minh họa sức khỏe tổng quát dùng cho kiểm thử');
    $rest_compliant = drthai_c3_create_post($admin_id);
    drthai_health_mark_post_medically_reviewed($rest_compliant);
    $rest_request = new WP_REST_Request('POST', '/wp/v2/posts/' . $rest_compliant);
    $rest_request->set_param('id', $rest_compliant);
    $rest_request->set_param('status', 'publish');
    $rest_request->set_param('featured_media', $image_id);
    $rest_response = rest_do_request($rest_request);
    $rest_result = $rest_response->is_error() ? $rest_response->as_error()->get_error_code() : 'status-' . get_post_status($rest_compliant);
    drthai_c3_assert(!$rest_response->is_error() && 'publish' === get_post_status($rest_compliant), "fully compliant REST publication succeeds (actual: {$rest_result})");
    drthai_c3_assert($image_id === get_post_thumbnail_id($rest_compliant), 'REST publication assigns the validated Featured Image');

    $api_compliant = drthai_c3_create_post($admin_id);
    drthai_health_mark_post_medically_reviewed($api_compliant);
    wp_update_post(
        array(
            'ID'          => $api_compliant,
            'post_status' => 'publish',
            'meta_input'  => array('_thumbnail_id' => $image_id),
        )
    );
    drthai_c3_assert('publish' === get_post_status($api_compliant), 'fully compliant non-REST publication succeeds with candidate media');
    drthai_c3_assert($image_id === get_post_thumbnail_id($api_compliant), 'non-REST publication assigns the validated Featured Image');

    $api_bypass = drthai_c3_create_post($admin_id);
    drthai_health_mark_post_medically_reviewed($api_bypass);
    wp_update_post(array('ID' => $api_bypass, 'post_status' => 'publish'));
    drthai_c3_assert('draft' === get_post_status($api_bypass), 'non-REST WordPress Post API cannot bypass media requirements');

    $allowed_mimes = get_allowed_mime_types($admin_id);
    drthai_c3_assert(!isset($allowed_mimes['svg']) && !isset($allowed_mimes['svgz']), 'unrestricted SVG Media Library upload is not enabled');
    drthai_c3_assert(!empty($theme_svg_hashes), 'existing trusted theme SVG assets are present');
    drthai_c3_assert('publish' === $legacy_status && 'publish' === get_post_status($legacy_post_id), 'legacy Published Post remains Published');
    drthai_c3_assert($legacy_thumbnail === get_post_thumbnail_id($legacy_post_id), 'legacy Featured Image is not fabricated or changed');

    $single_template = file_get_contents('/var/www/html/wp-content/themes/drthai-health/templates/single.html');
    $disclaimer = 'Nội dung được cung cấp nhằm mục đích thông tin sức khỏe chung và không thay thế việc khám, chẩn đoán hoặc điều trị trực tiếp bởi nhân viên y tế phù hợp.';
    drthai_c3_assert(1 === substr_count($single_template, $disclaimer), 'C1 Medical Disclaimer remains exactly once');
    foreach (array('archive.html', 'home.html', 'search.html') as $template) {
        $template_content = file_get_contents('/var/www/html/wp-content/themes/drthai-health/templates/' . $template);
        drthai_c3_assert(false === strpos($template_content, $disclaimer), "{$template} remains free of duplicate disclaimer content");
    }
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
    foreach (array_reverse($created_users) as $user_id) {
        wp_delete_user($user_id);
    }
}

$final_posts = get_posts(
    array(
        'post_type'      => array('post', 'page'),
        'post_status'    => 'any',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'orderby'        => 'ID',
        'order'          => 'ASC',
    )
);
drthai_c3_assert($baseline_posts === $final_posts, 'existing Post and Page inventory is restored exactly');
drthai_c3_assert($baseline_attachments === drthai_c3_attachment_snapshot(), 'existing Attachment filenames, Alt Text and metadata are restored exactly');
foreach ($baseline_thumbnails as $post_id => $thumbnail_id) {
    drthai_c3_assert($thumbnail_id === get_post_thumbnail_id($post_id), "existing Featured Image is preserved for {$post_id}");
}
foreach ($theme_svg_hashes as $filename => $hash) {
    drthai_c3_assert($hash === hash_file('sha256', '/var/www/html/wp-content/themes/drthai-health/assets/images/' . $filename), "trusted theme SVG is unchanged: {$filename}");
}

if ($exit_code) {
    exit($exit_code);
}

echo "C3_TESTS_RUN={$tests_run}\n";
echo "C3_TEST_STATUS=PASS\n";
