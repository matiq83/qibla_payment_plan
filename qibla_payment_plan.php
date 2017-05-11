<?php
/*
Plugin Name: Qibla Payment Plan
Description: Plugin  will enable payment plans through both credit card and PayPal on Woocommerce.
Version: 1.0.0
Author: Muhammad Atiq
*/

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
    echo "Hi there!  I'm just a plugin, not much I can do when called directly.";
    exit;
}

define( 'QPP_PLUGIN_NAME', 'Qibla Payment Plan' );
define( 'QPP_PLUGIN_PATH', plugin_dir_path(__FILE__) );
define( 'QPP_PLUGIN_URL', plugin_dir_url(__FILE__) );
define( 'QPP_SITE_BASE_URL',  rtrim(get_bloginfo('url'),"/")."/");

require_once QPP_PLUGIN_PATH.'includes/qpp_class.php';

register_activation_hook( __FILE__, array( 'QPP', 'qpp_install' ) );
register_deactivation_hook( __FILE__, array( 'QPP', 'qpp_uninstall' ) );

//add_filter( 'woocommerce_email_classes', 'qpp_add_pending_order_woocommerce_email' );
function qpp_add_pending_order_woocommerce_email( $email_classes ) {
    require_once QPP_PLUGIN_PATH.'includes/qpp_pending_order_mail_class.php';
    $email_classes['WC_Pending_Order_Email'] = new WC_Pending_Order_Email();
    return $email_classes;
}