<?php
/**
 * Read-only editorial visibility for the native WordPress Posts List Table.
 *
 * @package DrThai_Health
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add focused editorial columns while preserving useful native columns.
 *
 * @param array $columns Existing Post columns.
 * @return array
 */
function drthai_health_editorial_admin_columns( $columns ) {
	$editorial_columns = array(
		'drthai_medical_review' => __( 'Medical Review', 'drthai-health' ),
		'drthai_reviewed_date'  => __( 'Reviewed', 'drthai-health' ),
		'drthai_media_status'   => __( 'Media', 'drthai-health' ),
		'drthai_updated_date'   => __( 'Updated', 'drthai-health' ),
		'drthai_editorial_health' => __( 'Editorial Health', 'drthai-health' ),
		'drthai_lifecycle'       => __( 'Lifecycle', 'drthai-health' ),
	);
	$result = array();

	foreach ( $columns as $key => $label ) {
		if ( 'date' === $key ) {
			$result = array_merge( $result, $editorial_columns );
		}
		$result[ $key ] = $label;
	}

	if ( ! isset( $columns['date'] ) ) {
		$result = array_merge( $result, $editorial_columns );
	}

	return $result;
}
add_filter( 'manage_post_posts_columns', 'drthai_health_editorial_admin_columns' );

/**
 * Return the displayable reviewer for a valid C1 review.
 *
 * @param int $post_id Post ID.
 * @return WP_User|false
 */
function drthai_health_editorial_admin_reviewer( $post_id ) {
	if ( ! drthai_health_post_has_valid_medical_review( $post_id ) ) {
		return false;
	}

	return get_user_by( 'id', absint( get_post_meta( $post_id, DRTHAI_MEDICAL_REVIEWER_META, true ) ) );
}

/**
 * Return the current C3 media status for a Post.
 *
 * @param int $post_id Post ID.
 * @return string complete, missing_image or missing_alt.
 */
function drthai_health_editorial_admin_media_status( $post_id ) {
	$thumbnail_id = absint( get_post_thumbnail_id( $post_id ) );
	if ( ! $thumbnail_id || 'attachment' !== get_post_type( $thumbnail_id ) || ! wp_attachment_is_image( $thumbnail_id ) ) {
		return 'missing_image';
	}

	$alt_text = trim( wp_strip_all_tags( (string) get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true ) ) );

	return '' === $alt_text ? 'missing_alt' : 'complete';
}

/**
 * Check whether a Post has a meaningful non-default Category.
 *
 * @param int $post_id Post ID.
 * @return bool
 */
function drthai_health_editorial_admin_has_category( $post_id ) {
	$default_category = absint( get_option( 'default_category' ) );
	$category_ids     = wp_get_post_categories( $post_id );

	foreach ( $category_ids as $category_id ) {
		if ( absint( $category_id ) !== $default_category ) {
			return true;
		}
	}

	return false;
}

/**
 * Derive the read-only Editorial Health state from current Post data.
 *
 * @param int $post_id Post ID.
 * @return array{state:string,label:string,reasons:string[]}
 */
function drthai_health_get_editorial_health( $post_id ) {
	$post    = get_post( $post_id );
	$reasons = array();

	if ( ! $post || 'post' !== $post->post_type ) {
		return array(
			'state'   => 'attention',
			'label'   => __( 'NEEDS ATTENTION', 'drthai-health' ),
			'reasons' => array( __( 'Bài viết không hợp lệ', 'drthai-health' ) ),
		);
	}

	if ( ! drthai_health_post_has_valid_medical_review( $post_id ) ) {
		$reasons[] = __( 'Thiếu review', 'drthai-health' );
	}

	$media_status = drthai_health_editorial_admin_media_status( $post_id );
	if ( 'missing_image' === $media_status ) {
		$reasons[] = __( 'Thiếu ảnh', 'drthai-health' );
	} elseif ( 'missing_alt' === $media_status ) {
		$reasons[] = __( 'Thiếu Alt', 'drthai-health' );
	}

	if ( '' === trim( wp_strip_all_tags( (string) $post->post_excerpt ) ) ) {
		$reasons[] = __( 'Thiếu excerpt', 'drthai-health' );
	}

	if ( ! drthai_health_editorial_admin_has_category( $post_id ) ) {
		$reasons[] = __( 'Chưa phân loại', 'drthai-health' );
	}

	if ( $reasons ) {
		return array(
			'state'   => 'attention',
			'label'   => __( 'NEEDS ATTENTION', 'drthai-health' ),
			'reasons' => $reasons,
		);
	}

	$is_ready = in_array( $post->post_status, array( 'draft', 'pending', 'private' ), true );

	return array(
		'state'   => $is_ready ? 'ready' : 'ok',
		'label'   => $is_ready ? __( 'READY', 'drthai-health' ) : __( 'OK', 'drthai-health' ),
		'reasons' => array(),
	);
}

