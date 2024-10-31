<?php
/**
 * WC_Gateway_Paytomorrow_IPN_Handler Class
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

require_once dirname(__FILE__) . '/class-wc-gateway-paytomorrow-response.php';

/**
 * Handles responses from PayTomorrow IPN.
 */
class WC_Gateway_Paytomorrow_IPN_Handler extends WC_Gateway_Paytomorrow_Response {

	/**
	 * Receiver email address to validate.
	 *
	 * @var string
	 */
	protected $receiver_email;
	/**
	 * Validate Instant Payment Notification.
	 *
	 * @var string
	 */
	protected $validateipn_postfix;
	/**
	 * PayTomorrow API URL.
	 *
	 * @var string
	 */
	protected $api_url;

	/**
	 * Constructor.
	 *
	 * @param string $receiver_email Receiver email to validate.
	 * @param string $api_url PayTomorrow API url.
	 * @param string $validateipn_posfix IPN Posfix to validate.
	 */
	public function __construct( $receiver_email = '', $api_url, $validateipn_posfix ) {
		add_action( 'woocommerce_api_wc_gateway_paytomorrow', array( $this, 'check_response' ) );
		add_action( 'valid_paytomorrow_standard_ipn_request', array( $this, 'valid_response' ) );
        add_action( 'woocommerce_api_wc_gateway_paytomorrow_track', array( $this, 'find_order_tracking') );

		$this->receiver_email      = $receiver_email;
		$this->api_url             = $api_url;
		$this->validateipn_postfix = $validateipn_posfix;
	}

	/**
	 * Handle Paytomorrow Postback.
	 *
	 * @throws Exception Invalid request exceptions.
	 */
	public function check_response() {
		paytomorrow_log( 'ENTERING check_response' );

		try {

			if ( empty( $_POST ) ) {
				throw new Exception( esc_html__( 'Empty POST data.', 'paytomorrow' ) );
			}

            $posted = array(
                'uuid' => sanitize_text_field( $_POST['uuid'] ),
                'txn_type' => sanitize_text_field( $_POST['txn_type'] ),
                'payment_status' => sanitize_text_field( $_POST['payment_status'] ),
                'pt_currency' => sanitize_text_field( $_POST['pt_currency'] ),
                'pt_amount' => sanitize_text_field( $_POST['pt_amount'] ),
                'pt_ammount' => sanitize_text_field( $_POST['pt_ammount'] ),
                'pt_id' => sanitize_text_field( $_POST['pt_id'] ),
                'pt_app_status' => sanitize_text_field( $_POST['pt_app_status'] ),
                'platform' => sanitize_text_field( $_POST['platform'] ),
                'lender' => sanitize_text_field( $_POST['lender'] ),
                'tax_exempt' => sanitize_text_field( $_POST['tax_exempt'] ),
                'order_id' => sanitize_text_field( $_POST['order_id'] ),
            );

			if ( $this->validate_ipn( $posted['uuid'] ) ) {

				do_action( 'valid_paytomorrow_standard_ipn_request', $posted );
				exit;
			} else {
				paytomorrow_log( 'IPN request is NOT valid according to PayTomorrow.' );
				throw new Exception( esc_html__( 'Invalid IPN request.', 'paytomorrow' ) );
			}
		} catch ( Exception $e ) {
			wp_die( esc_html( $e->getMessage() ), esc_html__( 'PayTomorrow IPN Request Failure', 'paytomorrow' ), array( 'response' => 500 ) );
		}
	}

