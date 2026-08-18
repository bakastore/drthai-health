<?php
/**
 * Local integration tests for Content Operations 1.2 / C1.
 *
 * Run explicitly inside wp-env. All synthetic users and Posts are removed.
 */

declare(strict_types=1);

define('DISABLE_WP_CRON', true);

require '/var/www/html/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/user.php';

$created_posts = array();
$created_users = array();
$tests_run = 0;

function drthai_c1_assert(bool $condition, string $label): void
{
    global $tests_run;
    ++$tests_run;
    if (!$condition) {
        throw new RuntimeException("FAIL {$label}");
    }
    echo "PASS {$label}\n";
}

function drthai_c1_create_user(string $role, string $suffix): int
{
    global $created_users;
    $user_id = wp_insert_user(
        array(
            'user_login' => "drthai-c1-{$role}-{$suffix}",
            'user_pass'  => wp_generate_password(32, true, true),
            'user_email' => "drthai-c1-{$role}-{$suffix}@example.invalid",
            'role'       => $role,
        )
    );
    if (is_wp_error($user_id)) {
        throw new RuntimeException('Unable to create test user: ' . $user_id->get_error_code());
    }
    $created_users[] = (int) $user_id;
    return (int) $user_id;
}

function drthai_c1_create_post(int $author_id, string $status = 'draft'): int
{
    global $created_posts;
    $post_id = wp_insert_post(
        array(
            'post_type'    => 'post',
            'post_status'  => $status,
            'post_title'   => 'C1 synthetic editorial test',
            'post_content' => '<!-- wp:paragraph --><p>Synthetic Local-only content.</p><!-- /wp:paragraph -->',
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
$baseline_comments = array();
foreach ($baseline_posts as $baseline_post_id) {
    $baseline_comments[(int) $baseline_post_id] = get_post_field('comment_status', $baseline_post_id);
}
$legacy_post_id = 24;
$legacy_status = get_post_status($legacy_post_id);
$legacy_reviewer = get_post_meta($legacy_post_id, DRTHAI_MEDICAL_REVIEWER_META, true);
$legacy_reviewed_at = get_post_meta($legacy_post_id, DRTHAI_REVIEWED_AT_META, true);

try {
    $administrator = get_role('administrator');
    drthai_c1_assert($administrator instanceof WP_Role, 'administrator role exists');
    drthai_c1_assert($administrator->has_cap(DRTHAI_MEDICAL_REVIEW_CAPABILITY), 'administrator review capability exists');

    $registered = get_registered_meta_keys('post', 'post');
    drthai_c1_assert(isset($registered[DRTHAI_MEDICAL_REVIEWER_META]), 'reviewer metadata registered for Posts');
    drthai_c1_assert(isset($registered[DRTHAI_REVIEWED_AT_META]), 'review timestamp metadata registered for Posts');
    drthai_c1_assert(false === $registered[DRTHAI_MEDICAL_REVIEWER_META]['show_in_rest'], 'reviewer metadata excluded from REST writes');
    drthai_c1_assert(false === $registered[DRTHAI_REVIEWED_AT_META]['show_in_rest'], 'review timestamp excluded from REST writes');

    $suffix = strtolower(wp_generate_password(8, false, false));
    $admin_id = drthai_c1_create_user('administrator', $suffix);
    $editor_id = drthai_c1_create_user('editor', $suffix);
    $author_id = drthai_c1_create_user('author', $suffix);

    $editor = get_user_by('id', $editor_id);
    drthai_c1_assert(!$editor->has_cap(DRTHAI_MEDICAL_REVIEW_CAPABILITY), 'Editor is not granted review capability by default');
    $editor_edit_posts = $editor->has_cap('edit_posts');
    wp_set_current_user($admin_id);
    drthai_c1_assert(drthai_health_set_user_review_capability($editor_id, true), 'Administrator grants individual review capability');
    $editor = get_user_by('id', $editor_id);
    drthai_c1_assert($editor->has_cap(DRTHAI_MEDICAL_REVIEW_CAPABILITY), 'individual review grant applied');
    drthai_c1_assert($editor_edit_posts === $editor->has_cap('edit_posts'), 'grant preserves unrelated capabilities');
    drthai_c1_assert(drthai_health_set_user_review_capability($editor_id, false), 'Administrator revokes individual review capability');
    $editor = get_user_by('id', $editor_id);
    drthai_c1_assert(!$editor->has_cap(DRTHAI_MEDICAL_REVIEW_CAPABILITY), 'individual review revoke applied');
    drthai_c1_assert($editor_edit_posts === $editor->has_cap('edit_posts'), 'revoke preserves unrelated capabilities');

    $draft_id = drthai_c1_create_post($author_id);
    drthai_c1_assert('closed' === get_post_field('comment_status', $draft_id), 'new Post defaults Comments closed');

    wp_set_current_user($author_id);
    $invalid_cap_result = drthai_health_mark_post_medically_reviewed($draft_id);
    drthai_c1_assert(is_wp_error($invalid_cap_result), 'unauthorized Author cannot perform Medical Review');
    drthai_c1_assert(!current_user_can('edit_post_meta', $draft_id, DRTHAI_MEDICAL_REVIEWER_META), 'unauthorized reviewer metadata capability denied');
    $author_nonce = wp_create_nonce('drthai_medical_review_' . $draft_id);
    $invalid_cap_request = drthai_health_validate_medical_review_request($draft_id, $author_nonce);
    drthai_c1_assert(is_wp_error($invalid_cap_request) && 'drthai_review_capability_required' === $invalid_cap_request->get_error_code(), 'review action rejects insufficient capability');

    wp_set_current_user($admin_id);
    drthai_c1_assert(!current_user_can('edit_post_meta', $draft_id, DRTHAI_MEDICAL_REVIEWER_META), 'protected reviewer metadata rejects arbitrary Administrator edits');
    drthai_c1_assert(!current_user_can('edit_post_meta', $draft_id, DRTHAI_REVIEWED_AT_META), 'protected timestamp metadata rejects arbitrary Administrator edits');
    $invalid_nonce = drthai_health_validate_medical_review_request($draft_id, 'invalid');
    drthai_c1_assert(is_wp_error($invalid_nonce) && 'drthai_review_invalid_nonce' === $invalid_nonce->get_error_code(), 'review action rejects invalid nonce');

    $publish_result = wp_update_post(array('ID' => $draft_id, 'post_status' => 'publish'), true);
    drthai_c1_assert(!is_wp_error($publish_result) && 'draft' === get_post_status($draft_id), 'unreviewed Draft cannot Publish through Post API');
    $schedule_result = wp_update_post(
        array(
            'ID'            => $draft_id,
            'post_status'   => 'future',
            'post_date'     => gmdate('Y-m-d H:i:s', time() + HOUR_IN_SECONDS),
            'post_date_gmt' => gmdate('Y-m-d H:i:s', time() + HOUR_IN_SECONDS),
        ),
        true
    );
    drthai_c1_assert(!is_wp_error($schedule_result) && 'draft' === get_post_status($draft_id), 'unreviewed Draft cannot Schedule through Post API');

    wp_update_post(array('ID' => $draft_id, 'post_status' => 'pending'));
    wp_update_post(array('ID' => $draft_id, 'post_status' => 'publish'));
    drthai_c1_assert('pending' === get_post_status($draft_id), 'unreviewed Pending Post cannot Publish');
    wp_update_post(
        array(
            'ID'            => $draft_id,
            'post_status'   => 'future',
            'post_date'     => gmdate('Y-m-d H:i:s', time() + HOUR_IN_SECONDS),
            'post_date_gmt' => gmdate('Y-m-d H:i:s', time() + HOUR_IN_SECONDS),
        )
    );
    drthai_c1_assert('pending' === get_post_status($draft_id), 'unreviewed Pending Post cannot Schedule');

    $rest_request = new WP_REST_Request('POST', '/wp/v2/posts/' . $draft_id);
    $rest_request->set_param('id', $draft_id);
    $rest_request->set_param('status', 'publish');
    $rest_response = rest_do_request($rest_request);
    drthai_c1_assert($rest_response->is_error() && 'drthai_medical_review_required' === $rest_response->as_error()->get_error_code(), 'REST Gutenberg publication bypass is rejected');

    wp_set_current_user($author_id);
    $meta_request = new WP_REST_Request('POST', '/wp/v2/posts/' . $draft_id);
    $meta_request->set_param('id', $draft_id);
    $meta_request->set_param(
        'meta',
        array(
            DRTHAI_MEDICAL_REVIEWER_META => $author_id,
            DRTHAI_REVIEWED_AT_META      => '2020-01-01T00:00:00Z',
        )
    );
    $meta_response = rest_do_request($meta_request);
    drthai_c1_assert(403 === $meta_response->get_status(), 'REST reviewer metadata forgery is rejected');
    drthai_c1_assert(! metadata_exists('post', $draft_id, DRTHAI_MEDICAL_REVIEWER_META), 'unauthorized REST reviewer forgery is not stored');
    drthai_c1_assert(! metadata_exists('post', $draft_id, DRTHAI_REVIEWED_AT_META), 'unauthorized REST timestamp forgery is not stored');

    wp_set_current_user($admin_id);
    $valid_nonce = wp_create_nonce('drthai_medical_review_' . $draft_id);
    drthai_c1_assert(true === drthai_health_validate_medical_review_request($draft_id, $valid_nonce), 'authorized review request validates');
    drthai_c1_assert(true === drthai_health_mark_post_medically_reviewed($draft_id), 'authorized reviewer marks Post reviewed');
    $first_timestamp = get_post_meta($draft_id, DRTHAI_REVIEWED_AT_META, true);
    drthai_c1_assert($admin_id === (int) get_post_meta($draft_id, DRTHAI_MEDICAL_REVIEWER_META, true), 'reviewer equals current authorized user');
    drthai_c1_assert((bool) preg_match('/Z$/', $first_timestamp), 'review timestamp is server-generated UTC');
    drthai_c1_assert(drthai_health_post_has_valid_medical_review($draft_id), 'review metadata is valid');

    drthai_health_set_user_review_capability($editor_id, true);
    wp_set_current_user($editor_id);
    drthai_c1_assert(true === drthai_health_mark_post_medically_reviewed($draft_id), 'authorized re-review succeeds');
    $second_timestamp = get_post_meta($draft_id, DRTHAI_REVIEWED_AT_META, true);
    drthai_c1_assert($editor_id === (int) get_post_meta($draft_id, DRTHAI_MEDICAL_REVIEWER_META, true), 're-review records current reviewer');
    drthai_c1_assert($first_timestamp !== $second_timestamp, 're-review replaces server timestamp');

    wp_set_current_user($admin_id);
    wp_update_post(
        array(
            'ID'            => $draft_id,
            'post_status'   => 'publish',
            'post_date'     => current_time('mysql'),
            'post_date_gmt' => current_time('mysql', true),
        )
    );
    drthai_c1_assert('publish' === get_post_status($draft_id), 'reviewed Draft or Pending Post can Publish');

    $scheduled_id = drthai_c1_create_post($admin_id);
    drthai_c1_assert(true === drthai_health_mark_post_medically_reviewed($scheduled_id), 'authorized reviewer marks scheduled Post reviewed');
    wp_update_post(
        array(
            'ID'            => $scheduled_id,
            'post_status'   => 'future',
            'edit_date'     => true,
            'post_date'     => wp_date('Y-m-d H:i:s', time() + DAY_IN_SECONDS, wp_timezone()),
            'post_date_gmt' => gmdate('Y-m-d H:i:s', time() + DAY_IN_SECONDS),
        )
    );
    $scheduled_status = get_post_status($scheduled_id);
    $scheduled_post = get_post($scheduled_id);
    drthai_c1_assert(
        'future' === $scheduled_status,
        "reviewed Post can Schedule (actual: {$scheduled_status}; local: {$scheduled_post->post_date}; UTC: {$scheduled_post->post_date_gmt})"
    );

    $single_template = file_get_contents('/var/www/html/wp-content/themes/drthai-health/templates/single.html');
    $disclaimer = 'Nội dung được cung cấp nhằm mục đích thông tin sức khỏe chung và không thay thế việc khám, chẩn đoán hoặc điều trị trực tiếp bởi nhân viên y tế phù hợp.';
    drthai_c1_assert(1 === substr_count($single_template, $disclaimer), 'Single template contains one automatic Medical Disclaimer');
    foreach (array('archive.html', 'home.html', 'search.html') as $template) {
        $template_content = file_get_contents('/var/www/html/wp-content/themes/drthai-health/templates/' . $template);
        drthai_c1_assert(false === strpos($template_content, $disclaimer), "{$template} excludes Medical Disclaimer");
    }

    drthai_c1_assert('publish' === $legacy_status && 'publish' === get_post_status($legacy_post_id), 'legacy published Post remains published');
    drthai_c1_assert($legacy_reviewer === get_post_meta($legacy_post_id, DRTHAI_MEDICAL_REVIEWER_META, true), 'legacy reviewer metadata is not fabricated');
    drthai_c1_assert($legacy_reviewed_at === get_post_meta($legacy_post_id, DRTHAI_REVIEWED_AT_META, true), 'legacy reviewed timestamp is not fabricated');
    drthai_c1_assert(null === get_post_status_object('medical_review'), 'no custom Medical Review post status exists');
    drthai_c1_assert(false === post_type_exists('drthai_article'), 'no custom article Post Type exists');
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    $exit_code = 1;
} finally {
    wp_set_current_user(0);
    foreach (array_reverse($created_posts) as $post_id) {
        wp_delete_post($post_id, true);
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
drthai_c1_assert($baseline_posts === $final_posts, 'existing Post and Page inventory restored exactly');
foreach ($baseline_comments as $post_id => $comment_status) {
    drthai_c1_assert($comment_status === get_post_field('comment_status', $post_id), "existing comment status preserved for {$post_id}");
}

if (!empty($exit_code)) {
    exit($exit_code);
}

echo "C1_TESTS_RUN={$tests_run}\n";
echo "C1_TEST_STATUS=PASS\n";
