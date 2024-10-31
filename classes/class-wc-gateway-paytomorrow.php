<?php
/**
 * PayTomorrow Standard Payment Gateway.
 *
 * Provides a PayTomorrow Standard Payment Gateway.
 *
 * @class       WC_Gateway_Paytomorrow
 * @extends     WC_Payment_Gateway
 * @version     3.0.1
 * @package     WooCommerce/Classes/Payment
 * @author      WooThemes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Gateway_Paytomorrow Class.
 */
class WC_Gateway_Paytomorrow extends WC_Payment_Gateway {

	/**
	 * Whether or not logging is enabled
	 *
	 * @var bool
	 */
	public static $log_enabled = false;

	/**
	 * Logger Instance
	 *
	 * @var WC_Logger
	 */
	public static $log = false;

	/**
	 * OAuth API endpoint
	 *
	 * @var string
	 */
	public static $oauth_postfix = '/api/uaa/oauth/token';

	/**
	 * WooCommerce Endpoint
	 *
	 * @var string
	 */
	public static $checkout_postfix = '/api/application/ecommerce/orders';

	/**
	 * IPN Validation Endpoint
	 *
	 * @var string
	 */
	public static $validateipn_postfix = '/api/application-validation/validateipn';


	/**
	 * API URL
	 *
	 * @var string
	 */
	public static $api_url;

    public static $checkout_status;

    const PAYTOMORROW_PAYMENT_METHOD = 'paytomorrow';
    const BASE_ECOMMERCE_URL = '/api/ecommerce/application/';

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
        include_once dirname(__FILE__) . '/StatusResponse.php';
		$this->id                = 'paytomorrow';
		$this->has_fields        = true;
		$this->order_button_text = __( 'Proceed to PayTomorrow', 'wc_paytomorrow' );
		$this->method_title      = __( 'PayTomorrow', 'wc_paytomorrow' );
		/* translators: %1$s: '<a href="' . admin_url( 'admin.php?page=wc-status' ) . '">' %2$s: '</a>' */
            $this->method_description = __( 'PayTomorrow, consumer financing for all credit types with one easy application', 'wc_paytomorrow' );
		$this->supports           = array(
			'products',
            'refunds'
		);

