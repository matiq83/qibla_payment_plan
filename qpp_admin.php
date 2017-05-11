<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Class that will hold functionality for admin side
 *
 * PHP version 5
 *
 * @category   Admin Side Code
 * @package    Qibla Payment Plan
 * @author     Muhammad Atiq
 * @version    1.0.0
 * @since      File available since Release 1.0.0
*/

class QPP_Admin extends QPP
{
    //Admin side starting point. Will call appropriate admin side hooks
    public function __construct() {
        
        do_action('qpp_before_admin', $this );
        //All admin side code will go here
        
        //add_action( 'admin_menu', array( $this, 'qpp_admin_menus' ) );    
        add_action( 'woocommerce_admin_order_totals_after_total', array( $this, 'qpp_woocommerce_admin_order_totals_after_total' ), 10, 1 );        
        add_filter( 'manage_edit-shop_order_columns', array( $this, 'qpp_woo_admin_order_columns' ), 10, 1 );        
        add_action( 'manage_shop_order_posts_custom_column', array( $this, 'qpp_woo_manage_admin_order_columns' ), 2, 1 );
        
        do_action('qpp_after_admin', $this );            
    }
    
    public function qpp_woo_manage_admin_order_columns( $column ) {
        
        global $post;
        $data = get_post_meta( $post->ID );
        
        if ( $column == 'payment_made' ) {
            $order_id = $post->ID;
            $order  = wc_get_order( $order_id );
            $percentage_made = get_post_meta( $order_id, '_qpp_make_payment', true );
            if( empty($percentage_made) ) {
                $percentage_made = get_post_meta( $order_id, '_qpp_paypal_make_payment', true );
            }
            $order_total = get_post_meta( $order_id, '_order_total', true );
            if( empty($percentage_made) ) {
                $payment_made = $order_total;
            }else{
                $payment_made = round($order_total*$percentage_made/100,2);
            }
            echo wc_price( $payment_made, array( 'currency' => $order->get_order_currency() ) );
        }
        if ( $column == 'payment_remaining' ) {
            
            $order_id = $post->ID;
            $order  = wc_get_order( $order_id );
            $percentage_made = get_post_meta( $order_id, '_qpp_make_payment', true );
            if( empty($percentage_made) ) {
                $percentage_made = get_post_meta( $order_id, '_qpp_paypal_make_payment', true );
            }
            $order_total = get_post_meta( $order_id, '_order_total', true );
            $percentage_remaining = 100-$percentage_made;
            if( empty($percentage_made) ) {
                $payment_made = $order_total;
            }else{
                $payment_made = round($order_total*$percentage_made/100,2);
            }
            $payment_remaining = $order_total - $payment_made;
            
            echo wc_price( $payment_remaining, array( 'currency' => $order->get_order_currency() ) );
        }
    }
    
    public function qpp_woo_admin_order_columns( $columns ) {
        
        $more_columns = (is_array($columns)) ? $columns : array();
        unset( $more_columns['order_actions'] );
        unset( $more_columns['order_total'] );
        
        $more_columns['payment_made'] = 'Paid';
        $more_columns['payment_remaining'] = 'Remaining Payment';
        
        $more_columns['order_total'] = $columns['order_total'];
        $more_columns['order_actions'] = $columns['order_actions'];
        
        return $more_columns;
    }
    
    public function qpp_woocommerce_admin_order_totals_after_total( $order_id ) {
        
        $order  = wc_get_order( $order_id );
        $percentage_made = get_post_meta( $order_id, '_qpp_make_payment', true );
        if( empty($percentage_made) ) {
            $percentage_made = get_post_meta( $order_id, '_qpp_paypal_make_payment', true );
        }
        $order_total = get_post_meta( $order_id, '_order_total', true );
        $percentage_remaining = 100-$percentage_made;
        if( empty($percentage_made) ) {
            $payment_made = $order_total;
        }else{
            $payment_made = round($order_total*$percentage_made/100,2);
        }
        $payment_remaining = $order_total - $payment_made;
        echo '<tr>
			<td class="label">Payment Made:</td>
			<td width="1%"></td>
			<td class="total">'.wc_price( $payment_made, array( 'currency' => $order->get_order_currency() ) ).'</td>
		</tr>';
        echo '<tr>
			<td class="label">Payment Remaining:</td>
			<td width="1%"></td>
			<td class="total">'.wc_price( $payment_remaining, array( 'currency' => $order->get_order_currency() ) ).'</td>
		</tr>';
    }
    
    public function qpp_admin_menus(){
        
        add_menu_page( QPP_PLUGIN_NAME, QPP_PLUGIN_NAME, 'manage_options', 'qpp_settings', array( $this, 'qpp_settings' ) );
    }    
    
    public function qpp_settings() {
        
        if( isset($_POST['btnsave']) && $_POST['btnsave'] != "" ) {
            
            $exclude = array('btnsave');
            $options = array();
            
            foreach( $_POST as $k => $v ) {
                if( !in_array( $k, $exclude )) {
                    $options[$k] = $v;
                }
            }
            
            update_option( 'qpp_settings', $options );
            $message = 'Settings Saved Successfully!';
        }
        
        $options = get_option( 'qpp_settings' );
        
        require_once QPP_PLUGIN_PATH.'templates/admin/settings.php';
        $this->load_wp_media_uploader();
    }
    
    private function load_wp_media_uploader() {
        
        wp_enqueue_script('media-upload');
    	wp_enqueue_script('thickbox');
    	wp_enqueue_style('thickbox');
        
        $this->load_javascript();
    }
    
    private function load_javascript() {
        $html = '';
        ob_start();
        require_once QPP_PLUGIN_PATH.'templates/admin/load_media_upload_js.php';
        $html = ob_get_contents();
        ob_end_clean();
        echo $html;
    }
}

$qpp_admin = new QPP_Admin();