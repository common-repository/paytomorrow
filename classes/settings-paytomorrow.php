<?php
/**
 * PayTomorrow Standard Payment Gateway Settings.
 *
 * Provides a PayTomorrow Standard Payment Gateway's Settings.
 *
 * @version     3.0.1
 * @package     paytomorrow
 * @author      PayTomorrow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$formated_debug_description = sprintf( 'Log PayTomorrow events, such as IPN requests, inside <code>%s</code>', wc_get_log_file_path( 'paytomorrow' ) );

/**
 * Settings for PayTomorrow Gateway.
 */
return array(
    'enabled' => array(
        		'title'       => __( 'Enable/Disable', 'wc_paytomorrow' ),
        		'type'        => 'checkbox',
        		'label'       => __( 'Enable / Disable PayTomorrow Plugin', 'wc_paytomorrow' ),
        		'description' => __( 'This enables PayTomorrow Checkout which allows customers to checkout directly via PayTomorrow from your cart page.', 'wc_paytomorrow' ),
        		'default'     => 'yes',
        		'desc_tip'    => true,
    ),
	'debug'          => array(
		'title'       => __( 'Debug Log', 'wc_paytomorrow' ),
		'type'        => 'checkbox',
		'label'       => __( 'Enable logging', 'wc_paytomorrow' ),
		'default'     => 'no',
		/* translators: %s: wc_get_log_file_path( 'paytomorrow' ) */
		'description' => __( 'Log PayTomorrow events, such as IPN requests, inside <code>%s</code>', 'wc_paytomorrow' ),
	),	
	'api_url'        => array(
		'title'       => __( 'Environment*', 'wc_paytomorrow' ),
		'type'        => 'select',
		'class'       => array('wc_paytomorrow'),
		'label'       => __( 'Select Environment', 'wc_paytomorrow' ),
		'options'     => array(
			'blank'  =>  __( 'Select Environment', 'wc_paytomorrow' ),
			'https://api.paytomorrow.com' => __( 'Production', 'wc_paytomorrow' ),
			'https://api-staging.paytomorrow.com'=> 	 __( 'Staging', 'wc_paytomorrow' )
			)
	),
	'api_username'   => array(
		'title'       => __( 'API Username*', 'wc_paytomorrow' ),
		'type'        => 'text',
		'description' => __( 'Get your API credentials from PayTomorrow.', 'wc_paytomorrow' ),
		'default'     => '',
		'desc_tip'    => true,
		'placeholder' => __( 'username', 'wc_paytomorrow' ),
	),
	'api_password'   => array(
		'title'       => __( 'API Password*', 'wc_paytomorrow' ),
		'type'        => 'password',
		'description' => __( 'Get your API credentials from PayTomorrow.', 'wc_paytomorrow' ),
		'default'     => '',
		'desc_tip'    => true,
		'placeholder' => __( 'password', 'wc_paytomorrow' ),
	),
	'api_signature'  => array(
		'title'       => __( 'API Signature*', 'wc_paytomorrow' ),
		'type'        => 'password',
		'description' => __( 'Get your API credentials from PayTomorrow.', 'wc_paytomorrow' ),
		'default'     => '',
		'desc_tip'    => true,
		'placeholder' => __( 'signature', 'wc_paytomorrow' ),
	),
	'pt_title'   => array(
        		'title'       => __( 'Checkout title', 'wc_paytomorrow' ),
        		'type'        => 'text',
        		'description' => __( 'Add a title on checkout page', 'wc_paytomorrow' ),
        		'default'     => 'PayTomorrow',
        		'desc_tip'    => true,
        		'placeholder' => __( 'title', 'wc_paytomorrow' ),
        	),
	'mpe'          => array(
		'title'       => __( 'Monthly Price Estimator  (MPE)', 'wc_paytomorrow' ),
		'type'        => 'checkbox',
		'label'       => __( 'Enable MPE', 'wc_paytomorrow'),
		'default'     => 'no',
		/* translators: %s: wc_get_log_file_path( 'paytomorrow' ) */
		'description' => __( 'Enable Monthly Price Estimator', 'wc_paytomorrow' ),
	),
	'debug_mpe'          => array(
		'title'       => __( 'Debug Mode  (MPE)', 'wc_paytomorrow' ),
		'type'        => 'checkbox',
		'label'       => __( 'Enable Debug', 'wc_paytomorrow' ),
		'default'     => 'no',
		/* translators: %s: wc_get_log_file_path( 'paytomorrow' ) */
		'description' => __( 'Display PayTomorrow\'s MPE console logs for debugging errors.', 'wc_paytomorrow' ),
	),
	'enableMoreInfoLink_mpe'          => array(
		'title'       => __( 'Info Link  (MPE)', 'wc_paytomorrow' ),
		'type'        => 'checkbox',
		'label'       => __( 'More Info Link', 'wc_paytomorrow' ),
		'default'     => 'yes',
		/* translators: %s: wc_get_log_file_path( 'paytomorrow' ) */
		'description' => __( 'Display a link under the MPE for opening the MPE popup.', 'wc_paytomorrow' ),
	),
	'logoColor_mpe'        => array(
		'title'       => __( 'Logo Color (MPE)', 'wc_paytomorrow' ),
		'type'        => 'select',
		'description' => __( 'PayTomorrow\'s logo color on the MPE.', 'wc_paytomorrow' ),
		'desc_tip'    => true,
		'class'       => array('wc_paytomorrow'),
		'label'       => __( 'Select Color', 'wc_paytomorrow' ),
		'options'     => array(
			'original'  =>  __( 'Select Color', 'wc_paytomorrow' ),
			'white' => __( 'white', 'wc_paytomorrow' ),
			'black'=> 	 __( 'black', 'wc_paytomorrow' ),
			'original'=> 	 __( 'original', 'wc_paytomorrow' )
			)
	),
	'maxAmount_mpe'   => array(
		'title'       => __( 'maxAmount (MPE)', 'wc_paytomorrow' ),
		'type'        => 'number',
		'description' => __( 'Max price limit at which the MPE appears.', 'wc_paytomorrow' ),
		'default'     => '5000',
		'desc_tip'    => true,
		'placeholder' => __( 'maxAmount', 'wc_paytomorrow' ),
	),
	'maxTerm_mpe'   => array(
		'title'       => __( 'Maximum Term (MPE)', 'wc_paytomorrow' ),
		'type'        => 'number',
		'description' => __( 'Max financing term available on your PayTomorrow account.', 'wc_paytomorrow' ),
		'default'     => '24',
		'desc_tip'    => true,
		'placeholder' => __( 'maxTerm', 'wc_paytomorrow' ),
	),
	'minAmount_mpe'   => array(
		'title'       => __( 'Minimum Amount (MPE)', 'wc_paytomorrow' ),
		'type'        => 'number',
		'description' => __( 'Min price limit at which the MPE appears.', 'wc_paytomorrow' ),
		'default'     => '500',
		'desc_tip'    => true,
		'placeholder' => __( 'minAmount', 'wc_paytomorrow' ),
	),
	'mpeSelector_mpe'   => array(
		'title'       => __( 'MPE Selector (MPE)', 'wc_paytomorrow' ),
		'type'        => 'text',
		'description' => __( 'CSS selector for the element in which the MPE will be added.', 'wc_paytomorrow' ),
		'default'     => '.pt-mpe',
		'desc_tip'    => true,
		'placeholder' => __( 'mpeSelector', 'wc_paytomorrow' ),
	),
	'priceSelector_mpe'   => array(
		'title'       => __( 'Price Selector (MPE)', 'wc_paytomorrow' ),
		'type'        => 'text',
		'description' => __( 'CSS selector for scanning product prices.', 'wc_paytomorrow' ),
		'default'     =>'.mpe-price',
		'desc_tip'    => true,
		'placeholder' => __( 'priceSelector', 'wc_paytomorrow' ),
	),
	'storeDisplayName_mpe'   => array(
		'title'       => __( 'Store Display Name (MPE)', 'wc_paytomorrow' ),
		'type'        => 'text',
		'description' => __( 'The store name you want us to display in the popup.', 'wc_paytomorrow' ),
		'default'     => 'Your Favorite Merchant',
		'desc_tip'    => true,
		'placeholder' => __( 'pristoreDisplayName', 'wc_paytomorrow' ),
	),
	'publicId_mpe'   => array(
		'title'       => __( 'Public Id (MPE)', 'wc_paytomorrow' ),
		'type'        => 'text',
		'description' => __( 'Your publicId provided by PayTomorrow (under Resources in the Dashboard).', 'wc_paytomorrow' ),
		'default'     => '',
		'placeholder' => __( 'publicId', 'wc_paytomorrow' ),
	),
    'display_micro_offer_mpe'          => array(
        'title'       => __( 'Display Micro offers (MPE)', 'wc_paytomorrow' ),
        'type'        => 'checkbox',
        'label'       => __( 'Enable micro offers', 'wc_paytomorrow' ),
        'default'     => 'no',
        /* translators: %s: wc_get_log_file_path( 'paytomorrow' ) */
        'description' => __( 'Enable PayTomorrow\'s micro offers.', 'wc_paytomorrow' ),
    ),
    'display_prime_offer_mpe'          => array(
        'title'       => __( 'Display Prime offers (MPE)', 'wc_paytomorrow' ),
        'type'        => 'checkbox',
        'label'       => __( 'Enable prime offers', 'wc_paytomorrow' ),
        'default'     => 'no',
        /* translators: %s: wc_get_log_file_path( 'paytomorrow' ) */
        'description' => __( 'Enable PayTomorrow\'s prime offers.', 'wc_paytomorrow' ),
    ),
    'max_micro_amount_mpe'   => array(
        'title'       => __( 'Max Micro Amount (MPE)', 'wc_paytomorrow' ),
        'type'        => 'number',
        'description' => __( 'Maximum amount for a micro loan offer.', 'wc_paytomorrow' ),
        'default'     => '0',
        'desc_tip'    => true,
    ),
    'prime_apr_mpe'   => array(
        'title'       => __( 'Prime APR (MPE)', 'wc_paytomorrow' ),
        'type'        => 'currency',
        'description' => __( 'The apr used by the prime offers.', 'wc_paytomorrow' ),
        'default'     => '0',
        'desc_tip'    => true,
    ),
    'pt_wc_checkout_status' => array(
        'title'    => __( 'Initial Order Status', 'wc_paytomorrow' ),
        'description'     => __( 'Order Status on Checkout Complete', 'wc_paytomorrow' ),
        'type'     => 'select',
        'class'       => array('wc_paytomorrow'),
        'options'  => array(
            'processing'  => __( 'Processing', 'wc_paytomorrow' ),
            'pending'  => __( 'Pending', 'wc_paytomorrow' ),
        ),
        'default' => 'processing',
        'desc_tip' => false,
    )
);
