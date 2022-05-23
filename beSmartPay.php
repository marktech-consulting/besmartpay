<?php
/*
 * Plugin Name: BeSmartPay
 * Description: This customer can modified and will be use so on bank statement will show that name for that client.
 * Author: BeSmart
 * Author URI: https://yourblogcoach.com
 * Version: 1.0.0
 */


/* Payment intigration with woocommerce */
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;

add_action( 'plugins_loaded', 'initialize_gateway_class' );
function initialize_gateway_class() {
	
        include('besmart_payment.php');
}

require_once('classes/UpdateClient.class.php');
/* Creating Database Table */
register_activation_hook(__file__, 'besmart_dbtable');

function besmart_dbtable()
{

    global $wpdb;
$table_name = $wpdb->prefix . "besmart_token_meta";
$my_products_dbversion = '1.0.0';
$charset_collate = $wpdb->get_charset_collate();

if ( $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name ) {

    $sqlq = "CREATE TABLE $table_name (
            id int(10) NOT NULL AUTO_INCREMENT,
            `customer_id` varchar(255) NOT NULL,
            `card_id` varchar(255) NOT NULL,
            `transaction_id` varchar(255) NOT NULL,
            `four_digit` varchar(255) NOT NULL,
            `cvc` varchar(255) NOT NULL,
            `expiry_date` varchar(255) NOT NULL,
            `user_uniqe_id` varchar(255) NOT NULL,
            `user_id` varchar(255) NOT NULL,
            PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sqlq);
    add_option('my_db_version', $my_products_dbversion);
}

}
?>