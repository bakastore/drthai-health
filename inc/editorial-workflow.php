<?php
/**
 * Native editorial and medical review workflow for Posts.
 *
 * @package DrThai_Health
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const DRTHAI_MEDICAL_REVIEW_CAPABILITY = 'drthai_review_medical_content';
const DRTHAI_MEDICAL_REVIEWER_META     = 'drthai_medical_reviewer';
const DRTHAI_REVIEWED_AT_META          = 'drthai_reviewed_at';

/**
 * Authorize access to protected medical review metadata.
 *
 * The fields are intentionally unavailable through REST. This callback also
 * protects normal WordPress metadata-capability checks.
 *
 * @param bool   $allowed  Existing authorization result.
 * @param string $meta_key Metadata key.
 * @param int    $post_id  Post ID.
 * @param int    $user_id  User ID.
 * @return bool
 */
function drthai_health_authorize_medical_review_meta( $allowed, $meta_key, $post_id, $user_id ) {
	unset( $allowed, $meta_key, $post_id, $user_id );

	return false;
}

/**
 * Sanitize a reviewer user ID.
 *
 * @param mixed $value Candidate value.
 * @return int
 */
function drthai_health_sanitize_reviewer_id( $value ) {
	return absint( $value );
}

/**
 * Sanitize an ISO 8601 UTC review timestamp.
 *
 * @param mixed $value Candidate value.
 * @return string
 */
function drthai_health_sanitize_reviewed_at( $value ) {
	$value = sanitize_text_field( (string) $value );

	return preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{6})?Z$/', $value ) ? $value : '';
}

/**
 * Register protected Post metadata.
 */
function drthai_health_register_editorial_meta() {
	$common = array(
		'object_subtype' => 'post',
		'single'         => true,
		'show_in_rest'   => false,
		'auth_callback'  => 'drthai_health_authorize_medical_review_meta',
	);

	register_post_meta(
		'post',
		DRTHAI_MEDICAL_REVIEWER_META,
		array_merge(
			$common,
			array(
				'type'              => 'integer',
				'default'           => 0,
				'sanitize_callback' => 'drthai_health_sanitize_reviewer_id',
			)
		)
	);

	register_post_meta(
		'post',
		DRTHAI_REVIEWED_AT_META,
		array_merge(
			$common,
			array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'drthai_health_sanitize_reviewed_at',
			)
		)
	);
}
add_action( 'init', 'drthai_health_register_editorial_meta' );

/**
 * Idempotently grant the review capability to the Administrator role.
 */
function drthai_health_ensure_administrator_review_capability() {
	$administrator = get_role( 'administrator' );
	if ( $administrator && ! $administrator->has_cap( DRTHAI_MEDICAL_REVIEW_CAPABILITY ) ) {
		$administrator->add_cap( DRTHAI_MEDICAL_REVIEW_CAPABILITY );
	}
}
add_action( 'init', 'drthai_health_ensure_administrator_review_capability', 20 );

/**
 * Grant or revoke review capability for one user without changing other caps.
 *
 * @param int  $user_id User ID.
 * @param bool $grant   Whether to grant the capability.
 * @return bool
 */
function drthai_health_set_user_review_capability( $user_id, $grant ) {
	$user = get_user_by( 'id', absint( $user_id ) );
	if ( ! $user ) {
		return false;
	}

	if ( $grant ) {
		$user->add_cap( DRTHAI_MEDICAL_REVIEW_CAPABILITY );
	} else {
		$user->remove_cap( DRTHAI_MEDICAL_REVIEW_CAPABILITY );
	}

	return true;
}

/**
 * Render the medical review capability control on a user profile.
 *
 * @param WP_User $profile_user Profile being edited.
 */
function drthai_health_render_reviewer_profile_control( $profile_user ) {
	if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_user', $profile_user->ID ) ) {
		return;
	}
	?>
	<h2><?php esc_html_e( 'DrThai Editorial Permissions', 'drthai-health' ); ?></h2>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><?php esc_html_e( 'Medical Review', 'drthai-health' ); ?></th>
			<td>
				<label for="drthai-review-medical-content">
					<input type="checkbox" id="drthai-review-medical-content" name="drthai_review_medical_content" value="1" <?php checked( $profile_user->has_cap( DRTHAI_MEDICAL_REVIEW_CAPABILITY ) ); ?> />
					<?php esc_html_e( 'Allow this user to mark Posts as medically reviewed.', 'drthai-health' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Administrators receive this capability through their role.', 'drthai-health' ); ?></p>
				<?php wp_nonce_field( 'drthai_update_review_capability_' . $profile_user->ID, 'drthai_review_capability_nonce' ); ?>
			</td>
		</tr>
	</table>
	<?php
}
add_action( 'show_user_profile', 'drthai_health_render_reviewer_profile_control' );
add_action( 'edit_user_profile', 'drthai_health_render_reviewer_profile_control' );

