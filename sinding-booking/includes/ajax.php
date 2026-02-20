<?php
/**
 * AJAX handlers for booking form submission.
 *
 * @package Sinding_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_ajax_sinding_submit_booking', 'sinding_booking_ajax_submit' );
add_action( 'wp_ajax_nopriv_sinding_submit_booking', 'sinding_booking_ajax_submit' );

/**
 * Handle booking form submission via AJAX.
 */
function sinding_booking_ajax_submit() {
	check_ajax_referer( 'sinding_booking_nonce', 'nonce' );

	// --- Collect and sanitise input ----------------------------------------
	$name         = isset( $_POST['name'] )         ? sanitize_text_field( wp_unslash( $_POST['name'] ) )         : '';
	$email        = isset( $_POST['email'] )        ? sanitize_email( wp_unslash( $_POST['email'] ) )              : '';
	$phone        = isset( $_POST['phone'] )        ? sanitize_text_field( wp_unslash( $_POST['phone'] ) )        : '';
	$booking_date = isset( $_POST['booking_date'] ) ? sanitize_text_field( wp_unslash( $_POST['booking_date'] ) ) : '';
	$session_ids  = isset( $_POST['session_items'] ) && is_array( $_POST['session_items'] )
		? array_map( 'sanitize_key', wp_unslash( $_POST['session_items'] ) )
		: array();
	$addon_ids    = isset( $_POST['addon_items'] ) && is_array( $_POST['addon_items'] )
		? array_map( 'sanitize_key', wp_unslash( $_POST['addon_items'] ) )
		: array();

	// --- Server-side validation --------------------------------------------
	if ( empty( $name ) || empty( $email ) || empty( $phone ) || empty( $booking_date ) ) {
		wp_send_json_error( array( 'message' => __( 'Please fill in all required fields.', 'sinding-booking' ) ) );
	}

	if ( ! is_email( $email ) ) {
		wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'sinding-booking' ) ) );
	}

	if ( empty( $session_ids ) ) {
		wp_send_json_error( array( 'message' => __( 'Please select at least one session type.', 'sinding-booking' ) ) );
	}

	$date_obj = DateTime::createFromFormat( 'Y-m-d', $booking_date );
	$today    = new DateTime( 'today midnight' );
	if ( ! $date_obj || $date_obj->setTime( 0, 0, 0 ) <= $today ) {
		wp_send_json_error( array( 'message' => __( 'Please select a future date.', 'sinding-booking' ) ) );
	}

	// --- Calculate server-side price to prevent tampering ------------------
	$catalogue      = sinding_booking_get_items();
	$all_items_map  = array();
	foreach ( $catalogue as $item ) {
		$all_items_map[ $item['id'] ] = $item;
	}

	$total           = 0;
	$session_labels  = array();
	$addon_labels    = array();

	foreach ( $session_ids as $id ) {
		if ( isset( $all_items_map[ $id ] ) ) {
			$total            += $all_items_map[ $id ]['price'];
			$session_labels[]  = $all_items_map[ $id ]['label'];
		}
	}
	foreach ( $addon_ids as $id ) {
		if ( isset( $all_items_map[ $id ] ) ) {
			$total          += $all_items_map[ $id ]['price'];
			$addon_labels[] = $all_items_map[ $id ]['label'];
		}
	}

	// --- Persist to database -----------------------------------------------
	$booking_id = sinding_booking_insert( array(
		'name'          => $name,
		'email'         => $email,
		'phone'         => $phone,
		'booking_date'  => $booking_date,
		'session_items' => $session_labels,
		'addon_items'   => $addon_labels,
		'total_price'   => $total,
	) );

	if ( ! $booking_id ) {
		wp_send_json_error( array( 'message' => __( 'Could not save your booking. Please try again.', 'sinding-booking' ) ) );
	}

	// --- Send emails -------------------------------------------------------
	sinding_booking_send_customer_email( $name, $email, $phone, $booking_date, $session_labels, $addon_labels, $total );
	sinding_booking_send_admin_email( $name, $email, $phone, $booking_date, $session_labels, $addon_labels, $total );

	wp_send_json_success( array( 'message' => __( 'Your booking has been received! Check your email for a confirmation.', 'sinding-booking' ) ) );
}

/**
 * Send confirmation email to the customer.
 *
 * @param string $name
 * @param string $email
 * @param string $phone
 * @param string $date
 * @param array  $sessions
 * @param array  $addons
 * @param float  $total
 */
function sinding_booking_send_customer_email( $name, $email, $phone, $date, $sessions, $addons, $total ) {
	$subject  = sprintf(
		/* translators: %s: site name */
		__( 'Booking Confirmation – %s', 'sinding-booking' ),
		get_bloginfo( 'name' )
	);

	$sessions_list = ! empty( $sessions ) ? implode( ', ', $sessions ) : __( 'None', 'sinding-booking' );
	$addons_list   = ! empty( $addons )   ? implode( ', ', $addons )   : __( 'None', 'sinding-booking' );

	$message = sprintf(
		/* translators: 1: customer name, 2: session items, 3: add-on items, 4: booking date, 5: total price, 6: site name */
		__(
			"Hi %1\$s,\n\nThank you for your booking! Here is a summary:\n\n" .
			"Session: %2\$s\nAdd-ons: %3\$s\nDate: %4\$s\nTotal: NOK %5\$s\n\n" .
			"We will be in touch to confirm your appointment.\n\nKind regards,\n%6\$s",
			'sinding-booking'
		),
		$name,
		$sessions_list,
		$addons_list,
		$date,
		number_format( $total, 0, '.', ' ' ),
		get_bloginfo( 'name' )
	);

	wp_mail( $email, $subject, $message );
}

/**
 * Send notification email to the site admin.
 *
 * @param string $name
 * @param string $email
 * @param string $phone
 * @param string $date
 * @param array  $sessions
 * @param array  $addons
 * @param float  $total
 */
function sinding_booking_send_admin_email( $name, $email, $phone, $date, $sessions, $addons, $total ) {
	$admin_email = get_option( 'admin_email' );
	$subject     = sprintf(
		/* translators: %s: site name */
		__( 'New Booking Request – %s', 'sinding-booking' ),
		get_bloginfo( 'name' )
	);

	$sessions_list = ! empty( $sessions ) ? implode( ', ', $sessions ) : __( 'None', 'sinding-booking' );
	$addons_list   = ! empty( $addons )   ? implode( ', ', $addons )   : __( 'None', 'sinding-booking' );

	$message = sprintf(
		/* translators: 1: name, 2: email, 3: phone, 4: session items, 5: add-on items, 6: date, 7: total price */
		__(
			"A new booking has been submitted.\n\n" .
			"Name: %1\$s\nEmail: %2\$s\nPhone: %3\$s\n" .
			"Session: %4\$s\nAdd-ons: %5\$s\nDate: %6\$s\nTotal: NOK %7\$s\n\n" .
			"Log in to your WordPress dashboard to manage bookings.",
			'sinding-booking'
		),
		$name,
		$email,
		$phone,
		$sessions_list,
		$addons_list,
		$date,
		number_format( $total, 0, '.', ' ' )
	);

	wp_mail( $admin_email, $subject, $message );
}
