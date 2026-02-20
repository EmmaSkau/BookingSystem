<?php
/**
 * WordPress admin page for managing bookings.
 *
 * @package Sinding_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', 'sinding_booking_admin_menu' );

/**
 * Register the admin menu page.
 */
function sinding_booking_admin_menu() {
	add_menu_page(
		__( 'Bookings', 'sinding-booking' ),
		__( 'Bookings', 'sinding-booking' ),
		'manage_options',
		'sinding-bookings',
		'sinding_booking_admin_page',
		'dashicons-calendar-alt',
		30
	);
}

/**
 * Render the admin bookings list page.
 */
function sinding_booking_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$bookings = sinding_booking_get_all();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Bookings', 'sinding-booking' ); ?></h1>

		<?php if ( empty( $bookings ) ) : ?>
			<p><?php esc_html_e( 'No bookings found.', 'sinding-booking' ); ?></p>
		<?php else : ?>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'ID', 'sinding-booking' ); ?></th>
					<th><?php esc_html_e( 'Name', 'sinding-booking' ); ?></th>
					<th><?php esc_html_e( 'Email', 'sinding-booking' ); ?></th>
					<th><?php esc_html_e( 'Phone', 'sinding-booking' ); ?></th>
					<th><?php esc_html_e( 'Date', 'sinding-booking' ); ?></th>
					<th><?php esc_html_e( 'Session', 'sinding-booking' ); ?></th>
					<th><?php esc_html_e( 'Add-ons', 'sinding-booking' ); ?></th>
					<th><?php esc_html_e( 'Total (NOK)', 'sinding-booking' ); ?></th>
					<th><?php esc_html_e( 'Submitted', 'sinding-booking' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $bookings as $booking ) : ?>
				<?php
				$session_items = json_decode( $booking->session_items, true );
				$addon_items   = json_decode( $booking->addon_items, true );
				$sessions_str  = is_array( $session_items ) ? implode( ', ', $session_items ) : $booking->session_items;
				$addons_str    = is_array( $addon_items )   ? implode( ', ', $addon_items )   : $booking->addon_items;
				?>
				<tr>
					<td><?php echo esc_html( $booking->id ); ?></td>
					<td><?php echo esc_html( $booking->name ); ?></td>
					<td><a href="mailto:<?php echo esc_attr( $booking->email ); ?>"><?php echo esc_html( $booking->email ); ?></a></td>
					<td><?php echo esc_html( $booking->phone ); ?></td>
					<td><?php echo esc_html( $booking->booking_date ); ?></td>
					<td><?php echo esc_html( $sessions_str ); ?></td>
					<td><?php echo esc_html( $addons_str ); ?></td>
					<td><?php echo esc_html( number_format( (float) $booking->total_price, 0, '.', ' ' ) ); ?></td>
					<td><?php echo esc_html( $booking->created_at ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>
	</div>
	<?php
}
