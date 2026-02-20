<?php
/**
 * Front-end booking form template.
 * Rendered by the [sinding_booking] shortcode.
 *
 * @package Sinding_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$items    = sinding_booking_get_items();
$sessions = array_filter( $items, function ( $i ) { return 'session' === $i['type']; } );
$addons   = array_filter( $items, function ( $i ) { return 'addon' === $i['type']; } );
?>
<div id="sinding-booking-wrap" class="sb-wrap">

	<!-- ====== STEP 1 – SELECT SERVICES ====== -->
	<section class="sb-section" id="sb-step-1">
		<h2 class="sb-section__title"><?php esc_html_e( 'Select Your Session', 'sinding-booking' ); ?></h2>
		<p class="sb-section__subtitle"><?php esc_html_e( 'Choose one session type to get started.', 'sinding-booking' ); ?></p>

		<div class="sb-cards" role="group" aria-labelledby="sb-sessions-label">
			<span id="sb-sessions-label" class="screen-reader-text"><?php esc_html_e( 'Session types', 'sinding-booking' ); ?></span>
			<?php foreach ( $sessions as $item ) : ?>
			<label class="sb-card" for="sb-item-<?php echo esc_attr( $item['id'] ); ?>">
				<input
					type="radio"
					id="sb-item-<?php echo esc_attr( $item['id'] ); ?>"
					name="sb_session"
					value="<?php echo esc_attr( $item['id'] ); ?>"
					data-price="<?php echo esc_attr( $item['price'] ); ?>"
					data-type="session"
				>
				<span class="sb-card__inner">
					<span class="sb-card__label"><?php echo esc_html( $item['label'] ); ?></span>
					<span class="sb-card__desc"><?php echo esc_html( $item['description'] ); ?></span>
					<span class="sb-card__price">NOK <?php echo esc_html( number_format( $item['price'], 0, '.', ' ' ) ); ?></span>
				</span>
			</label>
			<?php endforeach; ?>
		</div>
	</section>

	<!-- ====== STEP 2 – ADD-ONS ====== -->
	<section class="sb-section" id="sb-step-2">
		<h2 class="sb-section__title"><?php esc_html_e( 'Customise with Add-ons', 'sinding-booking' ); ?></h2>
		<p class="sb-section__subtitle"><?php esc_html_e( 'Optionally add extras to your session.', 'sinding-booking' ); ?></p>

		<div class="sb-cards sb-cards--addons" role="group" aria-labelledby="sb-addons-label">
			<span id="sb-addons-label" class="screen-reader-text"><?php esc_html_e( 'Add-ons', 'sinding-booking' ); ?></span>
			<?php foreach ( $addons as $item ) : ?>
			<label class="sb-card sb-card--addon" for="sb-item-<?php echo esc_attr( $item['id'] ); ?>">
				<input
					type="checkbox"
					id="sb-item-<?php echo esc_attr( $item['id'] ); ?>"
					name="sb_addons[]"
					value="<?php echo esc_attr( $item['id'] ); ?>"
					data-price="<?php echo esc_attr( $item['price'] ); ?>"
					data-type="addon"
				>
				<span class="sb-card__inner">
					<span class="sb-card__label"><?php echo esc_html( $item['label'] ); ?></span>
					<span class="sb-card__desc"><?php echo esc_html( $item['description'] ); ?></span>
					<span class="sb-card__price">+ NOK <?php echo esc_html( number_format( $item['price'], 0, '.', ' ' ) ); ?></span>
				</span>
			</label>
			<?php endforeach; ?>
		</div>
	</section>

	<!-- ====== STICKY PRICE BAR ====== -->
	<div class="sb-price-bar" id="sb-price-bar" aria-live="polite">
		<span class="sb-price-bar__label"><?php esc_html_e( 'Total', 'sinding-booking' ); ?></span>
		<span class="sb-price-bar__amount" id="sb-total-display">NOK 0</span>
		<button type="button" class="sb-price-bar__cta" id="sb-open-booking" disabled>
			<?php esc_html_e( 'Book Now', 'sinding-booking' ); ?>
		</button>
	</div>

	<!-- ====== BOOKING FORM MODAL ====== -->
	<div class="sb-modal" id="sb-modal" role="dialog" aria-modal="true" aria-labelledby="sb-modal-title" hidden>
		<div class="sb-modal__backdrop" id="sb-modal-backdrop"></div>
		<div class="sb-modal__box">
			<button type="button" class="sb-modal__close" id="sb-modal-close" aria-label="<?php esc_attr_e( 'Close', 'sinding-booking' ); ?>">&#215;</button>
			<h2 class="sb-modal__title" id="sb-modal-title"><?php esc_html_e( 'Complete Your Booking', 'sinding-booking' ); ?></h2>

			<!-- Order summary inside modal -->
			<div class="sb-summary" id="sb-summary">
				<h3 class="sb-summary__heading"><?php esc_html_e( 'Your Selection', 'sinding-booking' ); ?></h3>
				<ul class="sb-summary__list" id="sb-summary-list"></ul>
				<p class="sb-summary__total">
					<?php esc_html_e( 'Total:', 'sinding-booking' ); ?>
					<strong id="sb-summary-total">NOK 0</strong>
				</p>
			</div>

			<form id="sb-booking-form" novalidate>
				<?php wp_nonce_field( 'sinding_booking_nonce', 'sinding_booking_nonce_field' ); ?>

				<div class="sb-form__group">
					<label class="sb-form__label" for="sb-name">
						<?php esc_html_e( 'Full Name', 'sinding-booking' ); ?>
						<span aria-hidden="true">*</span>
					</label>
					<input
						type="text"
						id="sb-name"
						name="name"
						class="sb-form__input"
						placeholder="<?php esc_attr_e( 'Jane Doe', 'sinding-booking' ); ?>"
						required
						autocomplete="name"
					>
					<span class="sb-form__error" id="sb-name-error" role="alert"></span>
				</div>

				<div class="sb-form__group">
					<label class="sb-form__label" for="sb-email">
						<?php esc_html_e( 'Email Address', 'sinding-booking' ); ?>
						<span aria-hidden="true">*</span>
					</label>
					<input
						type="email"
						id="sb-email"
						name="email"
						class="sb-form__input"
						placeholder="<?php esc_attr_e( 'jane@example.com', 'sinding-booking' ); ?>"
						required
						autocomplete="email"
					>
					<span class="sb-form__error" id="sb-email-error" role="alert"></span>
				</div>

				<div class="sb-form__group">
					<label class="sb-form__label" for="sb-phone">
						<?php esc_html_e( 'Phone Number', 'sinding-booking' ); ?>
						<span aria-hidden="true">*</span>
					</label>
					<input
						type="tel"
						id="sb-phone"
						name="phone"
						class="sb-form__input"
						placeholder="<?php esc_attr_e( '+47 000 00 000', 'sinding-booking' ); ?>"
						required
						autocomplete="tel"
					>
					<span class="sb-form__error" id="sb-phone-error" role="alert"></span>
				</div>

				<div class="sb-form__group">
					<label class="sb-form__label" for="sb-date">
						<?php esc_html_e( 'Preferred Date', 'sinding-booking' ); ?>
						<span aria-hidden="true">*</span>
					</label>
					<input
						type="date"
						id="sb-date"
						name="booking_date"
						class="sb-form__input"
						required
					>
					<span class="sb-form__error" id="sb-date-error" role="alert"></span>
				</div>

				<div class="sb-form__actions">
					<div class="sb-form__notice" id="sb-form-notice" role="alert" hidden></div>
					<button type="submit" class="sb-form__submit" id="sb-submit-btn">
						<?php esc_html_e( 'Confirm Booking', 'sinding-booking' ); ?>
					</button>
				</div>
			</form>
		</div>
	</div>

</div><!-- /#sinding-booking-wrap -->