/**
 * Render the custom Post column values.
 *
 * @param string $column  Column key.
 * @param int    $post_id Post ID.
 */
function drthai_health_render_editorial_admin_column( $column, $post_id ) {
	if ( 'drthai_medical_review' === $column ) {
		$reviewer = drthai_health_editorial_admin_reviewer( $post_id );
		echo $reviewer ? esc_html( $reviewer->display_name ) : esc_html__( 'Chưa rà soát', 'drthai-health' );
		return;
	}

	if ( 'drthai_reviewed_date' === $column ) {
		$reviewed_at = (string) get_post_meta( $post_id, DRTHAI_REVIEWED_AT_META, true );
		$formatted   = drthai_health_sanitize_reviewed_at( $reviewed_at ) === $reviewed_at
			? drthai_health_format_reviewed_at( $reviewed_at )
			: '';
		echo '' !== $formatted ? esc_html( $formatted ) : '&mdash;';
		return;
	}

	if ( 'drthai_media_status' === $column ) {
		$labels = array(
			'complete'      => __( 'OK', 'drthai-health' ),
			'missing_image' => __( 'Thiếu ảnh', 'drthai-health' ),
			'missing_alt'   => __( 'Thiếu Alt', 'drthai-health' ),
		);
		echo esc_html( $labels[ drthai_health_editorial_admin_media_status( $post_id ) ] );
		return;
	}

	if ( 'drthai_updated_date' === $column ) {
		$modified = get_post_modified_time( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), false, $post_id, true );
		echo $modified ? esc_html( $modified ) : '&mdash;';
		return;
	}

	if ( 'drthai_editorial_health' === $column ) {
		$health = drthai_health_get_editorial_health( $post_id );
		echo '<strong>' . esc_html( $health['label'] ) . '</strong>';
		if ( $health['reasons'] ) {
			echo '<br><span>' . esc_html( implode( '; ', array_slice( $health['reasons'], 0, 5 ) ) ) . '</span>';
		}
		return;
	}

	if ( 'drthai_lifecycle' === $column ) {
		$lifecycle = drthai_health_get_content_lifecycle( $post_id );
		echo '<strong>' . esc_html( $lifecycle['label'] ) . '</strong>';
		if ( 'never_reviewed' === $lifecycle['state'] ) {
			echo '<br><span>' . esc_html__( 'Chưa có ngày rà soát', 'drthai-health' ) . '</span>';
		} elseif ( 'current' === $lifecycle['state'] && $lifecycle['due'] ) {
			echo '<br><span>' . esc_html__( 'Review due:', 'drthai-health' ) . ' ' . esc_html( drthai_health_format_lifecycle_date( $lifecycle['due'] ) ) . '</span>';
		} elseif ( 'needs_review' === $lifecycle['state'] && $lifecycle['due'] ) {
			echo '<br><span>' . esc_html__( 'Overdue since:', 'drthai-health' ) . ' ' . esc_html( drthai_health_format_lifecycle_date( $lifecycle['due'] ) ) . '</span>';
		} elseif ( 'updated_since_review' === $lifecycle['state'] ) {
			echo '<br><span>' . esc_html__( 'Content changed after review', 'drthai-health' ) . '</span>';
		}
	}
}
add_action( 'manage_post_posts_custom_column', 'drthai_health_render_editorial_admin_column', 10, 2 );

/**
 * Register simple native sorting for Updated and Reviewed dates.
 *
 * @param array $columns Sortable columns.
 * @return array
 */
function drthai_health_editorial_admin_sortable_columns( $columns ) {
	$columns['drthai_updated_date']  = 'drthai_updated_date';
	$columns['drthai_reviewed_date'] = 'drthai_reviewed_date';

	return $columns;
}
add_filter( 'manage_edit-post_sortable_columns', 'drthai_health_editorial_admin_sortable_columns' );

/**
 * Read one scalar request value without accepting array-shaped input.
 *
 * @param array  $source Request-like input.
 * @param string $key    Input key.
 * @return string
 */