		$this->method_description = sprintf( $this->method_description, '<a href="' . admin_url( 'admin.php?page=wc-status' ) . '">', '</a>' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables.
//		$this->title       =  $this->get_option( 'pt_title' );
		self::$api_url     = $this->get_option( 'api_url' );
		$this->description = '';

		$this->debug          = true;
		$this->email          = $this->get_option( 'email' );
		$this->receiver_email = $this->get_option( 'receiver_email', $this->email );
		self::$log_enabled = $this->debug;

		if ( ! $this->is_valid_for_use() ) {
			$this->enabled = 'no';
		} else {
			include_once dirname(__FILE__) . '/class-wc-gateway-paytomorrow-ipn-handler.php';
			new WC_Gateway_Paytomorrow_IPN_Handler( $this->receiver_email, self::$api_url, self::$validateipn_postfix );
		}

            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
    		add_action( 'woocommerce_before_checkout_form', array( $this, 'add_jscript' ) );
            add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 10, 2 );
            add_action( 'woocommerce_order_status_cancelled', array( $this, 'wc_paytomorrow_cancel_order' ), 10, 1 );
            add_action( 'woocommerce_order_status_completed', array( $this, 'wc_paytomorrow_completed_order' ), 10, 1 );
            add_action('woocommerce_thankyou', array( $this, 'wc_paytomorrow_checkout_complete' ));
	}

        public function add_jscript() {

            if ( isset($_GET['paytomorrow']) && $_GET['paytomorrow'] == 1 && isset($_GET['order'])) {
                $order_id = sanitize_text_field($_GET['order']);
                paytomorrow_log("Order Id :".$order_id);
                paytomorrow_log(get_post_meta($order_id, PT_META_RETURN_URL));
                wp_enqueue_script( 'woocommerce_paytomorrow_popup', plugins_url( 'popup/js/pt-popup.js', __FILE__ ) );
        		wp_enqueue_style( 'pt-mpe', plugins_url( 'popup/css/pt-popup.css', __FILE__ ) );
        		wp_localize_script( 'woocommerce_paytomorrow_popup', 'popupUrl', get_post_meta($order_id, PT_META_URL));
                wp_localize_script( 'woocommerce_paytomorrow_popup', 'popupCloseUrl', get_permalink( wc_get_page_id( 'checkout' )) );
                wp_localize_script( 'woocommerce_paytomorrow_popup', 'popupSuccessUrl', get_post_meta($order_id, PT_META_RETURN_URL) );
            }
        }

	/**
	 * Logging method.
	 *
	 * @param string $message Message to log.
	 */
	public static function log( $message ) {
		if ( self::$log_enabled ) {
			if ( empty( self::$log ) ) {
				self::$log = new WC_Logger();
			}
			self::$log->add( 'paytomorrow', $message );
		}
	}

	/**
	 * Get gateway icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		$icon_html = '';
		$icon      = (array) $this->get_icon_image( WC()->countries->get_base_country() );
		
		$icon_html = '<a target="_blank">';
		foreach ( $icon as $i ) {
            $icon_html .= '<img src="'.plugins_url('assets/images/PTLogo.png', __DIR__ ).'" alt="' . esc_attr__( 'PayTomorrow Acceptance Mark', 'wc_paytomorrow' ) .'" style = "max-height : none ; height : 40px " />';
		}
		$icon_html .='</a>';

		return apply_filters( 'woocommerce_gateway_icon', $icon_html, $this->id );
	}

    /**
     * Get gateway fields.
     *
     * @return string
     */
    public function payment_fields()
    {
        $this->title       =  '';
        $description_html = 'PayTomorrow offers Fair Financing for All Credit Types. Simply select PayTomorrow, supply some basic information via our secure application process and get instantly approved to complete your purchase. Applying to PayTomorrow will not affect your credit score.';

        echo esc_html($description_html);

    }

	/**
	 * Get the link for an icon based on country.
	 *
	 * @param  string $country Country.
	 * @return string
	 */
	protected function get_icon_url( $country ) {
		$url = 'https://www.paytomorrow.com/';

		return $url;
	}

	/**
	 * Get PayTomorrow images for a country.
	 *
	 * @param  string $country Country.
	 * @return array of image URLs
	 */
	protected function get_icon_image( $country ) {
		$icon = WC_HTTPS::force_https_url(plugins_url( 'assets/images/pay-tomorrow.png'));
		return apply_filters( 'woocommerce_paytomorrow_icon', $icon );
	}

	/**
	 * Check if this gateway is enabled and available in the user's country.
	 *
	 * @return bool
	 */
	public function is_valid_for_use() {
		return in_array( get_woocommerce_currency(), apply_filters( 'woocommerce_paytomorrow_supported_currencies', array( 'USD' ) ) );
	}

	/**
	 * Admin Panel Options.
	 * - Options for bits like 'title' and availability on a country-by-country basis.
	 *
	 * @since 1.0.0
	 */
	public function admin_options() {
		if ( $this->is_valid_for_use() ) {
			parent::admin_options();
		} else {
			esc_html_e( '<div class="inline error"><p><strong>Gateway Disabled</strong>: PayTomorrow does not support your store currency.</p></div>', 'wc_paytomorrow' );
		}
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = include 'settings-paytomorrow.php';
	}

	/**
	 * Get the transaction URL.
	 *
	 * @param  WC_Order $order The order.
	 * @return string
	 */
	public function get_transaction_url( $order ) {
        if (str_contains(self::$api_url, 'staging')) {
            $this->view_transaction_url = str_replace('api', 'merchant', self::$api_url) .'/merchant/applications/details/token/%s';
//            $this->view_transaction_url = 'https://merchant-staging.paytomorrow.com'.'?application-token=%s';
        } else {
            $this->view_transaction_url = str_replace('api', 'merchant', self::$api_url) .'/merchant/applications/details/token/%s';
        }
		return parent::get_transaction_url( $order );
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param  int $order_id Order ID.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$this->init_api();

		include_once dirname(__FILE__) . '/class-wc-gateway-paytomorrow-request.php';
		include_once dirname(__FILE__) . '/class-wc-gateway-paytomorrow-api-handler.php';

		$order               = wc_get_order( $order_id );
		$paytomorrow_request = new WC_Gateway_Paytomorrow_Request( $this );

		// $request_url = 'http://localhost:9000/api/application/checkWoo';
		$request_url       = self::$api_url . self::$checkout_postfix;
		$ecommerceresponse = array(
			'url'   => '',
			'token' => '',
		);
		$body_request      = array_merge( WC_Gateway_Paytomorrow_API_Handler::get_capture_request( $order ), $paytomorrow_request->get_paytomorrow_order_body_args( $order ) );
		$request           = array(
			'method'      => 'POST',
			'headers'     => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'bearer ' . WC_Gateway_Paytomorrow_API_Handler::$api_token,
			),
			'body'        => wp_json_encode( $body_request ),
			'timeout'     => 70,
			'user-agent'  => 'WooCommerce/' . WC()->version,
			'httpversion' => '1.1',
			'sslverify'   => false,
		);

		WC_Gateway_Paytomorrow::log(
            (string)array(
                'requestUrl' => $request_url,
                'request' => $request,
                '$bodyRequest' => wp_json_encode($body_request),
            )
		);

		if ( is_ssl() ) {
			$raw_response = wp_safe_remote_post( $request_url, $request );
		} else {
			$raw_response = wp_remote_post( $request_url, $request );
		}

        // Default redirect url
        $redirect_url = add_query_arg(
                array(
                    'order' => $order_id
                ), get_permalink( wc_get_page_id( 'checkout' ) )
            );

          paytomorrow_log("Create Order Response Status :".wp_remote_retrieve_response_code($raw_response));

		if ( wp_remote_retrieve_response_code($raw_response) !== 200 ) {
			paytomorrow_log( array( 'Create Order Response' => $raw_response ) );
            wc_add_notice( 'We are currently experiencing problems trying to connect to this payment gateway. Sorry for the inconvenience.','error' );
		} else {
			$ecommerceresponse = json_decode( wp_remote_retrieve_body( $raw_response ) );
			update_post_meta( $order_id, PT_META_KEY, $ecommerceresponse->token );
			update_post_meta( $order_id, PT_META_URL, $ecommerceresponse->url );
			update_post_meta( $order_id, PT_META_RETURN_URL, $body_request['returnUrl']);
			$redirect_url = add_query_arg(
                array(
                    'paytomorrow' => '1',
                    'order' => $order_id
                ), get_permalink( wc_get_page_id( 'checkout' ) )
            );
		}

        // If no tracking plugin, add custom tracking fields to order
        if(!is_plugin_active( 'woo-advanced-shipment-tracking/woocommerce-advanced-shipment-tracking.php')){
            update_post_meta($order_id, 'carrier', '');
            update_post_meta($order_id, 'tracking_number', '');
        }

		return array(
			'result'   => 'success',
			'redirect' => $redirect_url,
		);
	}

	/**
	 * Can the order be refunded via PayTomorrow?
	 *
	 * @param  WC_Order $order Order object.
	 * @return bool
	 */
	public function can_refund_order( $order ) {
		return $order && $order->get_transaction_id();
	}

    /**
     *  Paytomorrow plugin successfully completes checkout
     *  as well as when the user completes the flow.
     *  @param string $order_id order id.
     */
    public function wc_paytomorrow_checkout_complete( $order_id ) {
        paytomorrow_log("start checkout :" .date("h:i:sa.u"));
        $order        = new WC_Order( $order_id );
        $payment_method = $order->get_payment_method();

        paytomorrow_log('Payment Type : '.$payment_method);
        paytomorrow_log('transaction Id : '.get_post_meta( $order_id, PT_META_KEY)[0]);
        paytomorrow_log('Order : '.$order);

        if ($payment_method ==  self::PAYTOMORROW_PAYMENT_METHOD) {
            if(!$order->get_transaction_id() ) {
                $sync = json_encode(['order_id' => $order_id]);
                $transaction_id = get_post_meta( $order_id, PT_META_KEY)[0];
                paytomorrow_log('Transaction Id : '.$transaction_id);
                if ($transaction_id) {
                    if(!$order->get_transaction_id() ) {
                        $this->set_woo_order_transaction_id( $order, $transaction_id );
                    }
                    paytomorrow_log("before status :" .date("h:i:sa.u"));
                    $status_response = $this->get_status($order);
                    paytomorrow_log("end status :" .date("h:i:sa.u"));
                    paytomorrow_log($status_response->getLender() === 'PAYTOMORROW');
                    if($status_response->isTaxExempt()) {
                        $order->add_meta_data( 'is_vat_exempt', 'yes', true );
                        $order->calculate_totals();
                    } else {
                        $this->validate_amount( $order, $status_response->getLoanAmount());
                    }
                    $order->payment_complete( $transaction_id );
                }
            }
            $checkout_status = self::$checkout_status;

            if(!empty($checkout_status) && $checkout_status !=='processing'){
                paytomorrow_log("before status update :" .date("h:i:sa.u"));
                $order->update_status($checkout_status);
            }
            paytomorrow_log("End processing :" .date("h:i:sa.u"));
        }
    }
    /**
     * Set PayTomorrow transaction ID if it missing for Woo order
     *
     * @param $order
     * @param $transaction_id
     */
    public function set_woo_order_transaction_id( $order, $transaction_id ) {
        try {
            if ( ! empty ( $transaction_id ) ) {
                $order->set_transaction_id( $transaction_id );
            }

            $order->save();
        } catch (\Exception $e) {
        }
    }


    /**
     * If PayTomorrow order, load a metabox in the admin screen.
     */
    public function add_meta_boxes($post_type, $post) {

        $meta = get_post_meta( sanitize_text_field( $_GET['post'] ) );

        if (gettype($meta) == 'array' && !array_key_exists( '_cart_hash', $meta ) ) {
            return;
        }

        $order = new WC_Order( sanitize_text_field( $_GET['post'] ) );
        $payment_method = $order->get_payment_method();
        if ( $payment_method != self::PAYTOMORROW_PAYMENT_METHOD ) return;
        add_filter('is_protected_meta', 'protected_paytomorrow_meta_filter', 10, 2);
        function protected_paytomorrow_meta_filter($protected, $meta_key) {
            return ($meta_key == 'PT-ReferenceID' ? true : $protected) ||
                ($meta_key == 'PT-RETURN-URL' ? true : $protected) ||
                ($meta_key == 'PT-URL' ? true : $protected);
        }
        $this->title       =  $this->get_option( 'pt_title' );

        $transaction_id = $order->get_transaction_id();
        paytomorrow_log('adding box for transaction id :'.$transaction_id);
        if ( ! $transaction_id ) return;

       $status_response = $this->get_status($order);
        // paytomorrow_log($status_response);
        add_meta_box(
            'woocommerce-order-my-custom',
            __( 'PayTomorrow Status' ),
            array( $this, 'build_paytomorrow_status_meta_box' ),
            'shop_order',
            'side',
            'default',
            array( $status_response, $order )
        );
    }

    /**
     * PayTomorrow box
     *
     * @param $data
     * @param $box
     */
    function build_paytomorrow_status_meta_box( $data, $box) {
        /** @var StatusResponse $status_response */
         $status_response = $box['args']['0'];
        /** @var WC_Order $order */
         $order = $box['args']['1'];
        ?>
        <style>
            .paytomorrow-status {
                color: green;
                display: inline-block;
                vertical-align: bottom;
            }
            .paytomorrow-value {
                display: inline-block;
                vertical-align: bottom;
            }

            .paytomorrow-error {
               color: red;
                font-size: 10px;
            }


            .paytomorrow-status-container .button {
                margin-top: 10px;
            }
            .pt-label {
                font-weight: bold;
            }

        </style>

        <div class="paytomorrow-status-container">
            <div>
                <label class="pt-label">Status :</label>
                <span class="paytomorrow-status"><?php echo esc_html( $status_response->getMessage()); ?></span>
            </div>
            <div>
                <label class="pt-label">Lender :</label>
                <span class="paytomorrow-value"><?php echo esc_html( $status_response->getLender() ); ?></span>
            </div>
            <div>
                <label class="pt-label">Approved Amount :</label>
                <span class="paytomorrow-value">$<?php echo esc_html( $status_response->getMaxApprovalAmount() ); ?></span>
            </div>
            <div>
                <label class="pt-label">Loan Amount :</label>
                <span class="paytomorrow-value">$<?php echo esc_html( number_format( $status_response->getLoanAmount(), 2, '.', '' )); ?></span>
            </div>
            <div>
                <span class="paytomorrow-error"><?php echo $this->check_amounts($order, $status_response->getLoanAmount()); ?></span>
                <span class="paytomorrow-error"><?php echo $this->check_status($order->get_status(), $status_response->getStatus()); ?></span>
            </div>
        </div>
    <?php }


    public function wc_paytomorrow_cancel_order($order_id) {
        return new WP_Error( 'error', __( 'Refund Failed: No transaction ID', 'wc_paytomorrow' ) );
        paytomorrow_log('order_id'.$order_id);
        $order        = new WC_Order( $order_id );
        $payment_method = $order->get_payment_method();
        // If not PayTomorrow return
        if ( $payment_method != self::PAYTOMORROW_PAYMENT_METHOD ) return;

        $request_url       = self::$api_url . self::BASE_ECOMMERCE_URL.$order->get_transaction_id().'/cancel?postback=false';
        $this->init_api();
        $request           = array(
            'method'      => 'POST',
            'headers'     => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'bearer ' . WC_Gateway_Paytomorrow_API_Handler::$api_token,
            ),
            'body'        => '',
            'timeout'     => 70,
            'user-agent'  => 'WooCommerce/' . WC()->version,
            'httpversion' => '1.2',
            'sslverify'   => false,
        );
        if ( is_ssl() ) {
            $raw_response = wp_safe_remote_post( $request_url, $request );
        } else {
            $raw_response = wp_remote_post( $request_url, $request );
        }
        if ( wp_remote_retrieve_response_code($raw_response) !== 200 ) {
            $order->add_order_note("Cancel order in PayTomorrow failed. Only Ready to Settle orders can be cancelled!", 0, false);
            paytomorrow_log($raw_response);
        }
    }

    public function wc_paytomorrow_completed_order($order_id) {

        paytomorrow_log('wc_paytomorrow_completed_order:order_id'.$order_id);
        $order        = new WC_Order( $order_id );
        $payment_method = $order->get_payment_method();
        // If not PayTomorrow return
        if ( $payment_method != self::PAYTOMORROW_PAYMENT_METHOD ) return;

        $request_url       = self::$api_url . self::BASE_ECOMMERCE_URL.$order->get_transaction_id().'/settle?postback=false';
        $this->init_api();
        $request           = array(
            'method'      => 'POST',
            'headers'     => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'bearer ' . WC_Gateway_Paytomorrow_API_Handler::$api_token,
            ),
            'body'        => '',
            'timeout'     => 70,
            'user-agent'  => 'WooCommerce/' . WC()->version,
            'httpversion' => '1.2',
            'sslverify'   => false,
        );
        if ( is_ssl() ) {
            $raw_response = wp_safe_remote_post( $request_url, $request );
        } else {
            $raw_response = wp_remote_post( $request_url, $request );
        }
        if ( wp_remote_retrieve_response_code($raw_response) !== 200 ) {
            $order->add_order_note("Funding the order in PayTomorrow failed", 0, false);
            paytomorrow_log($raw_response);
        }
    }

    /**
     * Gets the current status of a PayTomorrow order.
     * @param $transaction_id
     *
     * @return string|null
     */
    public function get_status( $order ) : StatusResponse {

        $request_url       = self::$api_url . self::BASE_ECOMMERCE_URL.$order->get_transaction_id().'/status';
        $this->init_api();
        $request           = array(
            'method'      => 'GET',
            'headers'     => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'bearer ' . WC_Gateway_Paytomorrow_API_Handler::$api_token,
            ),
            'body'        => '',
            'timeout'     => 70,
            'user-agent'  => 'WooCommerce/' . WC()->version,
            'httpversion' => '1.2',
            'sslverify'   => false,
        );
        if ( is_ssl() ) {
            $raw_response = wp_safe_remote_get( $request_url, $request );
        } else {
            $raw_response = wp_remote_get( $request_url, $request );
        }
        paytomorrow_log(wp_remote_retrieve_body( $raw_response ));
        $data = json_decode(wp_remote_retrieve_body( $raw_response ), true);
        $status_response = new StatusResponse();
        foreach ($data as $key => $value) $status_response->{$key} = $value;
        paytomorrow_log($status_response);
        return $status_response;
    }

	/**
	 * Init the API class and set the username/password etc.
	 */
	protected function init_api() {
		include_once dirname(__FILE__) . '/class-wc-gateway-paytomorrow-api-handler.php';

		WC_Gateway_Paytomorrow_API_Handler::$api_username     = $this->get_option( 'api_username' );
		WC_Gateway_Paytomorrow_API_Handler::$api_password     = $this->get_option( 'api_password' );
		WC_Gateway_Paytomorrow_API_Handler::$api_signature    = $this->get_option( 'api_signature' );
		WC_Gateway_Paytomorrow_API_Handler::$api_url          = $this->get_option( 'api_url' );
		WC_Gateway_Paytomorrow_API_Handler::$checkout_postfix = self::$checkout_postfix;
		WC_Gateway_Paytomorrow_API_Handler::$oauth_postfix    = self::$oauth_postfix;

		WC_Gateway_Paytomorrow_API_Handler::do_authorize();

	}

    /**
 * Function for `woocommerce_order_status_(to)` action-hook.
 *
 * @param int      $id                Order ID.
 * @param WC_Order $order             Order object.
 * @param array    $status_transition Status transition data.
 *
 * @return void
 */
