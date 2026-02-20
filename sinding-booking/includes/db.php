<?php
/**
 * Database helpers – table creation and queries.
 *
 * @package Sinding_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Create (or upgrade) the bookings table.
 */
function sinding_booking_create_table() {
	global $wpdb;

	$table      = $wpdb->prefix . 'sinding_bookings';
	$charset    = $wpdb->get_charset_collate();
	$sql        = "CREATE TABLE {$table} (
		id            BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		name          VARCHAR(100)        NOT NULL,
		email         VARCHAR(100)        NOT NULL,
		phone         VARCHAR(30)         NOT NULL,
		booking_date  DATE                NOT NULL,
		session_items LONGTEXT            NOT NULL,
		addon_items   LONGTEXT            NOT NULL,
		total_price   DECIMAL(10,2)       NOT NULL DEFAULT 0.00,
		status        VARCHAR(20)         NOT NULL DEFAULT 'pending',
		created_at    DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id)
	) {$charset};";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
}

/**
 * Insert a new booking record.
 *
 * @param array $data Associative array of booking fields.
 * @return int|false Inserted row ID or false on failure.
 */
function sinding_booking_insert( array $data ) {
	global $wpdb;

	$table  = $wpdb->prefix . 'sinding_bookings';
	$result = $wpdb->insert(
		$table,
		array(
			'name'         => sanitize_text_field( $data['name'] ),
			'email'        => sanitize_email( $data['email'] ),
			'phone'        => sanitize_text_field( $data['phone'] ),
			'booking_date' => sanitize_text_field( $data['booking_date'] ),
			'session_items' => wp_json_encode( $data['session_items'] ),
			'addon_items'  => wp_json_encode( $data['addon_items'] ),
			'total_price'  => floatval( $data['total_price'] ),
			'status'       => 'pending',
		),
		array( '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s' )
	);

	return $result ? $wpdb->insert_id : false;
}

/**
 * Return all bookings ordered by created_at DESC.
 *
 * @return array Array of booking row objects.
 */
function sinding_booking_get_all() {
	global $wpdb;
	$table = $wpdb->prefix . 'sinding_bookings';
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC" );
}

/**
 * Return the catalogue of bookable items (sessions and add-ons).
 *
 * Each item has:
 *   id       – unique slug used in HTML / JS
 *   label    – display name
 *   price    – numeric price in NOK
 *   type     – 'session' (radio) | 'addon' (checkbox)
 *   description – short description shown on the card
 *
 * @return array
 */
function sinding_booking_get_items() {
	return array(
		array(
			'id'          => 'portrait',
			'label'       => 'Portrait Session',
			'price'       => 1500,
			'type'        => 'session',
			'description' => 'A personalised 1-hour studio or outdoor portrait session.',
		),
		array(
			'id'          => 'family',
			'label'       => 'Family Session',
			'price'       => 2000,
			'type'        => 'session',
			'description' => 'Capture precious family memories in a relaxed 1.5-hour session.',
		),
		array(
			'id'          => 'couples',
			'label'       => 'Couples Session',
			'price'       => 1750,
			'type'        => 'session',
			'description' => 'A romantic 1-hour session for couples – indoors or outdoors.',
		),
		array(
			'id'          => 'wedding',
			'label'       => 'Wedding Coverage',
			'price'       => 8000,
			'type'        => 'session',
			'description' => 'Full-day wedding coverage from preparations to first dance.',
		),
		array(
			'id'          => 'event',
			'label'       => 'Event / Occasion',
			'price'       => 3000,
			'type'        => 'session',
			'description' => 'Corporate events, birthdays, graduations, and more.',
		),
		array(
			'id'          => 'extra_hour',
			'label'       => 'Extra Hour',
			'price'       => 1000,
			'type'        => 'addon',
			'description' => 'Add an extra hour to your session.',
		),
		array(
			'id'          => 'digital_package',
			'label'       => 'Full Digital Package',
			'price'       => 1500,
			'type'        => 'addon',
			'description' => 'Receive all edited photos as high-resolution digital files.',
		),
		array(
			'id'          => 'photo_album',
			'label'       => 'Luxury Photo Album',
			'price'       => 2000,
			'type'        => 'addon',
			'description' => 'A premium 30-page printed photo album.',
		),
		array(
			'id'          => 'canvas_print',
			'label'       => 'Canvas Print (50×70 cm)',
			'price'       => 1200,
			'type'        => 'addon',
			'description' => 'Your favourite image professionally printed on canvas.',
		),
		array(
			'id'          => 'rush_editing',
			'label'       => 'Rush Editing (48 hrs)',
			'price'       => 750,
			'type'        => 'addon',
			'description' => 'Receive your edited photos within 48 hours.',
		),
	);
}
