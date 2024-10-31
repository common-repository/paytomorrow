<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;


final class WC_Gateway_Paytomorrow_Blocks_Support extends AbstractPaymentMethodType
{
    /**
     * Payment method name defined by payment methods extending this class.
     *
     * @var string
     */
    protected $name = 'paytomorrow';

    /**
     * Initializes the payment method type.
     */
    public function initialize() {
        $this->settings = get_option( 'woocommerce_paytomorrow_settings', [] );
    }

    /**
     * Returns if this payment method should be active. If false, the scripts will not be enqueued.
     *
     * @return boolean
     */
    public function is_active() {
        return filter_var( $this->get_setting( 'enabled', false ), FILTER_VALIDATE_BOOLEAN );
    }

    /**
     * Returns an array of scripts/handles to be registered for this payment method.
     *
     * @return array
     */
    public function get_payment_method_script_handles() {
        wp_register_script(
            'pt-payment-method',
            plugin_dir_url(__FILE__) . 'blocks/pt-payment-method.js',
            [
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
            ],
            null,
            true,

        );

        if ( isset($_GET['paytomorrow']) && $_GET['paytomorrow'] == 1 && isset($_GET['order'])) {

            if(!wp_script_is('woocommerce_paytomorrow_popup', 'enqueued')){
                $order_id = sanitize_text_field($_GET['order']);
                wp_enqueue_script('woocommerce_paytomorrow_popup', plugins_url('popup/js/pt-popup.js', __FILE__));
                wp_enqueue_style('pt-mpe', plugins_url('popup/css/pt-popup.css', __FILE__));
                wp_localize_script('woocommerce_paytomorrow_popup', 'popupUrl', get_post_meta($order_id, PT_META_URL));

                wp_localize_script('woocommerce_paytomorrow_popup', 'popupCloseUrl', get_permalink(wc_get_page_id('checkout')));
                wp_localize_script('woocommerce_paytomorrow_popup', 'popupSuccessUrl', get_post_meta($order_id, PT_META_RETURN_URL));
            }
        }
        return [ 'pt-payment-method' ];
    }

    /**
     * Returns an array of key=>value pairs of data made available to the payment methods script.
     *
     * @return array
     */
    public function get_payment_method_data() {
        return [
            'title'       => 'paytomorrow',
            'description' => 'Paytomorrow Payment method',
            'supports'    => $this->get_supported_features(),
            'icon'     => plugins_url('assets/images/PTLogo.png', __DIR__ ),
            'enabled' => true
        ];
    }

    /**
     * Returns an array of supported features.
     *
     * @return string[]
     */
    public function get_supported_features() {
        $gateway  = new WC_Gateway_Paytomorrow();
        $features = array_filter( $gateway->supports, array( $gateway, 'supports' ) );

        /**
         * Filter to control what features are available for each payment gateway.
         *
         * @param array $features List of supported features.
         * @param string $name Gateway name.
         * @return array Updated list of supported features.
         */
        return apply_filters( '__experimental_woocommerce_blocks_payment_gateway_features_list', $features, $this->get_name() );
    }
}