/**
 * Save the medical review capability from a user profile.
 *
 * @param int $user_id User ID.
 */
function drthai_health_save_reviewer_profile_control( $user_id ) {
	if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_user', $user_id ) ) {
		return;
	}

	$nonce = isset( $_POST['drthai_review_capability_nonce'] )
		? sanitize_text_field( wp_unslash( $_POST['drthai_review_capability_nonce'] ) )
		: '';
	if ( ! wp_verify_nonce( $nonce, 'drthai_update_review_capability_' . $user_id ) ) {
		return;
	}

	$grant = isset( $_POST['drthai_review_medical_content'] )
		&& '1' === sanitize_text_field( wp_unslash( $_POST['drthai_review_medical_content'] ) );
	drthai_health_set_user_review_capability( $user_id, $grant );
}
add_action( 'personal_options_update', 'drthai_health_save_reviewer_profile_control' );
add_action( 'edit_user_profile_update', 'drthai_health_save_reviewer_profile_control' );

/**
 * Determine whether a Post has valid medical review metadata.
 *
 * @param int $post_id Post ID.
 * @return bool
 */
function drthai_health_post_has_valid_medical_review( $post_id ) {
	$reviewer_id = absint( get_post_meta( $post_id, DRTHAI_MEDICAL_REVIEWER_META, true ) );
	$reviewed_at = (string) get_post_meta( $post_id, DRTHAI_REVIEWED_AT_META, true );

	return $reviewer_id > 0
		&& false !== get_user_by( 'id', $reviewer_id )
		&& '' !== $reviewed_at
		&& $reviewed_at === drthai_health_sanitize_reviewed_at( $reviewed_at );
}

/**
 * Validate a request to mark a Post medically reviewed.
 *
 * @param int    $post_id Post ID.
 * @param string $nonce   Action nonce.
 * @return true|WP_Error
 */
function drthai_health_validate_medical_review_request( $post_id, $nonce ) {
	$post_id = absint( $post_id );
	$post    = get_post( $post_id );

	if ( ! is_user_logged_in() ) {
		return new WP_Error( 'drthai_review_authentication_required', __( 'You must be signed in to perform Medical Review.', 'drthai-health' ) );
	}
	if ( ! current_user_can( DRTHAI_MEDICAL_REVIEW_CAPABILITY ) ) {
		return new WP_Error( 'drthai_review_capability_required', __( 'You are not authorized to perform Medical Review.', 'drthai-health' ) );
	}
	if ( ! $post || 'post' !== $post->post_type ) {
		return new WP_Error( 'drthai_review_invalid_post', __( 'Medical Review is available only for Posts.', 'drthai-health' ) );
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return new WP_Error( 'drthai_review_edit_required', __( 'You are not allowed to edit this Post.', 'drthai-health' ) );
	}
	if ( ! wp_verify_nonce( sanitize_text_field( $nonce ), 'drthai_medical_review_' . $post_id ) ) {
		return new WP_Error( 'drthai_review_invalid_nonce', __( 'The Medical Review request expired. Reload the Post and try again.', 'drthai-health' ) );
	}

	return true;
}

/**
 * Write server-derived medical review metadata.
 *
 * @param int $post_id Post ID.
 * @return true|WP_Error
 */
function drthai_health_mark_post_medically_reviewed( $post_id ) {
	$post_id = absint( $post_id );
	$post    = get_post( $post_id );
	$user_id = get_current_user_id();

	if ( ! $post || 'post' !== $post->post_type || ! $user_id
		|| ! current_user_can( DRTHAI_MEDICAL_REVIEW_CAPABILITY )
		|| ! current_user_can( 'edit_post', $post_id ) ) {
		return new WP_Error( 'drthai_review_invalid_context', __( 'Unable to record Medical Review for this Post.', 'drthai-health' ) );
	}

	$old_reviewer    = get_post_meta( $post_id, DRTHAI_MEDICAL_REVIEWER_META, true );
	$old_reviewed_at = get_post_meta( $post_id, DRTHAI_REVIEWED_AT_META, true );
	$reviewed_at     = ( new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) ) )->format( 'Y-m-d\TH:i:s.u\Z' );
	update_post_meta( $post_id, DRTHAI_MEDICAL_REVIEWER_META, $user_id );
	update_post_meta( $post_id, DRTHAI_REVIEWED_AT_META, $reviewed_at );

	if ( ! drthai_health_post_has_valid_medical_review( $post_id ) ) {
		if ( '' === $old_reviewer ) {
			delete_post_meta( $post_id, DRTHAI_MEDICAL_REVIEWER_META );
		} else {
			update_post_meta( $post_id, DRTHAI_MEDICAL_REVIEWER_META, $old_reviewer );
		}
		if ( '' === $old_reviewed_at ) {
			delete_post_meta( $post_id, DRTHAI_REVIEWED_AT_META );
		} else {
			update_post_meta( $post_id, DRTHAI_REVIEWED_AT_META, $old_reviewed_at );
		}
		return new WP_Error( 'drthai_review_write_failed', __( 'Medical Review could not be recorded. Please try again.', 'drthai-health' ) );
	}

	return true;
}