	/**
	 * There was a valid response.
	 *
	 * @param array $posted Post data after wp_unslash.
	 */
	public function valid_response( $posted ) {

		paytomorrow_log( 'ENTERING valid_response' );
		paytomorrow_log( array( 'posted' => $posted ) );
		paytomorrow_log( 'UUID: ' . $posted['uuid'] );
		$order = $this->get_paytomorrow_order( $posted['uuid'] );
		if ( isset( $order ) && $order->get_payment_method() == 'paytomorrow' ) {

			// Lowercase returned variables.
			$posted['payment_status'] = strtolower( $posted['payment_status'] );

			// Sandbox fix.
			if ( isset( $posted['test_ipn'] ) && 1 === $posted['test_ipn'] && 'pending' === $posted['payment_status'] ) {
				$posted['payment_status'] = 'completed';
			}

			paytomorrow_log( 'Found order #' . $order->get_id() );
			paytomorrow_log( 'Payment status: ' . $posted['payment_status'] );

			if ( method_exists( $this, 'payment_status_' . $posted['payment_status'] ) ) {
				call_user_func( array( $this, 'payment_status_' . $posted['payment_status'] ), $order, $posted );
			}
		} else {
			paytomorrow_log( 'order not found' );
		}
	}

	/**
	 * Check PayTomorrow IPN validity.
	 *
	 * @param string $uuid The uuid from pay-tomorrow.
	 */
	public function validate_ipn( $uuid ) {
		paytomorrow_log( 'Checking IPN response is valid' );

		// Get received values from post data.
		$validate_ipn  = array(
			'cmd'  => '_validate-me',
			'uuid' => $uuid,
		);

		// Send back post vars to paytomorrow.
		$params = array(
			'headers'     => array( 'Content-Type' => 'application/json' ),
			'body'        => wp_json_encode( $validate_ipn ),
			'timeout'     => 60,
			'httpversion' => '1.1',
			'compress'    => false,
			'decompress'  => false,
			'user-agent'  => 'WooCommerce/' . WC()->version,
		);

		paytomorrow_log( 'ENTERING validate_ipn' );
		paytomorrow_log( $params );

		$request_url = $this->api_url . $this->validateipn_postfix;

		// Post back to get a response.
		if ( is_ssl() ) {
			paytomorrow_log( 'ssl call' );
			$response = wp_safe_remote_post( $request_url, $params );
		} else {
			paytomorrow_log( 'NO ssl call' );
			$response = wp_remote_post( $request_url, $params );
		}

		paytomorrow_log( $response );

		paytomorrow_log( 'IPN Request: ' . print_r( $params, true ) );
		paytomorrow_log( 'IPN Response: ' . print_r( $response, true ) );

		// Check to see if the request was valid.
		if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 && strstr( $response['body'], 'VERIFIED_PT' ) ) {
			paytomorrow_log( 'Received valid response from PayTomorrow' );
			return true;
		}

		paytomorrow_log( 'Received invalid response from PayTomorrow' );

		if ( is_wp_error( $response ) ) {
			paytomorrow_log( 'Error response: ' . $response->get_error_message() );
		}

