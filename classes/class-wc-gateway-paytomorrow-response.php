<?php
/**
 * WC_Gateway_Paytomorrow_Response Abstract Class
 *
 * @category Class
 * @package  paytomorrow
 * @author   PayTomorrow
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://www.hashbangcode.com/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles Responses.
 */
abstract class WC_Gateway_Paytomorrow_Response {


	/**
	 * Get the order from the PayTomorrow 'Custom' variable.
	 *
	 * @param string $uuid Order ID.
	 * @return bool|WC_Order object
	 */
	protected function get_paytomorrow_order( $uuid ) {
		$order_id = $this->get_order_id_by_uuid( $uuid );

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			paytomorrow_log( 'Error: cannot find order.' );
			return false;
		}

		paytomorrow_log( array( $order ) );

		return $order;
	}

	/**
	 * Complete order, add transaction ID and note.
	 *
	 * @param string $uuid Application UUID.
	 */
	protected function get_order_id_by_uuid( $uuid ) {
		global $wpdb;
		$meta = $wpdb->get_results( 'SELECT post_id FROM `' . $wpdb->postmeta . "` WHERE meta_key='" . esc_sql( PT_META_KEY ) . "' AND meta_value='" . esc_sql( $uuid ) . "' LIMIT 1" );
		if ( is_array( $meta ) && ! empty( $meta ) && isset( $meta[0] ) ) {
			$meta = $meta[0];
		}
		if ( is_object( $meta ) ) {
			return $meta->post_id;
		} else {
			return false;
		}
	}

	/**
	 * Complete order, add transaction ID and note.
	 *
	 * @param WC_Order $order WooCommerce Order.
	 * @param string   $txn_id Transaction ID.
	 * @param string   $note Order notes.
	 */
	protected function payment_complete( $order, $txn_id = '', $note = '' ) {
		paytomorrow_log( 'PAYMENT COMPLETE!!!' );
		$order->add_order_note( $note );
		$order->payment_complete( $txn_id );
	}

	/**
	 * Hold order and add note.
	 *
	 * @param WC_Order $order WooCommerce Order.
	 * @param string   $reason Reason the order is held.
	 */
	protected function payment_on_hold( $order, $reason = '' ) {
		global $woocommerce;
		$order->update_status( 'on-hold', $reason );
		wc_reduce_stock_levels( $order->get_id() );
		$woocommerce->cart->empty_cart();
	}

	/**
	 * Cancel order and add note.
	 *
	 * @param WC_Order $order WooCommerce Order.
	 * @param string   $reason Reason the order is held.
	 */
	protected function payment_cancelled( $order, $reason = '' ) {
		//global $woocommerce;
		//$order->update_status( 'failed', $reason );
		$order->update_status( 'cancelled', $reason );
		//wc_reduce_stock_levels( $order->get_id() );
		//$woocommerce->cart->empty_cart();
		//$order->status = 'cancelled';
	}
}
