<?php
/**
 * PayTomorrow Standard Payment Gateway.
 *
 * Provides a PayTomorrow Standard Payment Gateway.
 *
 * @class       WC_Paytomorrow
 * @package     paytomorrow
 * @version     3.0.0
 */

/**
 * PayTomorrow Main Class
 */
class WC_Paytomorrow {
	/**
	 * Construct
	 */
	public function __construct() {
		// called after all plugins have loaded.
		add_action( 'plugins_loaded', [ $this, 'init_gateways' ] );

		// add our scripts.
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}

	/**
	 * Initialize the gateway. Called very early - in the context of the plugins_loaded action
	 *
	 * @since 1.0.0
	 */
	public function init_gateways() {
		if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
			return;
		}

		require_once plugin_basename( 'classes/class-wc-gateway-paytomorrow.php' );

		add_filter( 'woocommerce_payment_gateways', [ $this, 'add_gateways' ] );

        // Registers WooCommerce Blocks integration.
        add_action( 'woocommerce_blocks_loaded', array( __CLASS__, 'pt_gateway_woocommerce_block_support') );
	}

	/**
	 * Add the gateways to WooCommerce.
	 *
	 * @param array $methods List of gateways.
	 * @since 1.0.0
	 */
	public function add_gateways( $methods ) {
		$methods[] = 'WC_Gateway_Paytomorrow';

		return $methods;
	}

	/**
	 * Loads front side scripts when checkout pages.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		if ( ! function_exists( 'is_checkout' ) || ! function_exists( 'is_cart' ) ) {
			return;
		}

		if ( ! is_checkout() && ! is_cart() ) {
			return;
		}

		// Make sure our gateways are enabled before we do anything.
		if ( ! $this->are_our_gateways_enabled() ) {
			return;
		}

		// Always enqueue styles for simplicity's sake (because not all styles are related to JavaScript manipulated elements).
		if ( is_checkout() ) {
			wp_register_style( 'paytomorrow_styles', plugins_url( 'assets/css/checkout.css', __FILE__ ) );
		}

		wp_enqueue_style( 'paytomorrow_styles' );

	}

	/**
	 * Returns true if our gateways are enabled, false otherwise
	 *
	 * @since 1.0.0
	 */
	public function are_our_gateways_enabled() {

		// It doesn't matter which gateway we check, since setting changes are cloned between them.
		$gateway_settings = get_option( 'woocommerce_paytomorrow_settings', [] );

		if ( empty( $gateway_settings ) ) {
			return false;
		}

		return ( 'yes' === $gateway_settings['enabled'] );

	}


    /**
     * Registers WooCommerce Blocks integration.
     *
     */
    public static function pt_gateway_woocommerce_block_support() {
        if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
            require_once 'classes/class-wc-gateway-paytomorrow-blocks-support.php';

            add_action(
                'woocommerce_blocks_payment_method_type_registration',
                function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
                    $payment_method_registry->register( new WC_Gateway_Paytomorrow_Blocks_Support() );
                }
            );
        }
    }

}
