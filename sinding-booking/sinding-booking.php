<?php
/**
 * Plugin Name: Sinding Photography Booking System
 * Description: A customizable booking system for Sinding Photography. Users can select photography services and add-ons, view live pricing, and submit bookings. Admin receives email notifications and can manage bookings in the WordPress dashboard.
 * Version: 1.0.0
 * Author: Sinding Photography
 * License: GPL-2.0-or-later
 * Text Domain: sinding-booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SINDING_BOOKING_VERSION', '1.0.0' );
define( 'SINDING_BOOKING_DIR', plugin_dir_path( __FILE__ ) );
define( 'SINDING_BOOKING_URL', plugin_dir_url( __FILE__ ) );

require_once SINDING_BOOKING_DIR . 'includes/db.php';
require_once SINDING_BOOKING_DIR . 'includes/ajax.php';
require_once SINDING_BOOKING_DIR . 'includes/admin.php';

/**
 * Activate plugin: create DB table and set default options.
 */
function sinding_booking_activate() {
	sinding_booking_create_table();
}
register_activation_hook( __FILE__, 'sinding_booking_activate' );

/**
 * Enqueue front-end assets and register shortcode.
 */
function sinding_booking_init() {
	add_shortcode( 'sinding_booking', 'sinding_booking_shortcode' );
}
add_action( 'init', 'sinding_booking_init' );

/**
 * Enqueue CSS and JS only on pages that use the shortcode.
 */
function sinding_booking_enqueue_assets() {
	global $post;
	if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'sinding_booking' ) ) {
		wp_enqueue_style(
			'sinding-booking',
			SINDING_BOOKING_URL . 'assets/css/booking.css',
			array(),
			SINDING_BOOKING_VERSION
		);
		wp_enqueue_script(
			'sinding-booking',
			SINDING_BOOKING_URL . 'assets/js/booking.js',
			array(),
			SINDING_BOOKING_VERSION,
			true
		);
		wp_localize_script(
			'sinding-booking',
			'sindingBooking',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'sinding_booking_nonce' ),
				'items'   => sinding_booking_get_items(),
				'i18n'    => array(
					'required'      => __( 'Please fill in all required fields.', 'sinding-booking' ),
					'selectSession' => __( 'Please select at least one session type.', 'sinding-booking' ),
					'invalidEmail'  => __( 'Please enter a valid email address.', 'sinding-booking' ),
					'invalidPhone'  => __( 'Please enter a valid phone number.', 'sinding-booking' ),
					'invalidDate'   => __( 'Please select a future date.', 'sinding-booking' ),
					'submitting'    => __( 'Submitting…', 'sinding-booking' ),
					'success'       => __( 'Your booking has been received! Check your email for a confirmation.', 'sinding-booking' ),
					'error'         => __( 'Something went wrong. Please try again.', 'sinding-booking' ),
				),
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'sinding_booking_enqueue_assets' );

/**
 * Shortcode callback – renders the booking form.
 *
 * @return string HTML output.
 */
function sinding_booking_shortcode() {
	ob_start();
	include SINDING_BOOKING_DIR . 'templates/booking-form.php';
	return ob_get_clean();
}
