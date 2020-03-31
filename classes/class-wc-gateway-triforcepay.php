<?php

/**
 * @name          TriforcePay Interac® Online Redirected Payment Gateway.
 *
 * @description   Provides an off site Secured Payment Gateway to Interac® Online by TriforcePay.
 *
 * @class         WC_Gateway_TriforcePay
 * @extends       WC_Payment_Gateway
 * @version       0.0.5
 * @package       WooCommerce/Classes/Payment
 * @developer     Rahim Khoja (rahim@khoja.ca) https://www.linkedin.com/in/rahim-khoja-879944139/
 * @author        Triforce Media Inc. (sales@triforcemedia.com) https://triforcemedia.com
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * WC_Gateway_TriforcePay Class.
 */
class WC_Gateway_TriforcePay extends WC_Payment_Gateway {

        /** @var bool Whether or not logging is enabled */
        public static $log_enabled = false;

        /** @var WC_Logger Logger instance */
        public static $log = false;

        /**
        * Constructor for the gateway.
        */
        public function __construct() {
                $this->id                 = 'triforcepay';
                $this->has_fields         = false;
                $this->order_button_text  = __( 'Proceed to Interac® Online', 'woocommerce' );
                $this->available_countries  = array( 'CA' );
                $this->available_currencies = array( 'CAD' );
                $this->method_title       = __( 'Interac® Online', 'woocommerce' );
                $this->method_description = sprintf( __( 'TriforcePay sends customers to a Secure Interac® Online Website in order to complete payment. TriforcePay requires callbacks to update order statuses after payment. Check the %ssystem status%s page for more details.', 'woocommerce' ), '<a href="' . admin_url( 'admin.php?page=wc-status' ) . '">', '</a>' );
                $this->icon               = WP_PLUGIN_URL . '/' . plugin_basename( dirname( dirname( __FILE__ ) ) ) . '/assets/images/icon_interac.png';
                $this->supports           = array(
                        'products',
                        'pre-orders',
                );

                // Load the settings.
                $this->init_form_fields();
                $this->init_settings();

                // Define user set variables.
                $this->title              = $this->get_option( 'title' );
                $this->description        = $this->get_option( 'description' );
                $this->testmode           = 'yes' === $this->get_option( 'testmode', 'no' );
				$this->debugmode          = true;
                $this->termid             = $this->get_option( 'termid' );
                $this->pass               = $this->get_option( 'pass' );
                $this->secure_id          = '';
				$this->payment_success_status     = $this->get_option( 'payment_success_status' );
				$this->payment_failed_status     = 'wc-failed';
			
                self::$log_enabled    = $this->debug;

                add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
                add_action( 'woocommerce_thankyou', array( $this, 'checkOrderAfter') );
        }

        /**
        * Logging method.
        * @param string $message
        */
        public static function log( $message ) {
                if ( self::$log_enabled ) {
                        if ( empty( self::$log ) ) {
                                self::$log = new WC_Logger();
                        }
                        self::$log->add( 'interpay', $message );
                }
        }

        /**
        * Get gateway icon.
        * @return string
        */
        public function get_icon() {
                $icon_html = '<img src="' . esc_attr( $this->icon) . '" alt="' . esc_attr__( 'Interac® Online Icon', 'woocommerce' ) . '" />';
                return apply_filters( 'woocommerce_gateway_icon', $icon_html, $this->id );
        }

        /**
        * Check if this gateway is enabled and available in the user's country.
        * @return bool
        */
        public function is_valid_for_use() {
                return in_array( get_woocommerce_currency(), apply_filters( 'woocommerce_triforcepay_supported_currencies', array( 'CAD' ) ) );
        }

