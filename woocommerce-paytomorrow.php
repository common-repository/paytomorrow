<?php
/**
 * WooCommerce PayTomorrow
 *
 * @package     PayTomorrow
 * @author      PayTomorrow
 * @copyright   2018 PayTomorrow
 * @license     GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: WooCommerce PayTomorrow
 * Plugin URI: http://www.paytomorrow.com/developer
 * Description: WooCommerce PayTomorrow plugin
 * Author: Paytomorrow
 * Author URI: https://www.paytomorrow.com
 * Version: 3.0.1
 * Text Domain: woocommerce-paytomorrow
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

/**
 * Check if WooCommerce is active
 */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {

	if ( ! class_exists( 'WC_Paytomorrow' ) ) {


		define( 'PT_META_KEY', 'PT-ReferenceID' );
		define( 'PT_META_URL', 'PT-URL' );
		define( 'PT_META_RETURN_URL', 'PT-RETURN-URL' );

		/**
		 * Log error
		 *
		 * @param string $message The message to log.
		 */
		function paytomorrow_log( $message ) {
		$mpe = get_option('woocommerce_paytomorrow_settings');
			if ( $mpe['debug'] === 'yes' ) {
				if ( is_array( $message ) || is_object( $message ) ) {
					WC_Gateway_Paytomorrow::log( print_r( $message, true ) );
				} else {
					//error_log( $message );
					WC_Gateway_Paytomorrow::log( $message );
				}
			}
		}
		/**
		 * Localisation
		 */
		load_plugin_textdomain( 'wc_paytomorrow', false, dirname( plugin_basename( __FILE__ ) ) . '/' );

		require_once 'class-wc-paytomorrow.php';

		// finally instantiate our plugin class and add it to the set of globals.
		$GLOBALS['wc_paytomorrow'] = new WC_Paytomorrow();
		add_action( 'wp_enqueue_scripts',  'paytomorrow_mpe_scripts' );
		add_action( 'woocommerce_before_add_to_cart_form', 'add_mpe_div');
	}

	function paytomorrow_mpe_scripts() {

		wp_enqueue_script( 'PayTomorrow', '//cdn.paytomorrow.com/js/pt-mpe.min.js');
		wp_enqueue_style( 'pt-mpe', '//cdn.paytomorrow.com/css/pt-mpe.min.css' );
	
		wp_register_script( 'mpe-startup', plugin_dir_url( __FILE__ ) . 'classes/mpe/mpe-startup.js' );
		wp_enqueue_script('mpe-startup', array( 'PayTomorrow' ));

        $mpe = get_option('woocommerce_paytomorrow_settings');
        unset($mpe['api_username']);
        unset($mpe['api_password']);
        unset($mpe['api_signature']);
        $mpe['priceSelector_mpe'] = str_replace("&gt;",">",$mpe['priceSelector_mpe']);
        $mpe['mpeSelector_mpe'] = str_replace("&gt;",">",$mpe['mpeSelector_mpe']);

		wp_localize_script('mpe-startup', 'mpeSettings', 
		  	array(
				  'mpeOptions' =>  $mpe,
			) 
		  );
	
	}

	function add_mpe_div(){
	    echo '<div class="pt-mpe"></div>';
    }
}
