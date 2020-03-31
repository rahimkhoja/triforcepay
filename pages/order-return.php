<?php
/**
 * InterPay Interac® Online Return Page.
 *
 * Is the return location from both successful and failed orders? Redirects page to the appropriate location.
 *
 * @version     0.0.5
 * @package     WooCommerce/Page/Payment
 * @author      TriForce Media
 */

/** Loads the WordPress Environment and Template */
require_once $_SERVER['DOCUMENT_ROOT'] . '/wp-blog-header.php';

error_log('Running InterPay Landing Page');

$_User_ID = get_current_user_id();

$_Interpay_Gateway = new WC_Gateway_InterPay();

$_User_Secure_ID = $_GET['SecureID'];

if (empty($_User_Secure_ID)) {
        $_User_Secure_ID = get_user_meta( $_User_ID, '_current_secure_id', true);
}

error_log('Order-Return->SecureID: '.$_User_Secure_ID,0 );

if ( (isset( $_User_Secure_ID )) &&  (strlen( $_User_Secure_ID ) > 1 ) ) {

        //$_Orders = get_posts(array('post_type' => 'shop_order'));

	$_Orders = wc_get_orders();

        foreach ($_Orders as $_Order) {
                $_Current_Order_ID = $_Order->ID;
                $_Current_Order_OBJ = new WC_Order($_Current_Order_ID);
                $_Order_Secure_ID = get_post_meta($_Current_Order_ID, 'sec_id', true);
                if ( $_Order_Secure_ID == $_User_Secure_ID) {
                error_log($_Interpay_Gateway->get_return_url( $_Current_Order_OBJ ),0);
                        if ( wp_redirect( $_Interpay_Gateway->get_return_url( $_Current_Order_OBJ ) ) ) {
                                exit;
                        }
                }
        }
}

// If it all goes wrong build redirect for Logged In and Logged Out Users. Cart for Logged Out, My Account for Logged In
if ( $_User_ID > 0) {
        $_Redirect_URL = wc_get_page_permalink( 'myaccount' );
} else {
        $_Redirect_URL = wc_get_page_permalink( 'cart' );
}
error_log('Failure Reirect: InterPay->OrderReturnPage: '.$_Redirect_URL,0);
// Redirect
if ( wp_redirect( $_Redirect_URL ) ) {
        exit;
}
?>