        /**
        * Admin Panel Options.
        * Check if currency is Canadian Dollars before allowing activation.
        * @since 1.0.0
        */
        public function admin_options() {
                if ( in_array( get_woocommerce_currency(), $this->available_currencies ) ) {
                        ?>
                        <h3><?php _e( 'TriforcePay\'s Interac® Online Payment Gateway', 'woocommerce' ); ?></h3>
                        <table class="form-table">
                                <?php $this->generate_settings_html(); ?>
                        </table>
                                <?php
                } else {
                                ?>
                                <h3><?php _e( 'Interac® Online Payment Gateway', 'woocommerce' ); ?></h3>
                                <div class="inline error"><p><strong><?php _e( 'Gateway Disabled', 'woocommerce-gateway-interpay' ); ?></strong> <?php echo sprintf( __( 'Choose Canadian Dollars as your store currency in %1$sPricing Options%2$s to enable the TriforcePay Interac® Online Payment Gateway.', 'woocommerce-gateway-interpay' ), '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=general' ) ) . '">', '</a>' ); ?></p></div>
                                <?php
                }
        }

        /**
        * Initialise Gateway Settings Form Fields.
        */
        function init_form_fields() {
error_log( get_bloginfo( 'url' ),0);
                $this->form_fields = array(
                        'enabled' => array(
                                'title' => __( 'Enable/Disable', 'woocommerce' ),
                                'type' => 'checkbox',
                                'label' => __( 'Enable Interac® Online', 'woocommerce' ),
                                'default' => 'yes'
                        ),
                        'title' => array(
                                'title'       => __( 'Title', 'woocommerce' ),
                                'type'        => 'text',
                                'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
                                'default'     => __( 'Interac® Online', 'woocommerce' ),
                                'desc_tip'    => true,
                        ),
                        'description' => array(
                                'title'       => __( 'Description', 'woocommerce' ),
                                'type'        => 'text',
                                'desc_tip'    => true,
                                'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
                                'default'     => __( 'Pay with Interac® Online', 'woocommerce' ),
                        ),
                                'termid' => array(
                                'title'       => __( 'TriforcePay TERMID', 'woocommerce' ),
                                'type'        => 'text',
                                'description' => __( 'Please enter your TriforcePay TERMID. Should be 8 Characters long. Required to process payments!', 'woocommerce' ),
                                'desc_tip'    => true,
                        ),
                        'pass' => array(
                                'title'       => __( 'TriforcePay PASS', 'woocommerce' ),
                                'type'        => 'text',
                                'description' => __( 'Please enter your TriforcePay Password. Required to process payments!', 'woocommerce' ),
                                'desc_tip'    => true,
                        ),
                        'testmode' => array(
                                'title'       => __( 'TriforcePay\'s Interac® Online Sandbox', 'woocommerce' ),
                                'type'        => 'checkbox',
                                'label'       => __( 'Enable TriforcePay\'s Interac® Online sandbox', 'woocommerce' ),
                                'default'     => 'no',
                                'description' =>  __( 'TriforcePay sandbox can be used to test payments.', 'woocommerce' ),
                        ),
					
						'payment_success_status' => array(
                                'title'       => __( 'TriforcePay\'s Interac® Online Successful Order Status', 'woocommerce' ),
                                'type'        => 'select',
							    'options' => array(
									'wc-pending'        => __( 'Pending Payment', 'woocommerce' ),
									'wc-processing'       => __( 'Processing', 'woocommerce' ),
									'wc-on-hold'  => __( 'On Hold', 'woocommerce' ),
									'wc-completed' => __( 'Completed', 'woocommerce' )
								),
                                'label'       => __( 'Select TriforcePay\'s Interac® Online successful order status', 'woocommerce' ),
                                'default'     => 'on-hold',
                                'description' =>  __( 'TriforcePay\'s Interac® Online plugin changes orders to this status upon successful order completion.', 'woocommerce' ),
                        ),
					
					    array(
                                'title' => __( 'TriforcePay\'s Return URL', 'woocommerce' ),
                                'type' => 'title',
                                'description' => __( WP_PLUGIN_URL . '/' . plugin_basename( dirname( dirname( __FILE__ ) ) ) .'pages/order-return.php' ),
                        )
                );
        }

        /**
        * Get the transaction URL.
        * @return String
        */
        public function get_transaction_url() {
                if ( $this->testmode ) {
                        return 'https://svra.interpaycanada.com:1443/';
                } else {
                        return 'https://svra.interpaycanada.com:1443/';
                }
        }
	
		/**
        * Get the transaction URL.
        * @return String
        */
        public function get_success_status() {
                return $this->payment_success_status;
        }

        /**
        * Update Order on Thank You page. Called via add_action();
        * @param  WC_Order $order
        */
        function checkOrderAfter($order_id) {
                $_Customer_Order = wc_get_order( $order_id );
                $_User_ID = get_current_user_id();
                $_Order_Secure_ID  = get_post_meta($order_id, 'sec_id', true);
                $valid = false;
                $_TriforcePay_Gateway = new WC_Gateway_TriforcePay();

                if (!($_User_ID == 0)) {
                        $_User_Secure_ID = get_user_meta( $_User_ID, '_current_secure_id', true);
                        update_user_meta( $_User_ID, '_current_secure_id', '' );
                }

                if ( 'pending' == $_Customer_Order->status ) {
                        // Checks if Transcaction is Valid for Not Logged In Users
                        if ( ($_User_ID = 0) && ( ! empty( $_Order_Secure_ID ) ) ) {
                                $valid = true;
                        }

                        // Checks if Transcaction is Valid for Logged In Users
                        if ( ( ! empty( $_Order_Secure_ID ) ) && ( $_User_Secure_ID == $_Order_Secure_ID ) ) {
                                $valid = true;
                        }

                        // If $valid is true check if Interac® Transcation was approved.
                        if ( $valid = true ) {
                                error_log("checkOrderAfter(): secure_id = ".$_Order_Secure_ID, 0);

                                $interpay_ack_url = $this->get_transaction_url().'TERMID='.$this->termid.'&TYPE=W&PASS='.$this->pass.'&ACTION=GetResult&SECUREID='.$_Order_Secure_ID.'&ACK=Y';

                                update_post_meta( $order_id, 'Transmit URL 2', $interpay_ack_url );

                                $_GetResult_Response = wp_remote_get( $interpay_ack_url , array( 'timeout' => 120, 'httpversion' => '1.1' ));

                                if ( !is_wp_error( $_GetResult_Response ) ) {

                                        update_post_meta( $order_id, 'Received Data 2', $_GetResult_Response['body'] );

                                        $url = "http://e.ca/?".$_GetResult_Response['body'];
                                        $parts = parse_url($url);
                                        parse_str($parts['query'], $out);

                                        if ( $out['TEXT'] == 'APPROVED' ) {
                                                // Update Order to Complete
											
												error_log('Order Aproved');
												error_log($_TriforcePay_Gateway->get_success_status());
											
                                                $_Customer_Order->update_status($_TriforcePay_Gateway->get_success_status(), 'Order Successful! From TriForce Interac Plugin');

                                                // Reduce stock levels
                                                $_Customer_Order->reduce_order_stock();

                                                // Complete Payment Again
                                                // $_Customer_Order->payment_complete();

                                                // Remove cart
                                                WC()->cart->empty_cart();
                                                error_log('checkOrderAfter(): Success Redirect: '.$_TriforcePay_Gateway->get_return_url( $_Customer_Order ),0);
                                                if ( wp_redirect( $_TriforcePay_Gateway->get_return_url( $_Customer_Order ) ) ) {
                                                        exit;
                                                }
											
                                        }
                                }
                        }

                        // Set Order to Failed
                        $_Customer_Order->update_status('wc-failed', 'Order Failed! From TriForce Interac Plugin');

                        // Remove cart
                        WC()->cart->empty_cart();
						
						error_log('checkOrderAfter(): Failure Redirect: '.$_TriforcePay_Gateway->get_return_url( $_Customer_Order ),0);
                        
						if ( wp_redirect( $_TriforcePay_Gateway->get_return_url( $_Customer_Order ) ) ) {
                                exit;
                        }
                }
        }

        /**
        * Retrieve $searchterm key contents from $querystring.
        * @param  String $query String $searchterm
        * @return string
        */
        function extractQuery($querystring, $searchterm) {
                $url = "http://e.ca/?".$querystring;
                $parts = parse_url($url);
                parse_str($parts['query'], $out);
                return $out[$searchterm];
        }


        /**
        * Process the payment and return the result.
        * @param  int $order_id
        * @return array
        */
        function process_payment( $order_id ) {
                $_User_ID = get_current_user_id();
                $_Customer_Order = wc_get_order( $order_id );

                // Check if retry
                $_Payment_Retries = get_post_meta($order_id, 'retries', true);
                if ( is_numeric( $_Payment_Retries ) ) {
                        $_Payment_Retries = $_Payment_Retries + 1;
                } else {
                        $_Payment_Retries = 0;
                }

                if ( $_Payment_Retries > 0 ) {
                        update_post_meta( $order_id, 'Transmit URL 1', '' );
                        update_post_meta( $order_id, 'Received Data 1', '' );
                        update_post_meta( $order_id, 'sec_id', '' );
                        update_post_meta( $order_id, 'Received Data 2', '' );
                        update_post_meta( $order_id, 'Start URL', '' );
                        update_post_meta( $order_id, 'Transmit URL 2', '' );

                        if ( 'failed' == $_Customer_Order->status ) {
                                $_Customer_Order->update_status('pending', 'Payment Retry '.$_Payment_Retries);
                        }
                }

                update_post_meta( $order_id, 'retries', $_Payment_Retries );

                // Set the Invoice Number Padding Based on Retries

                $_Retry_Num_Pad = sprintf("%'.03d", $_Payment_Retries);

                $_Invoice_Num = str_replace( "#", "", $_Customer_Order->get_order_number());

                $_Invoice_Num_Pad = sprintf("%".sprintf("%02d",7)."d", $_Invoice_Num);

                $_Return_URL = WP_PLUGIN_URL . '/' . plugin_basename( dirname( dirname( __FILE__ ) ) ) . 'pages/order-return.php';

                $_Order_Total = sprintf('%0.2f', $_Customer_Order->get_total());

				if ( $this->debugmode ) {
					error_log($_Return_URL,0);
				}
			
                $start_url = $this->get_transaction_url().'TERMID='.$this->termid.'&TYPE=W&PASS='.$this->pass.'&ACTION=StartSession&AMT='.$_Order_Total.'&INVOICE='.$_Retry_Num_Pad.$_Invoice_Num_Pad.'&CUSTEMAIL='.$_Customer_Order->billing_email;

				
                 if ( $this->testmode ) {
                 	$start_url = $start_url.'&SUCCESSURL='.$_Return_URL.'&FAILUREURL='.$_Return_URL;
				 }
			
				if ( $this->debugmode ) {
                        error_log($start_url,0);
                }

                update_post_meta( $order_id, 'Start URL', $start_url );

                $_TriforcePay_Response = wp_remote_get( $start_url, array( 'timeout' => 120, 'httpversion' => '1.1', 'sslverify' => true ));


                if ( ! is_wp_error( $_TriforcePay_Response ) ) {
                        update_post_meta( $order_id, 'Received Data 1', $_TriforcePay_Response['body'] );
						
						if ( $this->debugmode ) {
							error_log($_TriforcePay_Response['body']);
						}
                        
						$interpay_url = $this->extractQuery( str_replace("%38","&",$_TriforcePay_Response['body']) , 'URL').'&SecureTYPE=GET';
                        $this->secure_id = $this->extractQuery($_TriforcePay_Response['body'], 'SECUREID');
                        update_post_meta( $order_id, 'sec_id', $this->secure_id );
                        update_post_meta( $order_id, 'Transmit URL 1', $interpay_url );
                        
						if ( $this->debugmode ) {
							error_log("process_payment() SecureID = ".$this->secure_id ,0);
						}

                        if (!($_User_ID == 0)) {
                                update_user_meta( $_User_ID, '_current_secure_id', $this->secure_id );
                        }
                } else {
                        if ( $this->debugmode ) {
							error_log( $_TriforcePay_Response->get_error_message() ,0);
						}
                }

                if ( is_wp_error( $_TriforcePay_Response ) || ( strlen( $interpay_url ) < 15 ) ) {
                        // Return failure redirect
                        return array(
                                'result'    => 'failure',
                                'redirect'  => $this->get_return_url( $_Customer_Order )
                        );
                } else {
                        // Return thankyou redirect
                        return array(
                                'result'    => 'success',
                                'redirect'  => $interpay_url
                        );
                }
        }//process_payment
}       // End Class
?>