function drthai_health_editorial_admin_request_value( $source, $key ) {
	if ( ! isset( $source[ $key ] ) || ! is_scalar( $source[ $key ] ) ) {
		return '';
	}

	return (string) wp_unslash( $source[ $key ] );
}

/**
 * Sanitize the four C2 filter values.
 *
 * @param array $source Request-like input.
 * @return array
 */
function drthai_health_editorial_admin_sanitize_filters( $source ) {
	$review_values = array( 'reviewed', 'unreviewed' );
	$media_values  = array( 'complete', 'missing_image', 'missing_alt' );
	$health_values = array( 'healthy', 'attention' );
	$lifecycle_values = array( 'current', 'needs_review', 'never_reviewed', 'updated_since_review', 'needs_action' );
	$review_status = sanitize_key( drthai_health_editorial_admin_request_value( $source, 'drthai_review_status' ) );
	$media_status  = sanitize_key( drthai_health_editorial_admin_request_value( $source, 'drthai_media_status' ) );
	$health_status = sanitize_key( drthai_health_editorial_admin_request_value( $source, 'drthai_editorial_health' ) );
	$reviewer_raw  = drthai_health_editorial_admin_request_value( $source, 'drthai_reviewer' );
	$reviewer_id   = ctype_digit( $reviewer_raw ) ? absint( $reviewer_raw ) : 0;
	$lifecycle     = sanitize_key( drthai_health_editorial_admin_request_value( $source, 'drthai_lifecycle' ) );

	return array(
		'review_status' => in_array( $review_status, $review_values, true ) ? $review_status : '',
		'reviewer_id'   => $reviewer_id,
		'media_status'  => in_array( $media_status, $media_values, true ) ? $media_status : '',
		'health_status' => in_array( $health_status, $health_values, true ) ? $health_status : '',
		'lifecycle'     => in_array( $lifecycle, $lifecycle_values, true ) ? $lifecycle : '',
	);
}

/**
 * Return a bounded list of users who are actually recorded as reviewers.
 *
 * @return WP_User[]
 */
function drthai_health_editorial_admin_reviewers() {
	global $wpdb;

	$user_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT DISTINCT u.ID
			FROM {$wpdb->users} u
			INNER JOIN {$wpdb->postmeta} pm ON pm.meta_value = CAST(u.ID AS CHAR)
			INNER JOIN {$wpdb->posts} reviewed_post ON reviewed_post.ID = pm.post_id
			WHERE pm.meta_key = %s
			AND reviewed_post.post_type = 'post'
			ORDER BY u.display_name ASC
			LIMIT 200",
			DRTHAI_MEDICAL_REVIEWER_META
		)
	);

	if ( ! $user_ids ) {
		return array();
	}

	return get_users(
		array(
			'include' => array_map( 'absint', $user_ids ),
			'orderby' => 'display_name',
			'order'   => 'ASC',
		)
	);
}

/**
 * Render native dropdown filters above the Posts List Table.
 *
 * @param string $post_type Current post type.
 * @param string $which     Top or bottom table navigation.
 */
