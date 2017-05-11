<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
//(567) 510-0060 .... +15675100060
/**
 * Class that will hold functionality for front side
 *
 * PHP version 5
 *
 * @category   Front Side Code
 * @package    Qibla Payment Plan
 * @author     Muhammad Atiq
 * @version    1.0.0
 * @since      File available since Release 1.0.0
*/

class QPP_Front extends QPP
{
    //Front side starting point. Will call appropriate front side hooks
    public function __construct() {
        
        do_action('qpp_before_front', $this );
        
        //All front side code will go here
        add_action( 'woocommerce_review_order_after_order_total', array( $this, 'qpp_woocommerce_review_order_after_order_total' ) );
        add_filter( 'woocommerce_paypal_args', array( $this, 'qpp_woocommerce_paypal_args' ), 20, 1 );
        add_filter( 'woocommerce_order_amount_total', array( $this, 'qpp_woocommerce_order_amount_total' ), 10, 2 );
        add_action( 'woocommerce_checkout_order_processed', array( $this, 'qpp_woocommerce_checkout_order_processed' ),10, 2 );
        add_filter( 'woocommerce_get_order_item_totals', array( $this, 'qpp_woocommerce_get_order_item_totals' ), 10, 2 );
        add_action( 'paypal_ipn_for_wordpress_valid_ipn_request', array( $this, 'qpp_paypal_payment_completed' ), 10, 1 );
        add_action( 'paypal_ipn_for_wordpress_payment_status_completed', array( $this, 'qpp_paypal_payment_completed' ), 10, 1 );
        add_action( 'woocommerce_order_status_completed', array( $this, 'qpp_wc_order_status_completed' ), 10, 1 );
        add_action( 'woocommerce_new_order_item', array( $this, 'qpp_woocommerce_new_order_item' ), 10, 3 );
        add_filter( 'wc_stripe_generate_payment_request', array( $this, 'qpp_wc_stripe_generate_payment_request' ), 10, 3 );        
        add_action( 'woocommerce_api_wc_gateway_stripe', array( $this, 'qpp_check_stripe_response' ), 10 );
        do_action('qpp_after_front', $this );
    }
    
    public function qpp_check_stripe_response() {
        
        $options = get_option( 'woocommerce_stripe_settings' );
        $api_key = '';
        if ( isset( $options['testmode'], $options['secret_key'], $options['test_secret_key'] ) ) {
            if( 'yes' === $options['testmode'] ) {
                $api_key = $options['test_secret_key'];
            }else{
                $api_key = $options['secret_key'];
            }
        }
        if( !empty($api_key) ) {
            require_once( QPP_PLUGIN_PATH.'includes/stripe/init.php');
            \Stripe\Stripe::setApiKey($api_key);

            // Retrieve the request's body and parse it as JSON
            $input = @file_get_contents("php://input");
            $event_json = json_decode($input);
            // Verify the event by fetching it from Stripe
            $event = \Stripe\Event::retrieve($event_json->id);
            $data = json_decode($event->__toJSON(),true);            
            //delete_option( '_qpp_stripe_response' );
            if( $data['type'] == 'customer.subscription.created' ) {
                $subscription = $data['data']['object'];
                $plan = $subscription['plan'];
                $order_id = $plan['metadata']['order'];
                update_post_meta( $order_id, '_qpp_make_payment', 50 );                
            }elseif( $data['type'] == 'customer.subscription.updated' ){
                $subscription = $data['data']['object'];
                $plan = $subscription['plan'];
                $order_id = $plan['metadata']['order'];
                $order = wc_get_order( $order_id );
                $order->update_status( 'completed' );
                update_post_meta( $order_id, '_qpp_make_payment', 100 );
            }
            http_response_code(200); // PHP 5.4 or greater
        }
    }
    
    public function qpp_send_subscription_success_mail( $order_id ) {
        $mailer = WC()->mailer();
        $mails = $mailer->get_emails();
        if ( ! empty( $mails ) ) {
            foreach ( $mails as $mail ) {
                if ( $mail->id == 'customer_processing_order' ) {
                   $mail->trigger( $order_id );
                }
             }
        }
    }
    