/**
 * Handle the Medical Review action.
 */
function drthai_health_handle_medical_review_action() {
	$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	$nonce   = isset( $_POST['drthai_medical_review_nonce'] )
		? sanitize_text_field( wp_unslash( $_POST['drthai_medical_review_nonce'] ) )
		: '';
	$result  = drthai_health_validate_medical_review_request( $post_id, $nonce );

	if ( is_wp_error( $result ) ) {
		wp_die( esc_html( $result->get_error_message() ), esc_html__( 'Medical Review blocked', 'drthai-health' ), array( 'response' => 403 ) );
	}

	$result = drthai_health_mark_post_medically_reviewed( $post_id );
	if ( is_wp_error( $result ) ) {
		wp_die( esc_html( $result->get_error_message() ), esc_html__( 'Medical Review failed', 'drthai-health' ), array( 'response' => 500 ) );
	}

	wp_safe_redirect( add_query_arg( 'drthai_reviewed', '1', get_edit_post_link( $post_id, 'url' ) ) );
	exit;
}
add_action( 'admin_post_drthai_mark_medically_reviewed', 'drthai_health_handle_medical_review_action' );

/**
 * Format a stored UTC review timestamp in site-local time.
 *
 * @param string $reviewed_at UTC timestamp.
 * @return string
 */
function drthai_health_format_reviewed_at( $reviewed_at ) {
	$timestamp = strtotime( $reviewed_at );
	if ( false === $timestamp ) {
		return '';
	}

	return wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp, wp_timezone() );
}

/**
 * Render the native Post sidebar Medical Review status.
 *
 * @param WP_Post $post Post being edited.
 */
function drthai_health_render_medical_review_meta_box( $post ) {
	$reviewer_id = absint( get_post_meta( $post->ID, DRTHAI_MEDICAL_REVIEWER_META, true ) );
	$reviewed_at = (string) get_post_meta( $post->ID, DRTHAI_REVIEWED_AT_META, true );
	$reviewer    = $reviewer_id ? get_user_by( 'id', $reviewer_id ) : false;
	$is_reviewed = drthai_health_post_has_valid_medical_review( $post->ID );
	?>
	<p><strong><?php esc_html_e( 'Status:', 'drthai-health' ); ?></strong> <?php echo $is_reviewed ? esc_html__( 'Medically reviewed', 'drthai-health' ) : esc_html__( 'Review required before publishing or scheduling', 'drthai-health' ); ?></p>
	<?php if ( $is_reviewed ) : ?>
		<p><strong><?php esc_html_e( 'Reviewer:', 'drthai-health' ); ?></strong> <?php echo esc_html( $reviewer->display_name ); ?></p>
		<p><strong><?php esc_html_e( 'Reviewed:', 'drthai-health' ); ?></strong> <?php echo esc_html( drthai_health_format_reviewed_at( $reviewed_at ) ); ?></p>
	<?php endif; ?>
	<?php if ( current_user_can( DRTHAI_MEDICAL_REVIEW_CAPABILITY ) && current_user_can( 'edit_post', $post->ID ) ) : ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="drthai_mark_medically_reviewed" />
			<input type="hidden" name="post_id" value="<?php echo esc_attr( $post->ID ); ?>" />
			<?php wp_nonce_field( 'drthai_medical_review_' . $post->ID, 'drthai_medical_review_nonce' ); ?>
			<?php submit_button( $is_reviewed ? __( 'Review again', 'drthai-health' ) : __( 'Mark as Medically Reviewed', 'drthai-health' ), 'primary', 'submit', false ); ?>
		</form>
	<?php endif; ?>
	<?php
}

/**
 * Add the Medical Review status box to Post editing.
 */
function drthai_health_add_medical_review_meta_box() {
	add_meta_box(
		'drthai-medical-review',
		__( 'Medical Review', 'drthai-health' ),
		'drthai_health_render_medical_review_meta_box',
		'post',
		'side',
		'high'
	);
}
add_action( 'add_meta_boxes_post', 'drthai_health_add_medical_review_meta_box' );