function drthai_health_render_editorial_admin_filters( $post_type, $which ) {
	if ( 'post' !== $post_type || 'top' !== $which || ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	$filters = drthai_health_editorial_admin_sanitize_filters( $_GET );
	?>
	<label class="screen-reader-text" for="drthai-review-status-filter"><?php esc_html_e( 'Lọc theo trạng thái Medical Review', 'drthai-health' ); ?></label>
	<select id="drthai-review-status-filter" name="drthai_review_status">
		<option value=""><?php esc_html_e( 'Tất cả trạng thái review', 'drthai-health' ); ?></option>
		<option value="reviewed" <?php selected( $filters['review_status'], 'reviewed' ); ?>><?php esc_html_e( 'Đã rà soát', 'drthai-health' ); ?></option>
		<option value="unreviewed" <?php selected( $filters['review_status'], 'unreviewed' ); ?>><?php esc_html_e( 'Chưa rà soát', 'drthai-health' ); ?></option>
	</select>

	<label class="screen-reader-text" for="drthai-reviewer-filter"><?php esc_html_e( 'Lọc theo Medical Reviewer', 'drthai-health' ); ?></label>
	<select id="drthai-reviewer-filter" name="drthai_reviewer">
		<option value="0"><?php esc_html_e( 'Tất cả reviewer', 'drthai-health' ); ?></option>
		<?php foreach ( drthai_health_editorial_admin_reviewers() as $reviewer ) : ?>
			<option value="<?php echo esc_attr( $reviewer->ID ); ?>" <?php selected( $filters['reviewer_id'], $reviewer->ID ); ?>><?php echo esc_html( $reviewer->display_name ); ?></option>
		<?php endforeach; ?>
	</select>

	<label class="screen-reader-text" for="drthai-media-status-filter"><?php esc_html_e( 'Lọc theo trạng thái media', 'drthai-health' ); ?></label>
	<select id="drthai-media-status-filter" name="drthai_media_status">
		<option value=""><?php esc_html_e( 'Tất cả media', 'drthai-health' ); ?></option>
		<option value="complete" <?php selected( $filters['media_status'], 'complete' ); ?>><?php esc_html_e( 'Đầy đủ', 'drthai-health' ); ?></option>
		<option value="missing_image" <?php selected( $filters['media_status'], 'missing_image' ); ?>><?php esc_html_e( 'Thiếu Featured Image', 'drthai-health' ); ?></option>
		<option value="missing_alt" <?php selected( $filters['media_status'], 'missing_alt' ); ?>><?php esc_html_e( 'Thiếu Alt Text', 'drthai-health' ); ?></option>
	</select>

	<label class="screen-reader-text" for="drthai-health-filter"><?php esc_html_e( 'Lọc theo Editorial Health', 'drthai-health' ); ?></label>
	<select id="drthai-health-filter" name="drthai_editorial_health">
		<option value=""><?php esc_html_e( 'Tất cả chất lượng', 'drthai-health' ); ?></option>
		<option value="healthy" <?php selected( $filters['health_status'], 'healthy' ); ?>><?php esc_html_e( 'OK / Ready', 'drthai-health' ); ?></option>
		<option value="attention" <?php selected( $filters['health_status'], 'attention' ); ?>><?php esc_html_e( 'Needs Attention', 'drthai-health' ); ?></option>
	</select>

	<label class="screen-reader-text" for="drthai-lifecycle-filter"><?php esc_html_e( 'Lọc theo vòng đời nội dung', 'drthai-health' ); ?></label>
	<select id="drthai-lifecycle-filter" name="drthai_lifecycle">
		<option value=""><?php esc_html_e( 'Tất cả vòng đời', 'drthai-health' ); ?></option>
		<option value="current" <?php selected( $filters['lifecycle'], 'current' ); ?>><?php esc_html_e( 'Current', 'drthai-health' ); ?></option>
		<option value="needs_review" <?php selected( $filters['lifecycle'], 'needs_review' ); ?>><?php esc_html_e( 'Needs Review', 'drthai-health' ); ?></option>
		<option value="never_reviewed" <?php selected( $filters['lifecycle'], 'never_reviewed' ); ?>><?php esc_html_e( 'Never Reviewed', 'drthai-health' ); ?></option>
		<option value="updated_since_review" <?php selected( $filters['lifecycle'], 'updated_since_review' ); ?>><?php esc_html_e( 'Updated Since Review', 'drthai-health' ); ?></option>
		<option value="needs_action" <?php selected( $filters['lifecycle'], 'needs_action' ); ?>><?php esc_html_e( 'Cần xử lý', 'drthai-health' ); ?></option>
	</select>
	<?php
}
add_action( 'restrict_manage_posts', 'drthai_health_render_editorial_admin_filters', 10, 2 );

/**
 * Apply already-sanitized C2 filters to a WP_Query without loading Posts in PHP.
 *
 * @param WP_Query $query Query to update.
 * @param array    $source Request-like input.
 */
function drthai_health_editorial_admin_apply_query( $query, $source ) {
	$filters = drthai_health_editorial_admin_sanitize_filters( $source );

	$query->set( 'drthai_c2_active', true );
	$query->set( 'drthai_c2_review_status', $filters['review_status'] );
	$query->set( 'drthai_c2_reviewer_id', $filters['reviewer_id'] );
	$query->set( 'drthai_c2_media_status', $filters['media_status'] );
	$query->set( 'drthai_c2_health_status', $filters['health_status'] );
	$query->set( 'drthai_c4_lifecycle', $filters['lifecycle'] );

	$order_by = sanitize_key( drthai_health_editorial_admin_request_value( $source, 'orderby' ) );
	if ( 'drthai_updated_date' === $order_by ) {
		$query->set( 'orderby', 'modified' );
	} elseif ( 'drthai_reviewed_date' === $order_by ) {
		$query->set( 'orderby', 'drthai_reviewed_date' );
	}

	$order = strtoupper( sanitize_key( drthai_health_editorial_admin_request_value( $source, 'order' ) ) );
	$order = $order ? $order : 'DESC';
	$query->set( 'order', in_array( $order, array( 'ASC', 'DESC' ), true ) ? $order : 'DESC' );
}

/**
 * Prepare only the main native Posts admin query.
 *
 * @param WP_Query $query Query being prepared.
 */
function drthai_health_prepare_editorial_admin_query( $query ) {
	global $pagenow;

	$post_type = $query->get( 'post_type' );
	if ( ! is_admin() || 'edit.php' !== $pagenow || ! $query->is_main_query() || ( $post_type && 'post' !== $post_type ) || ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	drthai_health_editorial_admin_apply_query( $query, $_GET );
}
add_action( 'pre_get_posts', 'drthai_health_prepare_editorial_admin_query' );

/**
 * Build reusable SQL predicates for review, media and health filters.
 *
 * @return array{review:string,image:string,media:string,category:string,complete:string}
 */
function drthai_health_editorial_admin_sql_predicates() {
	global $wpdb;

	$review = $wpdb->prepare(
		"EXISTS (
			SELECT 1 FROM {$wpdb->postmeta} review_user
			INNER JOIN {$wpdb->users} reviewer ON reviewer.ID = CAST(review_user.meta_value AS UNSIGNED)
			WHERE review_user.post_id = {$wpdb->posts}.ID
			AND review_user.meta_key = %s
			AND review_user.meta_value REGEXP '^[1-9][0-9]*$'
		) AND EXISTS (
			SELECT 1 FROM {$wpdb->postmeta} review_time
			WHERE review_time.post_id = {$wpdb->posts}.ID
			AND review_time.meta_key = %s
			AND review_time.meta_value REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(\\.[0-9]{6})?Z$'
		)",
		DRTHAI_MEDICAL_REVIEWER_META,
		DRTHAI_REVIEWED_AT_META
	);

	$image = "EXISTS (
		SELECT 1 FROM {$wpdb->postmeta} thumbnail
		INNER JOIN {$wpdb->posts} attachment ON attachment.ID = CAST(thumbnail.meta_value AS UNSIGNED)
		WHERE thumbnail.post_id = {$wpdb->posts}.ID
		AND thumbnail.meta_key = '_thumbnail_id'
		AND attachment.post_type = 'attachment'
		AND attachment.post_mime_type LIKE 'image/%'
	)";

	$media = $wpdb->prepare(
		"EXISTS (
			SELECT 1 FROM {$wpdb->postmeta} thumbnail
			INNER JOIN {$wpdb->posts} attachment ON attachment.ID = CAST(thumbnail.meta_value AS UNSIGNED)
			WHERE thumbnail.post_id = {$wpdb->posts}.ID
			AND thumbnail.meta_key = '_thumbnail_id'
			AND attachment.post_type = 'attachment'
			AND attachment.post_mime_type LIKE 'image/%%'
			AND EXISTS (
				SELECT 1 FROM {$wpdb->postmeta} image_alt
				WHERE image_alt.post_id = attachment.ID
				AND image_alt.meta_key = %s
				AND TRIM(image_alt.meta_value) <> ''
			)
		)",
		'_wp_attachment_image_alt'
	);

	$category = $wpdb->prepare(
		"EXISTS (
			SELECT 1 FROM {$wpdb->term_relationships} relationship
			INNER JOIN {$wpdb->term_taxonomy} taxonomy ON taxonomy.term_taxonomy_id = relationship.term_taxonomy_id
			WHERE relationship.object_id = {$wpdb->posts}.ID
			AND taxonomy.taxonomy = 'category'
			AND taxonomy.term_id <> %d
		)",
		absint( get_option( 'default_category' ) )
	);

	$complete = "({$review}) AND ({$media}) AND TRIM(COALESCE({$wpdb->posts}.post_excerpt, '')) <> '' AND ({$category})";

	return array(
		'review'   => $review,
		'image'    => $image,
		'media'    => $media,
		'category' => $category,
		'complete' => $complete,
	);
}