    public function qpp_wc_stripe_generate_payment_request( $post_data, $order, $source ) {
        if( isset($_REQUEST['qpp_make_payment']) && !empty($_REQUEST['qpp_make_payment']) && is_numeric( $_REQUEST['qpp_make_payment'] ) ) {
            if( $_REQUEST['payment_method'] != 'paypal' ) {
                update_post_meta( $order->id, '_qpp_make_payment', $_REQUEST['qpp_make_payment']);
            }else{
                update_post_meta( $order->id, '_qpp_paypal_make_payment', $_REQUEST['qpp_make_payment']);
            }
            $percentage_made = $_REQUEST['qpp_make_payment'];
        }
        if( !empty($percentage_made) ) {
            $options = get_option( 'woocommerce_stripe_settings' );
            $api_key = '';
            if ( isset( $options['testmode'], $options['secret_key'], $options['test_secret_key'] ) ) {
                if( 'yes' === $options['testmode'] ) {
                    $api_key = $options['test_secret_key'];
                }else{
                    $api_key = $options['secret_key'];
                }
            }
            if( !empty($api_key) ) {

                require_once( QPP_PLUGIN_PATH.'includes/stripe/init.php');

                \Stripe\Stripe::setApiKey($api_key);

                $plan_id = str_replace( " ", "-", strtolower($post_data['description']) );
                $customer = wp_get_current_user();
                $stripe_customer_id = get_user_meta( $customer->ID, 'stripe_customer_id', true );
                $stripe_subscription_id = get_user_meta( $customer->ID, 'stripe_sub_for_'.$plan_id, true );

                try{
                    $plan = \Stripe\Plan::retrieve($plan_id);
                } catch (Stripe\Error\Base $e) {
                     try{
                        
                        $plan_data = array(
                            "name" => $post_data['description'],//"Qibla Course Plan For Order#".$order->get_order_number(),
                            "id" => $plan_id,
                            "interval" => "month",
                            "currency" => "usd",
                            "amount" => $post_data['amount'],
                            "metadata" => array('order'=>$order->id),
                          );
                        $plan = \Stripe\Plan::create($plan_data);
                     } catch (Stripe\Error\Base $e) {
                         echo($e->getMessage());
                     }
                }

                if( empty($stripe_customer_id) ) {
                    try{
                        $stripe_customer = \Stripe\Customer::create(array(
                            "email" => $customer->user_email,
                            "source" => $source->source
                        ));
                    } catch (Stripe\Error\Base $e) {
                         echo($e->getMessage());
                    }
                    $stripe_customer_id = $stripe_customer->id;
                    update_user_meta( $customer->ID, 'stripe_customer_id', $stripe_customer_id );
                }

                if( empty($stripe_subscription_id) ) {
                    try{
                       $subscription = \Stripe\Subscription::create(array(
                        "customer" => $stripe_customer_id,
                        "plan" => $plan_id
                      )); 
                    } catch (Stripe\Error\Base $e) {
                         echo($e->getMessage());
                    }
                    update_user_meta( $customer->ID, 'stripe_sub_for_'.$plan_id, $subscription->id );                    
                }

                //$order->payment_complete( $subscription->id );
                
                update_post_meta( $order->id, '_stripe_charge_id', $subscription->id );
                update_post_meta( $order->id, '_stripe_charge_subscription', 'yes' );
		update_post_meta( $order->id, '_stripe_charge_captured', $subscription->captured ? 'yes' : 'no' );
                
                $message = sprintf( __( 'Stripe charge complete (Charge ID: %s)', 'woocommerce-gateway-stripe' ), $subscription->id );
                $order->add_order_note( $message );
                
                $this->qpp_send_subscription_success_mail( $order->id );
                
                WC()->cart->empty_cart();
                do_action( 'wc_gateway_stripe_process_payment', $subscription, $order );

                if ( $order ) {
                    $return_url = $order->get_checkout_order_received_url();
                } else {
                    $return_url = wc_get_endpoint_url( 'order-received', '', wc_get_page_permalink( 'checkout' ) );
                }

                if ( is_ssl() || get_option('woocommerce_force_ssl_checkout') == 'yes' ) {
                    $return_url = str_replace( 'http:', 'https:', $return_url );
                }

                $return_url = apply_filters( 'woocommerce_get_return_url', $return_url, $order );
                // Return thank you page redirect.
                $result = array(
                            'result'   => 'success',
                            'redirect' => $return_url
                            );
                // Redirect to success/confirmation/payment page
                if ( isset( $result['result'] ) && 'success' === $result['result'] ) {
                    $result = apply_filters( 'woocommerce_payment_successful_result', $result, $order->id );
                    if ( is_ajax() ) {
                        wp_send_json( $result );
                    } else {
                        wp_redirect( $result['redirect'] );
                        exit;
                    }
                }
                exit();
            }
        }
        return $post_data;
    }
    
    public function qpp_cancel_stripe_plan( $order, $payment_gateway ) {
        
        $options = get_option( 'woocommerce_stripe_settings' );
        $api_key = '';
        if ( isset( $options['testmode'], $options['secret_key'], $options['test_secret_key'] ) ) {
            if( 'yes' === $options['testmode'] ) {
                $api_key = $options['test_secret_key'];
            }else{
                $api_key = $options['secret_key'];
            }
        }
        if( !empty($api_key) ) {

            require_once( QPP_PLUGIN_PATH.'includes/stripe/init.php');

            \Stripe\Stripe::setApiKey($api_key);
            
            $sub_id = get_post_meta( $order->id, '_stripe_charge_id', true );
            $sub = \Stripe\Subscription::retrieve($sub_id);
            $response = $sub->cancel();
        }
    }
    
