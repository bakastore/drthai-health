<?php
/**
 * Title: Tin tức mới nhất
 * Slug: drthai-health/latest-posts
 * Categories: drthai-health, posts
 * Description: Ba bài viết sức khỏe mới nhất.
 * Viewport Width: 1200
 */
?>
<!-- wp:group {"align":"full","backgroundColor":"white","className":"drthai-section","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull drthai-section has-white-background-color has-background" style="padding-top:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--70)">
	<!-- wp:group {"align":"wide","layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:paragraph {"align":"center","textColor":"primary","className":"drthai-eyebrow"} --><p class="has-text-align-center drthai-eyebrow has-primary-color has-text-color">TIN TỨC &amp; CẨM NANG</p><!-- /wp:paragraph -->
		<!-- wp:heading {"textAlign":"center","fontSize":"x-large"} --><h2 class="wp-block-heading has-text-align-center has-x-large-font-size">Kiến thức tiêu hóa mới nhất</h2><!-- /wp:heading -->
		<!-- wp:paragraph {"align":"center","textColor":"ink-soft"} --><p class="has-text-align-center has-ink-soft-color has-text-color">Mỗi bài viết có thể gắn nhiều chuyên mục và chủ đề để người đọc tìm kiếm thuận tiện.</p><!-- /wp:paragraph -->

		<!-- wp:query {"queryId":10,"query":{"perPage":3,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"displayLayout":{"type":"flex","columns":3},"align":"wide","style":{"spacing":{"margin":{"top":"2.5rem"}}}} -->
		<div class="wp-block-query alignwide" style="margin-top:2.5rem">
			<!-- wp:post-template -->
				<!-- wp:group {"className":"drthai-post-card","style":{"spacing":{"padding":{"top":"0","right":"1.25rem","bottom":"1.35rem","left":"1.25rem"}},"dimensions":{"minHeight":"100%"}},"layout":{"type":"constrained"}} -->
				<div class="wp-block-group drthai-post-card" style="min-height:100%;padding-top:0;padding-right:1.25rem;padding-bottom:1.35rem;padding-left:1.25rem">
					<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/10","style":{"spacing":{"margin":{"right":"-1.25rem","left":"-1.25rem"}}}} /-->
					<!-- wp:post-terms {"term":"category","textColor":"accent-dark","fontSize":"small"} /-->
					<!-- wp:post-title {"isLink":true,"fontSize":"large"} /-->
					<!-- wp:post-excerpt {"moreText":"Đọc tiếp","excerptLength":20,"fontSize":"small"} /-->
				</div>
				<!-- /wp:group -->
			<!-- /wp:post-template -->
			<!-- wp:query-no-results --><!-- wp:group {"backgroundColor":"background-alt","className":"drthai-empty-state","layout":{"type":"constrained"}} --><div class="wp-block-group drthai-empty-state has-background-alt-background-color has-background"><!-- wp:paragraph {"align":"center","textColor":"ink-soft"} --><p class="has-text-align-center has-ink-soft-color has-text-color">Chưa có bài viết được xuất bản. Anh có thể tạo bài tại Bài viết → Viết bài mới.</p><!-- /wp:paragraph --></div><!-- /wp:group --><!-- /wp:query-no-results -->
		</div>
		<!-- /wp:query -->

		<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"2rem"}}}} --><div class="wp-block-buttons" style="margin-top:2rem"><!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/tin-tuc/">Khám phá tất cả chuyên mục</a></div><!-- /wp:button --></div><!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->

