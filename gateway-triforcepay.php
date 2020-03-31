<?php
/**
 * Plugin Name: Triforce Media WooCommerce Interac® Online Payment Gateway
 * Description: Receive payments using the Canadian payments system Interac® Online.
 * Author: TriForce Media Inc. ( https://triforcemedia.com ) - Rahim Khoja ( rahim@khoja.ca )
 * Version: 0.0.5
 *
 * Copyright (c) 2017 TriForce Media Inc.
*/
if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

/**
 * Initialize the gateway.
 * @since 1.0.0
 */
function woocommerce_triforcepay_init() {
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
                        'section' => 'wc_gateway_triforcepay',
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
        $methods[] = 'WC_Gateway_triforcepay';
        return $methods;
}