    public function qpp_wc_order_status_completed( $order_id ) {
        
        $order = new WC_Order( $order_id );
        $payment_gateway = wc_get_payment_gateway_by_order( $order );
        if( $payment_gateway->id == 'paypal' ) {
            $posted = get_post_meta( $order_id, 'qpp_recurring_data', true );
            if( !empty($posted) ) {
                update_post_meta( $order_id, '_qpp_paypal_make_payment', 100 );
                //$this->qpp_cancel_paypal_plan( $posted, $payment_gateway );// Payment made locally so cancel the payment plan if scheduled                
            }
        }elseif( $payment_gateway->id == 'stripe' ) {
            $stripe_is_subscription = get_post_meat( $order_id, '_stripe_charge_subscription', true );
            if( $stripe_is_subscription == 'yes' ) {
                update_post_meta( $order_id, '_qpp_make_payment', 100 );
                //$this->qpp_cancel_stripe_plan( $order,$payment_gateway );// Payment made locally so cancel the payment plan if scheduled
            }
        }
    }
    
    private function qpp_cancel_paypal_plan( $posted, $payment_gateway ) {
        
        $api_request = 'USER=' . urlencode( $payment_gateway->settings['api_username'] )
                .  '&PWD=' . urlencode( $payment_gateway->settings['api_password'] )
                .  '&SIGNATURE=' . urlencode( $payment_gateway->settings['api_signature'] )
                .  '&VERSION=76.0'
                .  '&METHOD=ManageRecurringPaymentsProfileStatus'
                .  '&PROFILEID=' . urlencode( $posted['recurring_payment_id'] )
                .  '&ACTION=' . urlencode( 'Cancel' )
                .  '&NOTE=' . urlencode( 'Profile cancelled at store' );
        
        $paypal_api_url = 'https://api-3t.paypal.com/nvp';
        
        if( $payment_gateway->settings['testmode'] == 'yes' ) {
            $paypal_api_url = 'https://api-3t.sandbox.paypal.com/nvp';
        }
        
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $paypal_api_url );
        curl_setopt( $ch, CURLOPT_VERBOSE, 1 );

