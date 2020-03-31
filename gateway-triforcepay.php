<?php
/**
 * @name          TriforcePay Interac® Online Payment Gateway Plugin for 
 * 
 * @description   Receive payments using the Canadian payments system Interac® Online.
 * 
 * @version        0.0.5
 * @package        WooCommerce/Classes/Payment
 * @developer     Rahim Khoja (rahim@khoja.ca) https://www.linkedin.com/in/rahim-khoja-879944139/
 * @author        Triforce Media Inc. (sales@triforcemedia.com) https://triforcemedia.com
 */

if ( ! defined( 'ABSPATH' ) ) {
        exit;
}


/**
 * Initialize the gateway.
 * @since 1.0.0
 */
function woocommerce_TriforcePay_init() {
        if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
                return;
        }
        require_once( plugin_basename( 'classes/class-wc-gateway-triforcepay.php' ) );
        load_plugin_textdomain( 'woocommerce-gateway-triforcepay', false, trailingslashit( dirname( plugin_basename( __FILE__ ) ) ) );
        add_filter( 'woocommerce_payment_gateways', 'woocommerce_triforcepay_add_gateway' );
}
add_action( 'plugins_loaded', 'woocommerce_triforcepay_init', 0 );

function woocommerce_triforcepay_plugin_links( $links ) {
        $settings_url = add_query_arg(
                array(
                        'page' => 'wc-settings',
                        'tab' => 'checkout',
                        'section' => 'WC_Gateway_TriforcePay',
                ),
                admin_url( 'admin.php' )
        );

        $plugin_links = array(
                '<a href="' . esc_url( $settings_url ) . '">' . __( 'Settings', 'woocommerce-gateway-triforcepay' ) . '</a>',
        );

        return array_merge( $plugin_links, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'woocommerce_triforcepay_plugin_links' );


/**
 * Add the gateway to WooCommerce
 * @since 1.0.0
 */
function woocommerce_triforcepay_add_gateway( $methods ) {
        $methods[] = 'WC_Gateway_TriforcePay';
        return $methods;
}