function wp_paytomorrow_woocommerce_order_status_to_action( $id, $order, $status_transition ) {
    paytomorrow_log("id :".$id.' order: '.$order.' transition :'.$status_transition);
    // action...
}

	// @codingStandardsIgnoreStart
	/**
	 * Process a refund if supported.
	 *
	 * @param  int    $order_id The order id.
	 * @param  float  $amount The order amount.
	 * @param  string $reason The reason for the refund.
	 * @return bool|WP_Error
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {

		$order = wc_get_order( $order_id );
        if ( $order->get_payment_method() != self::PAYTOMORROW_PAYMENT_METHOD ) return;
        paytomorrow_log("in Refund");
        $status_results = $this->get_status($order);

		if ( ! $this->can_refund_order( $order ) ) {
			return new WP_Error( 'error', __( 'Refund Failed: No transaction ID', 'wc_paytomorrow' ));
		}
        if ( ! $this->check_refund_amount( $amount, $status_results->getLoanAmount() ) ) {
            return new WP_Error( 'error', __( 'Refund Failed: Only Full refunds are supported by PayTomorrow', 'wc_paytomorrow' ));
        }
        if ($this->check_amounts( $order, $status_results->getLoanAmount()) === '') {
            $body_request = array('reason' => 'Full refund issued by Woocommerce. Reason : ' .$reason);
            $request_url       = self::$api_url . self::BASE_ECOMMERCE_URL.$order->get_transaction_id().'/refund';
            $this->init_api();
            $request           = array(
                'method'      => 'POST',
                'headers'     => array(
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'bearer ' . WC_Gateway_Paytomorrow_API_Handler::$api_token,
                ),
                'body'        =>  wp_json_encode( $body_request ),
                'timeout'     => 70,
                'user-agent'  => 'WooCommerce/' . WC()->version,
                'httpversion' => '1.2',
                'sslverify'   => false,
            );
            if ( is_ssl() ) {
                $raw_response = wp_safe_remote_post( $request_url, $request );
            } else {
                $raw_response = wp_remote_post( $request_url, $request );
            }
            if ( wp_remote_retrieve_response_code($raw_response) !== 200 ) {
                $order->add_order_note("Refund order in PayTomorrow failed.", 0, false);
                paytomorrow_log($raw_response);
            }
            return true;
        } else {
            return new WP_Error( 'error', __( 'Refund Failed: Loan anounts do not match!', 'wc_paytomorrow' ) );
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
     * Check payment amount from IPN matches the order.
     *
     * @param WC_Order $order The order object.
     * @param int      $amount The amount.
     */
    public function check_amounts( $order, $amount ) {
        paytomorrow_log( 'Payment error: Amounts do not match (gross ' . $amount . ') '.$order->get_total() .'' );
        if ( number_format( $order->get_total(), 2, '.', '' ) != number_format( $amount, 2, '.', '' ) ) {
            paytomorrow_log( 'Payment error: Amounts do not match (gross ' . $amount . ')' );
            /* translators: %s: Amount */
            return 'PayTomorrow amount of '. number_format( $amount, 2, '.', '' ) .' does not match order total of '.number_format( $order->get_total(), 2, '.', '' );
        }
        return '';
    }

    /**
     * Check the order status matches
     *
     * @param string Woocomerce order status
     * @param string PayTomorrow order status
     * @return string
     */
    public function check_status( $orderStatus, $paytomorrowStatus ) {
        paytomorrow_log($orderStatus);
        paytomorrow_log($paytomorrowStatus);
        switch($orderStatus) {
            case 'processing':
            case 'pending' :   {
                    if ($paytomorrowStatus !== 'S') {
                        return 'PayTomorrow status does not match Order Status of Ready to Settle';
                    }
                    break;
                }
            case 'completed': {
                if ($paytomorrowStatus !== 'C' && $paytomorrowStatus !== 'L') {
                    return 'PayTomorrow status does not match Order Status of Funded';
                }
                break;
            }
            case 'refunded': {
                if ($paytomorrowStatus !== 'T') {
                    paytomorrow_log('uhu');
                    return 'PayTomorrow status does not match Order Status of Refunded';
                }
                break;
            }
        }
        return '';
    }

    /**
     * Check payment amount from IPN matches the order.
     *
     * @param WC_Order $order The order object.
     * @param int      $amount The amount.
     * @return bool|WP_Error
     */
    public function check_refund_amount( $refundAmount, $amount ) {
        if ( number_format( $refundAmount, 2, '.', '' ) != number_format( $amount, 2, '.', '' ) ) {
            /* translators: %s: Amount */
            return false;
        }
        return true;
    }
}
