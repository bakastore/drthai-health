<?php
/**
 * DrThai Health theme functions.
 *
 * @package DrThai_Health
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/inc/theme-setup.php';
require_once __DIR__ . '/inc/editorial-workflow.php';
require_once __DIR__ . '/inc/editorial-admin.php';

/**
 * Register private callback requests in the dashboard.
 */
function drthai_health_register_callback_post_type() {
	register_post_type(
		'drthai_callback',
		array(
			'labels' => array(
				'name'          => __( 'Yêu cầu đặt lịch', 'drthai-health' ),
				'singular_name' => __( 'Yêu cầu đặt lịch', 'drthai-health' ),
				'menu_name'     => __( 'Yêu cầu đặt lịch', 'drthai-health' ),
				'all_items'     => __( 'Tất cả yêu cầu', 'drthai-health' ),
				'edit_item'     => __( 'Xem yêu cầu', 'drthai-health' ),
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => false,
			'menu_icon'           => 'dashicons-calendar-alt',
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
			'map_meta_cap' => false,
			'has_archive'  => false,
			'rewrite'      => false,
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
		'cb'             => $columns['cb'],
		'title'          => __( 'Mã yêu cầu', 'drthai-health' ),
		'contact_name'   => __( 'Họ và tên', 'drthai-health' ),
		'phone'          => __( 'Số điện thoại', 'drthai-health' ),
		'service'        => __( 'Dịch vụ', 'drthai-health' ),
		'preferred_date' => __( 'Ngày mong muốn', 'drthai-health' ),
		'preferred_time' => __( 'Khung giờ', 'drthai-health' ),
		'date'           => __( 'Ngày gửi', 'drthai-health' ),
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
	$value = null;

	switch ( $column ) {
		case 'contact_name':
			$value = get_post_meta( $post_id, '_drthai_name', true );
			break;

		case 'phone':
			$value = get_post_meta( $post_id, '_drthai_phone', true );
			break;

		case 'service':
			$value = get_post_meta( $post_id, '_drthai_service', true );
			break;

		case 'preferred_date':
			$value = get_post_meta( $post_id, '_drthai_preferred_date', true );
			break;

		case 'preferred_time':
			$value = get_post_meta( $post_id, '_drthai_preferred_time', true );

			if ( '' === $value ) {
				$value = get_post_meta( $post_id, '_drthai_contact_time', true );
			}
			break;
	}

	if ( null !== $value ) {
		echo esc_html( '' !== $value ? $value : '—' );
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
	$preferred_time = get_post_meta( $post->ID, '_drthai_preferred_time', true );

	if ( '' === $preferred_time ) {
		$preferred_time = get_post_meta( $post->ID, '_drthai_contact_time', true );
	}

	$fields = array(
		__( 'Họ và tên', 'drthai-health' )          => get_post_meta( $post->ID, '_drthai_name', true ),
		__( 'Số điện thoại', 'drthai-health' )       => get_post_meta( $post->ID, '_drthai_phone', true ),
		__( 'Email', 'drthai-health' )               => get_post_meta( $post->ID, '_drthai_email', true ),
		__( 'Dịch vụ', 'drthai-health' )             => get_post_meta( $post->ID, '_drthai_service', true ),
		__( 'Ngày mong muốn', 'drthai-health' )      => get_post_meta( $post->ID, '_drthai_preferred_date', true ),
		__( 'Khung giờ mong muốn', 'drthai-health' ) => $preferred_time,
		__( 'Ghi chú liên hệ', 'drthai-health' )     => get_post_meta( $post->ID, '_drthai_note', true ),
		__( 'Thời điểm gửi', 'drthai-health' )       => get_post_meta( $post->ID, '_drthai_submitted_at', true ),
		__( 'Thời điểm đồng ý', 'drthai-health' )    => get_post_meta( $post->ID, '_drthai_consent_at', true ),
		__( 'Trang gửi yêu cầu', 'drthai-health' )   => get_post_meta( $post->ID, '_drthai_source_url', true ),
	);

	echo '<table class="widefat striped"><tbody>';

	foreach ( $fields as $label => $value ) {
		echo '<tr><th style="width:190px">' . esc_html( $label ) . '</th><td>' . esc_html( '' !== $value ? $value : '—' ) . '</td></tr>';
	}

	echo '</tbody></table>';
}
/**
 * Return supported booking services.
 *
 * @return array
 */
function drthai_health_booking_services() {
	return array(
		'kham-tieu-hoa'         => __( 'Khám tiêu hóa', 'drthai-health' ),
		'da-day-thuc-quan'      => __( 'Tư vấn bệnh lý dạ dày – thực quản', 'drthai-health' ),
		'dai-truc-trang'        => __( 'Tư vấn đại trực tràng', 'drthai-health' ),
		'hau-mon-truc-trang'    => __( 'Tư vấn hậu môn – trực tràng', 'drthai-health' ),
		'noi-soi-tieu-hoa'      => __( 'Nội soi tiêu hóa', 'drthai-health' ),
		'tu-van-sau-phau-thuat' => __( 'Tư vấn sau phẫu thuật', 'drthai-health' ),
		'dich-vu-khac'          => __( 'Dịch vụ khác', 'drthai-health' ),
	);
}

/**
 * Return supported preferred time slots.
 *
 * @return array
 */
function drthai_health_booking_time_slots() {
	return array(
		'any'       => __( 'Bất kỳ thời gian phù hợp', 'drthai-health' ),
		'morning'   => __( 'Buổi sáng', 'drthai-health' ),
		'afternoon' => __( 'Buổi chiều', 'drthai-health' ),
		'evening'   => __( 'Buổi tối', 'drthai-health' ),
	);
}

/**
 * Output the callback form shortcode.
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function drthai_health_callback_form_shortcode( $atts ) {
	$atts       = shortcode_atts( array( 'compact' => '0' ), $atts, 'drthai_callback_form' );
	$compact    = '1' === (string) $atts['compact'];
	$status     = isset( $_GET['drthai_callback'] ) ? sanitize_key( wp_unslash( $_GET['drthai_callback'] ) ) : '';
	$services   = drthai_health_booking_services();
	$time_slots = drthai_health_booking_time_slots();
	$today      = wp_date( 'Y-m-d' );

	ob_start();
	?>
	<div class="drthai-form-wrap<?php echo $compact ? ' drthai-form-wrap--compact' : ''; ?>">
		<div class="drthai-form-intro">
			<h2>Gửi yêu cầu đặt lịch khám</h2>
			<p>Đây là yêu cầu liên hệ, chưa phải lịch hẹn được xác nhận.<br>Nhân viên hỗ trợ sẽ gọi lại để thống nhất thời gian và địa điểm.</p>
		</div>

		<?php if ( 'success' === $status ) : ?>
			<div class="drthai-form-alert drthai-form-alert--success" role="status">Đã ghi nhận yêu cầu đặt lịch. Nhân viên hỗ trợ sẽ liên hệ để xác nhận thời gian và địa điểm phù hợp.</div>
		<?php elseif ( 'error' === $status ) : ?>
			<div class="drthai-form-alert drthai-form-alert--error" role="alert">Chưa thể ghi nhận yêu cầu. Vui lòng kiểm tra các trường bắt buộc và thử lại.</div>
		<?php elseif ( 'rate' === $status ) : ?>
			<div class="drthai-form-alert drthai-form-alert--error" role="alert">Yêu cầu đã được gửi gần đây. Vui lòng chờ ít phút hoặc gọi trực tiếp 0977 703 663.</div>
		<?php endif; ?>

		<form class="drthai-callback-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
			<?php wp_nonce_field( 'drthai_callback_submit', 'drthai_callback_nonce' ); ?>
			<input type="hidden" name="action" value="drthai_callback_submit">
			<input type="hidden" name="redirect_to" value="<?php echo esc_url( get_permalink() ); ?>">

			<div class="drthai-honeypot" aria-hidden="true">
				<label>Không điền trường này<input type="text" name="company_website" tabindex="-1" autocomplete="off"></label>
			</div>

			<div class="drthai-form-grid">
				<p class="drthai-field">
					<label for="drthai-name">Họ và tên <span aria-hidden="true">*</span></label>
					<input id="drthai-name" name="contact_name" type="text" maxlength="80" autocomplete="name" required>
				</p>

				<p class="drthai-field">
					<label for="drthai-phone">Số điện thoại <span aria-hidden="true">*</span></label>
					<input id="drthai-phone" name="contact_phone" type="tel" maxlength="20" inputmode="tel" autocomplete="tel" pattern="[0-9+(). -]{9,20}" required>
				</p>

				<p class="drthai-field">
					<label for="drthai-service">Dịch vụ quan tâm <span aria-hidden="true">*</span></label>
					<select id="drthai-service" name="booking_service" required>
						<option value="">Chọn dịch vụ</option>
						<?php foreach ( $services as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</p>

				<p class="drthai-field">
					<label for="drthai-date">Ngày mong muốn <span aria-hidden="true">*</span></label>
					<input id="drthai-date" name="preferred_date" type="date" min="<?php echo esc_attr( $today ); ?>" required>
				</p>

				<p class="drthai-field">
					<label for="drthai-time">Khung giờ mong muốn</label>
					<select id="drthai-time" name="preferred_time">
						<?php foreach ( $time_slots as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</p>

				<p class="drthai-field">
					<label for="drthai-email">Email <small>(không bắt buộc)</small></label>
					<input id="drthai-email" name="contact_email" type="email" maxlength="120" autocomplete="email">
				</p>

				<p class="drthai-field drthai-field--full">
					<label for="drthai-note">Ghi chú liên hệ <small>(không bắt buộc)</small></label>
					<textarea id="drthai-note" name="contact_note" maxlength="300" rows="4"></textarea>
				</p>
			</div>

			<p class="drthai-consent">
				<label><input type="checkbox" name="contact_consent" value="1" required> Tôi đồng ý để DrThai sử dụng thông tin trên nhằm liên hệ lại theo <a href="/chinh-sach-quyen-rieng-tu/">Chính sách quyền riêng tư</a>.</label>
			</p>

			<p class="drthai-form-note">Không nhập triệu chứng chi tiết, bệnh án, kết quả xét nghiệm, ảnh nội soi hoặc dữ liệu sức khỏe nhạy cảm vào biểu mẫu này.</p>

			<button class="drthai-submit" type="submit">Gửi yêu cầu đặt lịch</button>
		</form>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'drthai_callback_form', 'drthai_health_callback_form_shortcode' );

/**
 * Render the callback shortcode when its block is nested inside a pattern.
 *
 * @param string $block_content Rendered shortcode block content.
 * @return string
 */
function drthai_health_render_callback_shortcode_block( $block_content ) {
	if ( false === strpos( $block_content, '[drthai_callback_form' ) ) {
		return $block_content;
	}

	return do_shortcode( $block_content );
}
add_filter( 'render_block_core/shortcode', 'drthai_health_render_callback_shortcode_block' );

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
	$get_post_value = static function ( $key ) {
		return isset( $_POST[ $key ] ) && is_string( $_POST[ $key ] )
			? wp_unslash( $_POST[ $key ] )
			: '';
	};

	$nonce = sanitize_text_field( $get_post_value( 'drthai_callback_nonce' ) );
	if ( ! wp_verify_nonce( $nonce, 'drthai_callback_submit' ) ) {
		drthai_health_callback_redirect( 'error' );
	}

	if ( '' !== trim( $get_post_value( 'company_website' ) ) ) {
		drthai_health_callback_redirect( 'success' );
	}

	$services   = drthai_health_booking_services();
	$time_slots = drthai_health_booking_time_slots();

	$name               = sanitize_text_field( $get_post_value( 'contact_name' ) );
	$phone              = sanitize_text_field( $get_post_value( 'contact_phone' ) );
	$email_input        = trim( $get_post_value( 'contact_email' ) );
	$email              = sanitize_email( $email_input );
	$service_key        = sanitize_key( $get_post_value( 'booking_service' ) );
	$preferred_date     = sanitize_text_field( $get_post_value( 'preferred_date' ) );
	$preferred_time_key = sanitize_key( $get_post_value( 'preferred_time' ) );
	$note               = sanitize_textarea_field( $get_post_value( 'contact_note' ) );
	$consent            = '1' === $get_post_value( 'contact_consent' );

	if ( '' === $preferred_time_key ) {
		$preferred_time_key = 'any';
	}

	$digits      = preg_replace( '/\D+/', '', $phone );
	$name_length = function_exists( 'mb_strlen' ) ? mb_strlen( $name ) : strlen( $name );
	$note_length = function_exists( 'mb_strlen' ) ? mb_strlen( $note ) : strlen( $note );

	$date_parts = explode( '-', $preferred_date );
	$date_valid = 3 === count( $date_parts )
		&& ctype_digit( $date_parts[0] )
		&& ctype_digit( $date_parts[1] )
		&& ctype_digit( $date_parts[2] )
		&& checkdate( (int) $date_parts[1], (int) $date_parts[2], (int) $date_parts[0] );

	$today = wp_date( 'Y-m-d' );

	if (
		'' === $name
		|| $name_length > 80
		|| strlen( $digits ) < 9
		|| strlen( $digits ) > 12
		|| ! isset( $services[ $service_key ] )
		|| ! isset( $time_slots[ $preferred_time_key ] )
		|| ! $date_valid
		|| $preferred_date < $today
		|| ( '' !== $email_input && ! is_email( $email_input ) )
		|| $note_length > 300
		|| ! $consent
	) {
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

	$service_label = $services[ $service_key ];
	$time_label    = $time_slots[ $preferred_time_key ];
	$source_url    = wp_validate_redirect(
		esc_url_raw( $get_post_value( 'redirect_to' ) ),
		home_url( '/lien-he/' )
	);

	update_post_meta( $request_id, '_drthai_name', $name );
	update_post_meta( $request_id, '_drthai_phone', $phone );
	update_post_meta( $request_id, '_drthai_email', $email );
	update_post_meta( $request_id, '_drthai_service', $service_label );
	update_post_meta( $request_id, '_drthai_preferred_date', $preferred_date );
	update_post_meta( $request_id, '_drthai_preferred_time', $time_label );
	update_post_meta( $request_id, '_drthai_contact_time', $time_label );
	update_post_meta( $request_id, '_drthai_note', $note );
	update_post_meta( $request_id, '_drthai_submitted_at', wp_date( 'd/m/Y H:i:s' ) );
	update_post_meta( $request_id, '_drthai_consent_at', wp_date( 'Y-m-d H:i:s' ) );
	update_post_meta( $request_id, '_drthai_source_url', $source_url );
	update_post_meta( $request_id, '_drthai_consent', '1' );

	$subject = sprintf( '[DrThai] Yêu cầu đặt lịch %s', get_the_title( $request_id ) );

	$message = sprintf(
		"Họ tên: %s\nSố điện thoại: %s\nDịch vụ: %s\nNgày mong muốn: %s\nKhung giờ: %s\nEmail: %s\nGhi chú: %s\nNguồn: %s\n",
		$name,
		$phone,
		$service_label,
		$preferred_date,
		$time_label,
		$email ? $email : '—',
		$note ? $note : '—',
		$source_url
	);

	wp_mail( get_option( 'admin_email' ), $subject, $message );

	drthai_health_callback_redirect( 'success' );
}
add_action( 'admin_post_nopriv_drthai_callback_submit', 'drthai_health_handle_callback_form' );
add_action( 'admin_post_drthai_callback_submit', 'drthai_health_handle_callback_form' );

/**
 * End of the protected Booking implementation.
 */
