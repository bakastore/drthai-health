<?php
/**
 * Title: Mở đầu trang chủ
 * Slug: drthai-health/hero
 * Categories: drthai-health, banner
 * Description: Khu vực giới thiệu bác sĩ và biểu mẫu gọi lại nhanh.
 * Viewport Width: 1440
 */
?>
<!-- wp:group {"align":"full","className":"drthai-section drthai-hero","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull drthai-section drthai-hero" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:columns {"align":"wide","verticalAlignment":"center","className":"drthai-hero__layout","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|60"}}}} -->
	<div class="wp-block-columns alignwide are-vertically-aligned-center drthai-hero__layout">
		<!-- wp:column {"verticalAlignment":"center","width":"55%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:55%">
			<!-- wp:paragraph {"textColor":"primary","className":"drthai-eyebrow"} -->
			<p class="drthai-eyebrow has-primary-color has-text-color">CHĂM SÓC TIÊU HÓA CHUYÊN SÂU</p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"level":1,"className":"drthai-hero__title"} -->
			<h1 class="wp-block-heading drthai-hero__title">Chăm sóc tiêu hóa,<br><mark style="background-color:rgba(0,0,0,0)" class="has-inline-color">an tâm</mark> mỗi ngày</h1>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"textColor":"ink-soft","className":"drthai-hero__copy","fontSize":"large"} -->
			<p class="drthai-hero__copy has-ink-soft-color has-text-color has-large-font-size">Thạc sĩ Nguyễn Hồng Thái chia sẻ kiến thức và định hướng chăm sóc các vấn đề tiêu hóa theo cách rõ ràng, dễ hiểu và tôn trọng từng người bệnh.</p>
			<!-- /wp:paragraph -->

			<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"1.8rem"}}}} -->
			<div class="wp-block-buttons" style="margin-top:1.8rem">
				<!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/dat-lich/">Đặt lịch tư vấn</a></div><!-- /wp:button -->
				<!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/tin-tuc/">Xem kiến thức</a></div><!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->

			<!-- wp:html -->
			<div class="drthai-trust-row"><span>Nội dung dễ hiểu</span><span>Bảo mật thông tin liên hệ</span><span>Không thay thế khám trực tiếp</span></div>
			<!-- /wp:html -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"center","width":"45%","className":"drthai-hero__visual"} -->
		<div class="wp-block-column is-vertically-aligned-center drthai-hero__visual" style="flex-basis:45%">


			<!-- wp:group {"className":"drthai-hero-form","layout":{"type":"constrained"}} -->
			<div class="wp-block-group drthai-hero-form">
				<!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Yêu cầu gọi lại nhanh</h3><!-- /wp:heading -->
				<!-- wp:paragraph --><p>Chỉ cần để lại thông tin cơ bản, không nhập triệu chứng hay hồ sơ bệnh án.</p><!-- /wp:paragraph -->
				<!-- wp:shortcode -->[drthai_callback_form compact="1"]<!-- /wp:shortcode -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