/**
 * Add bounded database predicates and Reviewed Date sorting to the Posts query.
 *
 * @param array    $clauses SQL clauses.
 * @param WP_Query $query   Current query.
 * @return array
 */
function drthai_health_editorial_admin_posts_clauses( $clauses, $query ) {
	global $wpdb;

	if ( ! $query->get( 'drthai_c2_active' ) ) {
		return $clauses;
	}

	$predicates    = drthai_health_editorial_admin_sql_predicates();
	$review_status = $query->get( 'drthai_c2_review_status' );
	$reviewer_id   = absint( $query->get( 'drthai_c2_reviewer_id' ) );
	$media_status  = $query->get( 'drthai_c2_media_status' );
	$health_status = $query->get( 'drthai_c2_health_status' );
	$lifecycle     = $query->get( 'drthai_c4_lifecycle' );

	if ( 'reviewed' === $review_status ) {
		$clauses['where'] .= " AND ({$predicates['review']})";
	} elseif ( 'unreviewed' === $review_status ) {
		$clauses['where'] .= " AND NOT ({$predicates['review']})";
	}

	if ( $reviewer_id ) {
		$clauses['where'] .= $wpdb->prepare(
			" AND EXISTS (
				SELECT 1 FROM {$wpdb->postmeta} selected_reviewer
				WHERE selected_reviewer.post_id = {$wpdb->posts}.ID
				AND selected_reviewer.meta_key = %s
				AND CAST(selected_reviewer.meta_value AS UNSIGNED) = %d
			)",
			DRTHAI_MEDICAL_REVIEWER_META,
			$reviewer_id
		);
	}

	if ( 'complete' === $media_status ) {
		$clauses['where'] .= " AND ({$predicates['media']})";
	} elseif ( 'missing_image' === $media_status ) {
		$clauses['where'] .= " AND NOT ({$predicates['image']})";
	} elseif ( 'missing_alt' === $media_status ) {
		$clauses['where'] .= " AND ({$predicates['image']}) AND NOT ({$predicates['media']})";
	}

	if ( 'healthy' === $health_status ) {
		$clauses['where'] .= " AND ({$predicates['complete']})";
	} elseif ( 'attention' === $health_status ) {
		$clauses['where'] .= " AND NOT ({$predicates['complete']})";
	}

	if ( $lifecycle ) {
		$lifecycle_predicates = drthai_health_content_lifecycle_sql_predicates();
		if ( isset( $lifecycle_predicates[ $lifecycle ] ) ) {
			$clauses['where'] .= " AND ({$lifecycle_predicates[$lifecycle]})";
		}
	}

	if ( 'drthai_reviewed_date' === $query->get( 'orderby' ) ) {
		$clauses['join']   .= $wpdb->prepare(
			" LEFT JOIN (
				SELECT post_id, MAX(meta_value) AS meta_value
				FROM {$wpdb->postmeta}
				WHERE meta_key = %s
				GROUP BY post_id
			) drthai_review_sort ON drthai_review_sort.post_id = {$wpdb->posts}.ID",
			DRTHAI_REVIEWED_AT_META
		);
		$order              = 'ASC' === strtoupper( $query->get( 'order' ) ) ? 'ASC' : 'DESC';
		$clauses['orderby'] = "drthai_review_sort.meta_value {$order}, {$wpdb->posts}.ID {$order}";
	}

	return $clauses;
}
add_filter( 'posts_clauses', 'drthai_health_editorial_admin_posts_clauses', 20, 2 );