        // Uncomment these to turn off server and peer verification
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_POST, 1 );

        // Set the API parameters for this transaction
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $api_request );

        // Request response from PayPal
        $response = curl_exec( $ch );

        // If no response was received from PayPal there is no point parsing the response
        if( ! $response )
            die( 'Calling PayPal to change_subscription_status failed: ' . curl_error( $ch ) . '(' . curl_errno( $ch ) . ')' );

        curl_close( $ch );

        // An associative array is more usable than a parameter string
        parse_str( $response, $parsed_response );
        
        return $parsed_response;
    }
    
    public function qpp_woocommerce_new_order_item( $item_id, $item, $order_id ) {
        $item['order_item_name'] = strip_tags($item['order_item_name']);
        $this->qpp_update_record( 'woocommerce_order_items', $item, "order_item_id = '".$item_id."'" );        
    }
    
    public function qpp_paypal_payment_completed( $posted ) {
        $custom_str = stripcslashes($posted['custom']);
        $custom = json_decode($custom_str,true);
        $order_id = $custom['order_id'];
        if( $posted['txn_type'] == 'recurring_payment' || $posted['txn_type'] == 'recurring_payment_profile_created' ) {      
            $recurring_data = get_post_meta( $order_id, 'qpp_recurring_data', true );
            if( !empty($recurring_data) ) {
                $recurring_data[] = $posted;
            }else{
                $recurring_data = array($posted);
            }
            update_post_meta( $order_id, 'qpp_recurring_data', $posted );
            if(!isset($posted['next_payment_date'])) {
                $order = wc_get_order( $order_id );
                $order->update_status( 'completed' );
                update_post_meta( $order_id, '_qpp_paypal_make_payment', 100 );
            }else{
                $this->qpp_send_subscription_success_mail( $order_id );
                update_post_meta( $order_id, '_qpp_paypal_make_payment', 50 );
            }
        }        
        update_option( 'qpp_paypal_data', $posted );
    }
    
    public function qpp_woocommerce_get_order_item_totals( $total_rows, $order ) {
        
        $order_id  = $order->id;
        
        $percentage_made = get_post_meta( $order_id, '_qpp_make_payment', true );
        if( empty($percentage_made) ) {
            $percentage_made = get_post_meta( $order_id, '_qpp_paypal_make_payment', true );
        }
        $order_total = get_post_meta( $order_id, '_order_total', true );
        $percentage_remaining = 100-$percentage_made;
        
        $payment_made = round($order_total*$percentage_made/100,2);
        $payment_remaining = $order_total - $payment_made;
        
        $total_rows['order_total_paid'] = array(
			'label' => __( 'Payment Made:', 'woocommerce' ),
			'value'	=> wc_price( $payment_made, array( 'currency' => $order->get_order_currency() ) )
		);
        $total_rows['order_total_unpaid'] = array(
			'label' => __( 'Payment Remaining:', 'woocommerce' ),
			'value'	=> wc_price( $payment_remaining, array( 'currency' => $order->get_order_currency() ) )
		);
        
        return $total_rows;
    }
    
    public function qpp_woocommerce_checkout_order_processed( $order_id, $posted_data ) {
        
        if( isset($_REQUEST['qpp_make_payment']) && !empty($_REQUEST['qpp_make_payment']) && is_numeric( $_REQUEST['qpp_make_payment'] ) ) {
            if( $_REQUEST['payment_method'] != 'paypal' ) {
                update_post_meta( $order_id, '_qpp_make_payment', $_REQUEST['qpp_make_payment']);
            }else{
                update_post_meta( $order_id, '_qpp_paypal_make_payment', $_REQUEST['qpp_make_payment']);
            }
        }
    }
    
    public function qpp_woocommerce_order_amount_total( $total, $order_obj ) {
        
        if( isset($_REQUEST['qpp_make_payment']) && !empty($_REQUEST['qpp_make_payment']) && is_numeric( $_REQUEST['qpp_make_payment'] ) ) {
            if( $_REQUEST['payment_method'] != 'paypal' ) {
                $percentage = (int)$_REQUEST['qpp_make_payment'];
                $total = round($total*$percentage/100,2);            
            }else{
                update_post_meta( $order_id, '_qpp_paypal_make_payment', $_REQUEST['qpp_make_payment']);
            }
        }
        return $total;
    }
    
    public function qpp_woocommerce_paypal_args( $paypal_args ) {
        if( !empty($_REQUEST['qpp_make_payment']) ) {
            
            $percentage = (int)$_REQUEST['qpp_make_payment'];  
            
            if( $percentage < 100 ) {
                $i=1;
                $total = 0;
                $item_names = array();
                while (isset($paypal_args['amount_' . $i])) { 
                    $item_names[] = strip_tags($paypal_args['item_name_'.$i]);

                    $total = $total+$paypal_args['amount_' . $i];
                    ++$i;  
                }
                if( !empty($paypal_args['shipping_1']) ) {
                    $total = $total+$paypal_args['shipping_1'];
                }
                if( !empty($paypal_args['tax_cart']) ) {
                    $total = $total+$paypal_args['tax_cart'];
                }
                if( isset( $paypal_args['discount_amount_cart'] ) ) {
                    $total = $total - $paypal_args['discount_amount_cart'];
                }
                $installment = round(($total*$percentage/100),2);
                $remaining_amount = $total-$installment;

                $paypal_args['cmd'] = '_xclick-payment-plan';
                $paypal_args['disp_tot'] = 'Y';
                $paypal_args['option_select0'] = 'pay-in-2';
                $paypal_args['option_select0_type'] = 'E';
                $paypal_args['option_select0_name'] = 'Pay in 2 installments.';
                $paypal_args['option_select0_a0'] = $installment;
                $paypal_args['option_select0_p0'] = 1;
                $paypal_args['option_select0_t0'] = 'M';
                $paypal_args['option_select0_n0'] = 1;

                $paypal_args['option_select0_a1'] = $remaining_amount;
                $paypal_args['option_select0_p1'] = 1;
                $paypal_args['option_select0_t1'] = 'M';
                $paypal_args['option_select0_n1'] = 1;

                $paypal_args['option_index'] = 0;
                $paypal_args['on0'] = 'plan';
                $paypal_args['os0'] = 'pay-in-2';

                $paypal_args['item_name'] = 'Payment for the following products: '.implode(', ',$item_names);
                
                //$paypal_args['notify_url'] = QPP_SITE_BASE_URL.'?wc-api=WC_Gateway_Paypal';
                $paypal_args['return'] = $paypal_args['return'].'='.$paypal_args['order']; 
                $paypal_args['rm'] = 2;
            }
        }
        
        return $paypal_args;
    }
    
    public function qpp_woocommerce_review_order_after_order_total() {
        
        echo '<tr class="cart_item qpp_payment_plan">';
        echo '<td class="product-name">';
        echo 'Payment Plan (<a href="/payment-plan/" target="_blank">click here</a> for info)';
        echo '</td>';
        echo '<td class="product-total">';
        echo '<input type="checkbox" class="qpp_make_payment" name="qpp_make_payment" value="50" onclick="javascript: qpp_set_price(this);" /> 50% Payment';
        echo '</td>';
        echo '</tr>';
    }
    
}

$qpp_front = new QPP_Front();