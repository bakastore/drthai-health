<?php
/**
 * DrThai Health theme functions.
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

/**
 * Register private callback requests in the dashboard.
 */
function drthai_health_register_callback_post_type() {
	register_post_type(
		'drthai_callback',
		array(
			'labels' => array(
				'name'          => __( 'Yêu cầu gọi lại', 'drthai-health' ),
				'singular_name' => __( 'Yêu cầu gọi lại', 'drthai-health' ),
				'menu_name'     => __( 'Yêu cầu gọi lại', 'drthai-health' ),
				'all_items'     => __( 'Tất cả yêu cầu', 'drthai-health' ),
				'edit_item'     => __( 'Xem yêu cầu', 'drthai-health' ),
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => false,
			'menu_icon'           => 'dashicons-phone',
			'supports'            => array( 'title' ),
			'capabilities'        => array(
				'edit_post'          => 'manage_options',
				'read_post'          => 'manage_options',
				'delete_post'        => 'manage_options',
				'edit_posts'         => 'manage_options',
				'edit_others_posts'  => 'manage_options',
				'delete_posts'       => 'manage_options',
				'publish_posts'      => 'manage_options',
				'read_private_posts' => 'manage_options',
				'create_posts'       => 'do_not_allow',
			),
			'map_meta_cap'        => false,
			'has_archive'         => false,
			'rewrite'             => false,
		)
	);
}
add_action( 'init', 'drthai_health_register_callback_post_type' );

/**
 * Add readable columns to callback requests.
 *
 * @param array $columns Existing columns.
 * @return array
 */
function drthai_health_callback_columns( $columns ) {
	return array(
		'cb'           => $columns['cb'],
		'title'        => __( 'Mã yêu cầu', 'drthai-health' ),
		'contact_name' => __( 'Họ và tên', 'drthai-health' ),
		'phone'        => __( 'Số điện thoại', 'drthai-health' ),
		'contact_time' => __( 'Thời gian mong muốn', 'drthai-health' ),
		'date'         => __( 'Ngày gửi', 'drthai-health' ),
	);
}
add_filter( 'manage_drthai_callback_posts_columns', 'drthai_health_callback_columns' );

/**
 * Render callback request columns.
 *
 * @param string $column  Column name.
 * @param int    $post_id Request ID.
 */
function drthai_health_callback_column_content( $column, $post_id ) {
	if ( 'contact_name' === $column ) {
		echo esc_html( get_post_meta( $post_id, '_drthai_name', true ) );
	}
	if ( 'phone' === $column ) {
		echo esc_html( get_post_meta( $post_id, '_drthai_phone', true ) );
	}
	if ( 'contact_time' === $column ) {
		echo esc_html( get_post_meta( $post_id, '_drthai_contact_time', true ) );
	}
}
add_action( 'manage_drthai_callback_posts_custom_column', 'drthai_health_callback_column_content', 10, 2 );

/**
 * Show request details in the editor.
 */
function drthai_health_callback_meta_box() {
	add_meta_box(
		'drthai-callback-details',
		__( 'Thông tin liên hệ', 'drthai-health' ),
		'drthai_health_callback_meta_box_content',
		'drthai_callback',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'drthai_health_callback_meta_box' );

/**
 * Render read-only callback details.
 *
 * @param WP_Post $post Request post.
 */
function drthai_health_callback_meta_box_content( $post ) {
	$fields = array(
		'_drthai_name'         => __( 'Họ và tên', 'drthai-health' ),
		'_drthai_phone'        => __( 'Số điện thoại', 'drthai-health' ),
		'_drthai_email'        => __( 'Email', 'drthai-health' ),
		'_drthai_contact_time' => __( 'Thời gian mong muốn', 'drthai-health' ),
		'_drthai_submitted_at' => __( 'Thời điểm gửi', 'drthai-health' ),
	);

	echo '<table class="widefat striped"><tbody>';
	foreach ( $fields as $key => $label ) {
		$value = get_post_meta( $post->ID, $key, true );
		echo '<tr><th style="width:190px">' . esc_html( $label ) . '</th><td>' . esc_html( $value ? $value : '—' ) . '</td></tr>';
	}
	echo '</tbody></table>';
}

/**
 * Output the callback form shortcode.
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function drthai_health_callback_form_shortcode( $atts ) {
	$atts = shortcode_atts( array( 'compact' => '0' ), $atts, 'drthai_callback_form' );
	$compact = '1' === (string) $atts['compact'];
	$status  = isset( $_GET['drthai_callback'] ) ? sanitize_key( wp_unslash( $_GET['drthai_callback'] ) ) : '';

	ob_start();
	?>
	<div class="drthai-form-wrap<?php echo $compact ? ' drthai-form-wrap--compact' : ''; ?>">
		<?php if ( 'success' === $status ) : ?>
			<div class="drthai-form-alert drthai-form-alert--success" role="status">Đã ghi nhận yêu cầu. Bác sĩ hoặc người hỗ trợ sẽ liên hệ lại theo thông tin anh/chị cung cấp.</div>
		<?php elseif ( 'error' === $status ) : ?>
			<div class="drthai-form-alert drthai-form-alert--error" role="alert">Chưa thể ghi nhận yêu cầu. Vui lòng kiểm tra họ tên, số điện thoại và ô đồng ý.</div>
		<?php elseif ( 'rate' === $status ) : ?>
			<div class="drthai-form-alert drthai-form-alert--error" role="alert">Yêu cầu đã được gửi gần đây. Vui lòng chờ ít phút hoặc gọi trực tiếp 0977 703 663.</div>
		<?php endif; ?>

		<form class="drthai-callback-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
			<?php wp_nonce_field( 'drthai_callback_submit', 'drthai_callback_nonce' ); ?>
			<input type="hidden" name="action" value="drthai_callback_submit">
			<input type="hidden" name="redirect_to" value="<?php echo esc_url( get_permalink() ); ?>">
			<div class="drthai-honeypot" aria-hidden="true"><label>Không điền trường này<input type="text" name="company_website" tabindex="-1" autocomplete="off"></label></div>

			<div class="drthai-form-grid">
				<p class="drthai-field">
					<label for="drthai-name">Họ và tên <span aria-hidden="true">*</span></label>
					<input id="drthai-name" name="contact_name" type="text" maxlength="80" autocomplete="name" required>
				</p>
				<p class="drthai-field">
					<label for="drthai-phone">Số điện thoại <span aria-hidden="true">*</span></label>
					<input id="drthai-phone" name="contact_phone" type="tel" maxlength="20" inputmode="tel" autocomplete="tel" pattern="[0-9+(). -]{8,20}" required>
				</p>
				<p class="drthai-field">
					<label for="drthai-email">Email <small>(không bắt buộc)</small></label>
					<input id="drthai-email" name="contact_email" type="email" maxlength="120" autocomplete="email">
				</p>
				<p class="drthai-field">
					<label for="drthai-time">Thời gian muốn nhận cuộc gọi</label>
					<select id="drthai-time" name="contact_time">
						<option value="Bất kỳ thời gian phù hợp">Bất kỳ thời gian phù hợp</option>
						<option value="Buổi sáng 08:00–11:30">Buổi sáng 08:00–11:30</option>
						<option value="Buổi chiều 13:30–17:30">Buổi chiều 13:30–17:30</option>
						<option value="Buổi tối 18:00–20:00">Buổi tối 18:00–20:00</option>
					</select>
				</p>
			</div>

			<p class="drthai-consent">
				<label><input type="checkbox" name="contact_consent" value="1" required> Tôi đồng ý để DrThai sử dụng thông tin trên nhằm liên hệ lại theo <a href="/chinh-sach-quyen-rieng-tu/">Chính sách quyền riêng tư</a>.</label>
			</p>
			<p class="drthai-form-note">Không nhập triệu chứng, bệnh án hoặc dữ liệu sức khỏe vào biểu mẫu này. Trường hợp khẩn cấp, hãy gọi 115 hoặc đến cơ sở y tế gần nhất.</p>
			<button class="drthai-submit" type="submit">Gửi yêu cầu gọi lại</button>
		</form>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'drthai_callback_form', 'drthai_health_callback_form_shortcode' );

/**
 * Redirect back to the source page with a form status.
 *
 * @param string $status Status key.
 */
function drthai_health_callback_redirect( $status ) {
	$fallback = home_url( '/lien-he/' );
	$target   = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : $fallback;
	$target   = wp_validate_redirect( $target, $fallback );
	wp_safe_redirect( add_query_arg( 'drthai_callback', $status, $target ) );
	exit;
}

/**
 * Validate and save callback requests.
 */
function drthai_health_handle_callback_form() {
	$nonce = isset( $_POST['drthai_callback_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['drthai_callback_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'drthai_callback_submit' ) ) {
		drthai_health_callback_redirect( 'error' );
	}

	if ( ! empty( $_POST['company_website'] ) ) {
		drthai_health_callback_redirect( 'success' );
	}

	$name    = isset( $_POST['contact_name'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_name'] ) ) : '';
	$phone   = isset( $_POST['contact_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_phone'] ) ) : '';
	$email   = isset( $_POST['contact_email'] ) ? sanitize_email( wp_unslash( $_POST['contact_email'] ) ) : '';
	$time    = isset( $_POST['contact_time'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_time'] ) ) : '';
	$consent = isset( $_POST['contact_consent'] ) && '1' === (string) $_POST['contact_consent'];
	$digits  = preg_replace( '/\D+/', '', $phone );

	if ( '' === $name || strlen( $name ) > 80 || strlen( $digits ) < 8 || strlen( $digits ) > 15 || ! $consent ) {
		drthai_health_callback_redirect( 'error' );
	}

	$rate_key = 'drthai_callback_' . md5( $digits . '|' . wp_salt( 'nonce' ) );
	if ( get_transient( $rate_key ) ) {
		drthai_health_callback_redirect( 'rate' );
	}
	set_transient( $rate_key, 1, 5 * MINUTE_IN_SECONDS );

	$request_id = wp_insert_post(
		array(
			'post_type'   => 'drthai_callback',
			'post_status' => 'private',
			'post_title'  => sprintf( 'DT-%s-%04d', wp_date( 'Ymd-His' ), wp_rand( 0, 9999 ) ),
		),
		true
	);

	if ( is_wp_error( $request_id ) ) {
		delete_transient( $rate_key );
		drthai_health_callback_redirect( 'error' );
	}

	update_post_meta( $request_id, '_drthai_name', $name );
	update_post_meta( $request_id, '_drthai_phone', $phone );
	update_post_meta( $request_id, '_drthai_email', $email );
	update_post_meta( $request_id, '_drthai_contact_time', $time );
	update_post_meta( $request_id, '_drthai_submitted_at', wp_date( 'd/m/Y H:i:s' ) );
	update_post_meta( $request_id, '_drthai_consent', '1' );

	$subject = sprintf( '[DrThai] Yêu cầu gọi lại %s', get_the_title( $request_id ) );
	$message = "Họ tên: {$name}\nSố điện thoại: {$phone}\nEmail: {$email}\nThời gian: {$time}\n";
	wp_mail( get_option( 'admin_email' ), $subject, $message );

	drthai_health_callback_redirect( 'success' );
}
add_action( 'admin_post_nopriv_drthai_callback_submit', 'drthai_health_handle_callback_form' );
add_action( 'admin_post_drthai_callback_submit', 'drthai_health_handle_callback_form' );

/**
 * Render the news hub with native WordPress categories and tags.
 *
 * @return string
 */
function drthai_health_news_hub_shortcode() {
	$categories = get_categories(
		array(
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
			'exclude'    => array( (int) get_option( 'default_category' ) ),
		)
	);

	ob_start();
	?>
	<div class="drthai-news-hub">
		<div class="drthai-news-toolbar">
			<nav class="drthai-category-nav" aria-label="Chuyên mục tin tức">
				<?php foreach ( $categories as $category ) : ?>
					<a href="<?php echo esc_url( get_category_link( $category->term_id ) ); ?>"><?php echo esc_html( $category->name ); ?> <span><?php echo esc_html( $category->count ); ?></span></a>
				<?php endforeach; ?>
			</nav>
			<?php get_search_form(); ?>
		</div>

		<?php if ( empty( $categories ) ) : ?>
			<div class="drthai-empty-state"><h2>Chưa có chuyên mục</h2><p>Vào Bài viết → Chuyên mục để tạo cấu trúc nội dung đầu tiên.</p></div>
		<?php endif; ?>

		<?php foreach ( $categories as $category ) : ?>
			<?php
			$posts = get_posts(
				array(
					'category'       => $category->term_id,
					'numberposts'    => 3,
					'post_status'    => 'publish',
					'suppress_filters' => false,
				)
			);
			?>
			<section class="drthai-category-section" aria-labelledby="drthai-category-<?php echo esc_attr( $category->term_id ); ?>">
				<div class="drthai-category-heading">
					<div><p class="drthai-kicker">CHUYÊN MỤC</p><h2 id="drthai-category-<?php echo esc_attr( $category->term_id ); ?>"><?php echo esc_html( $category->name ); ?></h2></div>
					<a href="<?php echo esc_url( get_category_link( $category->term_id ) ); ?>">Xem tất cả →</a>
				</div>
				<?php if ( $posts ) : ?>
					<div class="drthai-news-grid">
						<?php foreach ( $posts as $post ) : ?>
							<article class="drthai-news-card">
								<a class="drthai-news-card__image" href="<?php echo esc_url( get_permalink( $post ) ); ?>" aria-label="<?php echo esc_attr( get_the_title( $post ) ); ?>">
									<?php if ( has_post_thumbnail( $post ) ) : ?>
										<?php echo get_the_post_thumbnail( $post, 'medium_large', array( 'loading' => 'lazy' ) ); ?>
									<?php else : ?>
										<span class="drthai-news-card__placeholder" aria-hidden="true">✦</span>
									<?php endif; ?>
								</a>
								<div class="drthai-news-card__body">
									<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C, $post ) ); ?>"><?php echo esc_html( get_the_date( 'd/m/Y', $post ) ); ?></time>
									<h3><a href="<?php echo esc_url( get_permalink( $post ) ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a></h3>
									<p><?php echo esc_html( wp_trim_words( get_the_excerpt( $post ), 20 ) ); ?></p>
									<a class="drthai-read-more" href="<?php echo esc_url( get_permalink( $post ) ); ?>">Đọc bài viết →</a>
								</div>
							</article>
						<?php endforeach; ?>
					</div>
				<?php else : ?>
					<div class="drthai-empty-state"><p>Chưa có bài viết trong chuyên mục này. Khi anh xuất bản bài đầu tiên, nội dung sẽ tự hiển thị tại đây.</p></div>
				<?php endif; ?>
			</section>
		<?php endforeach; ?>

		<?php $tags = get_tags( array( 'hide_empty' => true ) ); ?>
		<?php if ( $tags ) : ?>
			<section class="drthai-topic-cloud" aria-labelledby="drthai-topics-title">
				<p class="drthai-kicker">KHÁM PHÁ THEO CHỦ ĐỀ</p>
				<h2 id="drthai-topics-title">Chủ đề nổi bật</h2>
				<div>
					<?php foreach ( $tags as $tag ) : ?>
						<a href="<?php echo esc_url( get_tag_link( $tag->term_id ) ); ?>">#<?php echo esc_html( $tag->name ); ?></a>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'drthai_news_hub', 'drthai_health_news_hub_shortcode' );

/**
 * Create only missing pages and baseline news categories on theme activation.
 */
function drthai_health_activation_setup() {
	$pages = array(
		'trang-chu'                    => 'Trang chủ',
		'gioi-thieu'                   => 'Giới thiệu bác sĩ',
		'chuyen-mon'                   => 'Chuyên môn và dịch vụ',
		'tin-tuc'                      => 'Tin tức',
		'dat-lich'                     => 'Đặt lịch khám',
		'lien-he'                      => 'Liên hệ',
		'chinh-sach-quyen-rieng-tu'    => 'Chính sách quyền riêng tư',
		'mien-tru-trach-nhiem-y-khoa' => 'Miễn trừ trách nhiệm y khoa',
	);
	$ids = array();

	foreach ( $pages as $slug => $title ) {
		$page = get_page_by_path( $slug );
		if ( ! $page ) {
			$page_id = wp_insert_post(
				array(
					'post_type'   => 'page',
					'post_status' => 'publish',
					'post_title'  => $title,
					'post_name'   => $slug,
				)
			);
			if ( ! is_wp_error( $page_id ) ) {
				$ids[ $slug ] = $page_id;
			}
		} else {
			$ids[ $slug ] = $page->ID;
		}
	}

	if ( ! empty( $ids['trang-chu'] ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $ids['trang-chu'] );
	}

	$categories = array(
		'Dạ dày – Thực quản'    => 'da-day-thuc-quan',
		'Đại tràng – Trực tràng' => 'dai-trang-truc-trang',
		'Gan – Mật – Tụy'        => 'gan-mat-tuy',
		'Nội soi tiêu hóa'       => 'noi-soi-tieu-hoa',
		'Dinh dưỡng tiêu hóa'    => 'dinh-duong-tieu-hoa',
		'Phòng bệnh và lối sống' => 'phong-benh-loi-song',
	);

	foreach ( $categories as $name => $slug ) {
		if ( ! term_exists( $slug, 'category' ) ) {
			wp_insert_term( $name, 'category', array( 'slug' => $slug ) );
		}
	}

	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'drthai_health_activation_setup' );