/**
 * Prime reviewer users for the bounded current admin page to avoid N+1 lookups.
 *
 * @param WP_Post[] $posts Query results.
 * @param WP_Query  $query Query object.
 * @return WP_Post[]
 */
function drthai_health_prime_editorial_admin_reviewers( $posts, $query ) {
	if ( ! $query->get( 'drthai_c2_active' ) || ! $posts ) {
		return $posts;
	}

	$post_ids = wp_list_pluck( $posts, 'ID' );
	update_meta_cache( 'post', $post_ids );
	$reviewer_ids  = array();
	$thumbnail_ids = array();
	foreach ( $post_ids as $post_id ) {
		$reviewer_id = absint( get_post_meta( $post_id, DRTHAI_MEDICAL_REVIEWER_META, true ) );
		if ( $reviewer_id ) {
			$reviewer_ids[] = $reviewer_id;
		}
		$thumbnail_id = absint( get_post_meta( $post_id, '_thumbnail_id', true ) );
		if ( $thumbnail_id ) {
			$thumbnail_ids[] = $thumbnail_id;
		}
	}

	if ( $reviewer_ids ) {
		cache_users( array_unique( $reviewer_ids ) );
	}
	if ( $thumbnail_ids ) {
		_prime_post_caches( array_unique( $thumbnail_ids ), false, true );
	}

	return $posts;
}
add_filter( 'the_posts', 'drthai_health_prime_editorial_admin_reviewers', 10, 2 );
