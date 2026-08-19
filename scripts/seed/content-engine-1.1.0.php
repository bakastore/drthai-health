<?php
/**
 * Seed the semantic content structure required by Content Engine 1.1.0.
 *
 * Development only. Invoke explicitly inside the wp-env CLI container:
 * php /var/www/html/wp-content/themes/drthai-health/scripts/seed/content-engine-1.1.0.php
 *
 * Existing pages and terms are never updated or deleted.
 */

declare(strict_types=1);

define('DISABLE_WP_CRON', true);

require '/var/www/html/wp-load.php';

$pages = array(
    'trang-chu'                   => 'Trang chủ',
    'gioi-thieu'                  => 'Giới thiệu bác sĩ',
    'chuyen-mon'                  => 'Chuyên môn và dịch vụ',
    'tin-tuc'                     => 'Tin tức',
    'dat-lich'                    => 'Đặt lịch khám',
    'lien-he'                     => 'Liên hệ',
    'chinh-sach-quyen-rieng-tu'   => 'Chính sách quyền riêng tư',
    'mien-tru-trach-nhiem-y-khoa' => 'Miễn trừ trách nhiệm y khoa',
);

$categories = array(
    'da-day-thuc-quan'       => 'Dạ dày – Thực quản',
    'dai-trang-truc-trang'   => 'Đại tràng – Trực tràng',
    'gan-mat-tuy'             => 'Gan – Mật – Tụy',
    'noi-soi-tieu-hoa'        => 'Nội soi tiêu hóa',
    'dinh-duong-tieu-hoa'     => 'Dinh dưỡng tiêu hóa',
    'phong-benh-loi-song'     => 'Phòng bệnh và lối sống',
    'tin-hoat-dong'           => 'Tin hoạt động',
);

$failures = 0;

foreach ($pages as $slug => $title) {
    $existing = get_page_by_path($slug, OBJECT, 'page');
    if ($existing instanceof WP_Post) {
        echo "EXISTS PAGE {$slug}\n";
        continue;
    }

    $page_id = wp_insert_post(
        array(
            'post_type'   => 'page',
            'post_status' => 'publish',
            'post_title'  => $title,
            'post_name'   => $slug,
        ),
        true
    );

    if (is_wp_error($page_id)) {
        echo "SKIP PAGE {$slug} {$page_id->get_error_code()}\n";
        ++$failures;
        continue;
    }

    echo "CREATE PAGE {$slug}\n";
}

foreach ($categories as $slug => $name) {
    $existing = get_term_by('slug', $slug, 'category');
    if ($existing instanceof WP_Term) {
        echo "EXISTS CATEGORY {$slug}\n";
        continue;
    }

    $term = wp_insert_term($name, 'category', array('slug' => $slug));
    if (is_wp_error($term)) {
        echo "SKIP CATEGORY {$slug} {$term->get_error_code()}\n";
        ++$failures;
        continue;
    }

    echo "CREATE CATEGORY {$slug}\n";
}

if (0 !== $failures) {
    fwrite(STDERR, "SEED_STATUS=FAIL\n");
    exit(1);
}

echo "SEED_STATUS=PASS\n";