		return false;
	}

    public function find_order_tracking() {

        $orderId = wp_unslash( $_GET['order_id'] );
        $order = wc_get_order($orderId);

        if(!empty($order)) {

            $trackingData = $order->get_meta('_wc_shipment_tracking_items'); // Find trackingData field created by AST plugin

            if(empty($trackingData) ){
                paytomorrow_log('No AST tracking data found, checking custom properties');

                $trackingNo = $order->get_meta('tracking_number');
                $carrier = $order->get_meta('carrier');

                if(!is_null($trackingNo)){
                    $trackingData = [['tracking_number' => $trackingNo,'tracking_provider' => $carrier]] ;
                }
            }

            wp_send_json_success($trackingData, 200);
        }
        wp_send_json([], 404);
    }


    /**
	 * Check for a valid transaction type.
	 *
	 * @param string $txn_type The type of the transaction.
	 */
	protected function validate_transaction_type( $txn_type ) {
		$accepted_types = array( 'cart', 'instant', 'express_checkout', 'web_accept', 'masspay', 'send_money' );

		if ( ! in_array( strtolower( $txn_type ), $accepted_types, true ) ) {
			paytomorrow_log( 'Aborting, Invalid type:' . $txn_type );
			exit;
		}
	}

	/**
	 * Check currency from IPN matches the order.
	 *
	 * @param WC_Order $order The order object.
	 * @param string   $currency The currency used at purchase.
	 */
	protected function validate_currency( $order, $currency ) {
		if ( $order->get_currency() !== $currency ) {
			paytomorrow_log( 'Payment error: Currencies do not match (sent "' . $order->get_currency() . '" | returned "' . $currency . '")' );

			/* translators: %s: Currency */
			$translated_text = __( 'Validation error: PayTomorrow currencies do not match (code %s).', 'wc_paytomorrow' );

			// Put this order on-hold for manual checking.
			$order->update_status( 'on-hold', sprintf( $translated_text, $currency ) );
			exit;
		}
	}

	/**
	 * Check payment amount from IPN matches the order.
	 *
	 * @param WC_Order $order The order object.
	 * @param int      $amount The amount.
	 */
	public function validate_amount( $order, $amount ) {
		if ( number_format( $order->get_total(), 2, '.', '' ) != number_format( $amount, 2, '.', '' ) ) {
			paytomorrow_log( 'Payment error: Amounts do not match (gross ' . $amount . ')' );
			paytomorrow_log( 'Payment error: Amounts do not match (gross ' . $amount . ')' );

			/* translators: %s: Amount */
			$translated_text = __( 'Validation error: PayTomorrow amounts do not match (gross %s).', 'wc_paytomorrow' );

			// Put this order on-hold for manual checking.
			$order->update_status( 'on-hold', sprintf( $translated_text, $amount ) );
			exit;
		}
	}

	/**
	 * Check receiver email from PayTomorrow. If the receiver email in the IPN is different than what is stored in.
	 * WooCommerce -> Settings -> Checkout -> PayTomorrow, it will log an error about it.
	 *
	 * @param WC_Order $order The order object.
	 * @param string   $receiver_email  Receiver email.
	 */
	protected function validate_receiver_email( $order, $receiver_email ) {
		if ( strcasecmp( trim( $receiver_email ), trim( $this->receiver_email ) ) != 0 ) {
			paytomorrow_log( "IPN Response is for another account: {$receiver_email}. Your email is {$this->receiver_email}" );

			/* translators: %s: Receiver email */
			$translated_text = __( 'Validation error: PayTomorrow IPN response from a different email address (%s).', 'wc_paytomorrow' );

			// Put this order on-hold for manual checking.
			$order->update_status( 'on-hold', sprintf( $translated_text, $receiver_email ) );
			exit;
		}
	}



	/**
	 * Handle a completed payment.
	 *
	 * @param WC_Order $order  The order object.
	 * @param array    $posted Posted object.
	 */
	protected function payment_status_completed( $order, $posted ) {
		if ( $order->has_status( 'completed' ) ) {
			paytomorrow_log( 'Aborting, Order #' . $order->get_id() . ' is already complete.' );
			exit;
		}

		$this->validate_currency( $order, $posted['pt_currency'] );
		$this->save_paytomorrow_meta_data( $order, $posted );

		paytomorrow_log( '$posted[\'payment_status\'] : ' . $posted['payment_status'] );
		if ( 'completed' === $posted['payment_status'] ) {
			paytomorrow_log( 'payment_status_completed!!!' );
            if('yes' === $posted['tax_exempt']) {
                $order->add_meta_data( 'is_vat_exempt', 'yes', true );
                $order->calculate_totals();
            } else {
                $this->validate_amount( $order, $posted['pt_ammount'] );
            }
			$this->payment_complete( $order, ( ! empty( $posted['trans_id'] ) ? wc_clean( $posted['trans_id'] ) : '' ), __( 'IPN payment completed', 'wc_paytomorrow' ) );

		}
        else if ('S' === $posted['pt_app_status'] && 'pending' === $posted['payment_status']) {
            $this->validate_amount( $order, $posted['pt_ammount'] );
            paytomorrow_log( 'Change the order status to processing');
            global $woocommerce;
            $order->update_status( 'processing', wc_clean( $posted['payment_status'] ));
            wc_reduce_stock_levels( $order->get_id() );
            $woocommerce->cart->empty_cart();
        }
		else {
            $this->validate_amount( $order, $posted['pt_ammount'] );
			paytomorrow_log( 'Payment on hold' );
			/* translators: %s: Pending Reason */
			$translated_text = __( 'Payment pending: %s', 'wc_paytomorrow' );
			$this->payment_on_hold( $order, sprintf( $translated_text, $posted['pending_reason'] ) );
		}
	}

	/**
	 * Handle a pending payment.
	 *
	 * @param WC_Order $order The order object.
	 * @param array    $posted Posted Object.
	 */
	protected function payment_status_pending( $order, $posted ) {
		$this->payment_status_completed( $order, $posted );
	}

	/**
	 * Handle a failed payment.
	 *
	 * @param WC_Order $order The order object.
	 * @param array    $posted Posted Object.
	 */
	protected function payment_status_failed( $order, $posted ) {
		/* translators: %s: Payment Status */
		$translated_text = __( 'Payment %s via IPN.', 'wc_paytomorrow' );
		$order_status = $order->get_status(); 
		if ('on-hold' == $order_status) {   
			$items = $order->get_items();
			foreach ( $items as $item ) {
				$product_id = $item['product_id'];
				$product_instance = wc_get_product($product_id);
				wc_update_product_stock($product_instance, $item['quantity'], 'increase');
			}

		 }   
		$order->update_status( 'failed', sprintf( $translated_text, wc_clean( $posted['payment_status'] ) ) );
	}

	/**
	 * Handle a denied payment.
	 *
	 * @param WC_Order $order The order object.
	 * @param array    $posted Posted Object.
	 */
	protected function payment_status_denied( $order, $posted ) {
		$this->payment_status_failed( $order, $posted );
	}

	/**
	 * Handle an expired payment.
	 *
	 * @param WC_Order $order The order object.
	 * @param array    $posted Posted Object.
	 */
	protected function payment_status_expired( $order, $posted ) {
		$this->payment_status_failed( $order, $posted );
	}

	/**
	 * Handle a voided payment.
	 *
	 * @param WC_Order $order The order object.
	 * @param array    $posted Posted Object.
	 */
	protected function payment_status_voided( $order, $posted ) {
		$this->payment_status_failed( $order, $posted );
	}

	/**
	 * Handle a refunded order.
	 *
	 * @param WC_Order $order The order object.
	 * @param array    $posted Posted Object.
	 */
	protected function payment_status_refunded( $order, $posted ) {
		// Only handle full refunds, not partial.
		if ( $order->get_total() == ( $posted['pt_ammount'] * -1 ) ) {

			/* translators: %s: Payment Status */
			$translated_text = __( 'Payment %s via IPN.', 'wc_paytomorrow' );
			// Mark order as refunded.
			$order->update_status( 'refunded', sprintf( $translated_text, strtolower( $posted['payment_status'] ) ) );

			/* translators: %s: Link */
			$translated_email_subject = __( 'Payment for order %s refunded', 'wc_paytomorrow' );
			/* translators: %s: Message */
			$translated_email_message = __( 'Order #%1$s has been marked as refunded - PayTomorrow reason code: %2$s', 'wc_paytomorrow' );

			$this->send_ipn_email_notification(
				sprintf( $translated_email_subject, '<a class="link" href="' . esc_url( admin_url( 'post.php?post=' . $order->get_id() . '&action=edit' ) ) . '">' . $order->get_order_number() . '</a>' ),
				sprintf( $translated_email_message, $order->get_order_number(), $posted['reason_code'] )
			);
		}
	}

	/**
	 * Handle a reveral.
	 *
	 * @param WC_Order $order The order object.
	 * @param array    $posted Posted Object.
	 */
	protected function payment_status_reversed( $order, $posted ) {
		/* translators: %s: Payment Status */
		$translated_text = __( 'Payment %s via IPN.', 'wc_paytomorrow' );
		$order->update_status( 'on-hold', sprintf( $translated_text, wc_clean( $posted['payment_status'] ) ) );
		/* translators: %s: Link */
		$translated_email_subject = __( 'Payment for order %s reversed', 'wc_paytomorrow' );
		/* translators: %s: Message */
		$translated_email_message = __( 'Order #%1$s has been marked on-hold due to a reversal - PayTomorrow reason code: %2$s', 'wc_paytomorrow' );
		$this->send_ipn_email_notification(
			sprintf( $translated_email_subject, '<a class="link" href="' . esc_url( admin_url( 'post.php?post=' . $order->get_id() . '&action=edit' ) ) . '">' . $order->get_order_number() . '</a>' ),
			sprintf( $translated_email_message, $order->get_order_number(), wc_clean( $posted['reason_code'] ) )
		);
	}

	/**
	 * Handle a cancelled reveral.
	 *
	 * @param WC_Order $order The order object.
	 * @param array    $posted Posted Object.
	 */
	protected function payment_status_canceled_reversal( $order, $posted ) {
		/* translators: %s: Link */
		$translated_email_subject = __( 'Reversal cancelled for order #%s', 'wc_paytomorrow' );
		/* translators: %s: Message */
		$translated_email_message = __( 'Order #%1$s has had a reversal cancelled. Please check the status of payment and update the order status accordingly here: %2$s', 'wc_paytomorrow' );
		$this->send_ipn_email_notification(
			sprintf( $translated_email_subject, $order->get_order_number() ),
			sprintf( $translated_email_message, $order->get_order_number(), esc_url( admin_url( 'post.php?post=' . $order->get_id() . '&action=edit' ) ) )
		);
	}

	/**
	 * Cancel Order .
	 *
	 * @param WC_Order $order The order object.
	 * @param array    $posted Posted Object.
	 */
	protected function payment_status_cancelled( $order, $posted ) {
		paytomorrow_log( 'Payment  cancelled' );
		$order_status = $order->get_status();
		if ('on-hold' == $order_status) {   
			paytomorrow_log( 'increasing the stock because we are cancelling an order which haave tha status on-hold' );
			$items = $order->get_items();
			foreach ( $items as $item ) {
				$product_id = $item['product_id'];
				$product_instance = wc_get_product($product_id);
				wc_update_product_stock($product_instance, $item['quantity'], 'increase');
			}
		 } 
		$this->payment_cancelled( $order, $posted['pending_reason'] );
	}

	/**
	 * Save important data from the IPN to the order.
	 *
	 * @param WC_Order $order The order object.
	 * @param array    $posted Posted Object.
	 */
	protected function save_paytomorrow_meta_data( $order, $posted ) {
		if ( ! empty( $posted['payer_email'] ) ) {
			update_post_meta( $order->get_id(), 'Payer PayTomorrow address', wc_clean( $posted['payer_email'] ) );
		}
		if ( ! empty( $posted['first_name'] ) ) {
			update_post_meta( $order->get_id(), 'Payer first name', wc_clean( $posted['first_name'] ) );
		}
		if ( ! empty( $posted['last_name'] ) ) {
			update_post_meta( $order->get_id(), 'Payer last name', wc_clean( $posted['last_name'] ) );
		}
		if ( ! empty( $posted['payment_type'] ) ) {
			update_post_meta( $order->get_id(), 'Payment type', wc_clean( $posted['payment_type'] ) );
		}
	}

	/**
	 * Send a notification to the user handling orders.
	 *
	 * @param string $subject Email Subject.
	 * @param string $message Email message.
	 */
	protected function send_ipn_email_notification( $subject, $message ) {
		$new_order_settings = get_option( 'woocommerce_new_order_settings', array() );
		$mailer             = WC()->mailer();
		$message            = $mailer->wrap_message( $subject, $message );

		$mailer->send( ! empty( $new_order_settings['recipient'] ) ? $new_order_settings['recipient'] : get_option( 'admin_email' ), strip_tags( $subject ), $message );
	}
}