/**
 * Show clear admin feedback after review or a blocked publication.
 */
function drthai_health_editorial_admin_notices() {
	if ( isset( $_GET['drthai_reviewed'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['drthai_reviewed'] ) ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Medical Review recorded.', 'drthai-health' ) . '</p></div>';
	}
	if ( isset( $_GET['drthai_review_required'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['drthai_review_required'] ) ) ) {
		echo '<div class="notice notice-error"><p>' . esc_html__( 'Publishing or scheduling was blocked. Mark this Post as medically reviewed, then try again.', 'drthai-health' ) . '</p></div>';
	}
}
add_action( 'admin_notices', 'drthai_health_editorial_admin_notices' );

/**
 * Identify a transition from a safe unpublished state into publication.
 *
 * @param int    $post_id       Post ID, or zero for a new Post.
 * @param string $target_status Requested status.
 * @return bool
 */
function drthai_health_is_publication_transition( $post_id, $target_status ) {
	if ( ! in_array( $target_status, array( 'publish', 'future' ), true ) ) {
		return false;
	}

	$previous_status = $post_id ? get_post_status( $post_id ) : false;

	return ! in_array( $previous_status, array( 'publish', 'future' ), true );
}

/**
 * Enforce comments-closed defaults and server-side publication protection.
 *
 * @param array $data                Sanitized post data.
 * @param array $postarr             Sanitized post array.
 * @param array $unsanitized_postarr Original post array.
 * @return array
 */
function drthai_health_enforce_editorial_workflow( $data, $postarr, $unsanitized_postarr ) {
	$post_id = isset( $postarr['ID'] ) ? absint( $postarr['ID'] ) : 0;

	if ( 'post' !== $data['post_type'] ) {
		return $data;
	}

	if ( ! $post_id ) {
		$data['comment_status'] = 'closed';
	}

	if ( drthai_health_is_publication_transition( $post_id, $data['post_status'] )
		&& ! drthai_health_post_has_valid_medical_review( $post_id ) ) {
		$previous_status     = $post_id ? get_post_status( $post_id ) : false;
		$data['post_status'] = in_array( $previous_status, array( 'draft', 'pending', 'private' ), true ) ? $previous_status : 'draft';

		$GLOBALS['drthai_health_publication_blocked'] = true;
		do_action( 'drthai_health_publication_blocked', $post_id, $postarr, $unsanitized_postarr );

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::warning( 'Publishing or scheduling was blocked: Medical Review is required.' );
		}
	}

	return $data;
}
add_filter( 'wp_insert_post_data', 'drthai_health_enforce_editorial_workflow', 20, 3 );

/**
 * Add actionable feedback to normal wp-admin redirects after a block.
 *
 * @param string $location Redirect URL.
 * @return string
 */
function drthai_health_add_publication_block_notice( $location ) {
	if ( ! empty( $GLOBALS['drthai_health_publication_blocked'] ) ) {
		$location = add_query_arg( 'drthai_review_required', '1', $location );
	}

	return $location;
}
add_filter( 'redirect_post_location', 'drthai_health_add_publication_block_notice' );

/**
 * Reject REST/Gutenberg publication bypasses with an actionable error.
 *
 * @param stdClass       $prepared_post Prepared Post object.
 * @param WP_REST_Request $request       REST request.
 * @return stdClass|WP_Error
 */
function drthai_health_block_unreviewed_rest_publication( $prepared_post, $request ) {
	$request_meta = $request->get_param( 'meta' );

	if (
		is_array( $request_meta )
		&& (
			array_key_exists( DRTHAI_MEDICAL_REVIEWER_META, $request_meta )
			|| array_key_exists( DRTHAI_REVIEWED_AT_META, $request_meta )
		)
	) {
		return new WP_Error(
			'drthai_protected_review_meta',
			__( 'Medical review metadata can only be set with the medical review action.', 'drthai-health' ),
			array( 'status' => 403 )
		);
	}

	$target_status = isset( $prepared_post->post_status ) ? $prepared_post->post_status : '';
	$post_id       = absint( $request->get_param( 'id' ) );

	if ( drthai_health_is_publication_transition( $post_id, $target_status )
		&& ! drthai_health_post_has_valid_medical_review( $post_id ) ) {
		return new WP_Error(
			'drthai_medical_review_required',
			__( 'Medical Review is required before this Post can be published or scheduled.', 'drthai-health' ),
			array( 'status' => 400 )
		);
	}

	return $prepared_post;
}
add_filter( 'rest_pre_insert_post', 'drthai_health_block_unreviewed_rest_publication', 20, 2 );
