<?php
/*
 * Plugin Name: BeSmartpay
 * Description: This customer can modified and will be use so on bank statement will show that name for that client.
 * Author: BeSmart
 * Author URI: https://yourblogcoach.com
 * Version: 1.0
 */
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;

add_action( 'plugins_loaded', 'initialize_gateway_class' );
function initialize_gateway_class() {
    class WC_besmart_Gateway extends WC_Payment_Gateway {

    public function __construct() {

        $this->id = 'besmart'; // payment gateway ID
        $this->icon = ''; // payment gateway icon
        $this->has_fields = true; // for custom credit card form
        $this->title = __( 'Besmart Gateway', 'text-domain' ); // vertical tab title
        $this->method_title = __( 'Besmart Gateway', 'text-domain' ); // payment method name
        $this->method_description = __( 'Custom Besmart payment gateway', 'text-domain' );
        $this->supports = array( 'default_credit_card_form' );
        // load backend options fields
        $this->init_form_fields();
        $this->init_settings();
        $this->title = $this->get_option( 'title' );
        $this->description = $this->get_option( 'description' );
        $this->enabled = $this->get_option( 'enabled' );
        $this->test_mode = 'yes' === $this->get_option( 'test_mode' );
        $this->private_key = $this->test_mode ? $this->get_option( 'test_private_key' ) : $this->get_option( 'private_key' );
        $this->publish_key = $this->test_mode ? $this->get_option( 'test_publish_key' ) : $this->get_option( 'publish_key' );
        if(is_admin()) {
              add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        }
        add_action( 'wp_enqueue_scripts', array( $this, 'payment_gateway_scripts' ) );
        add_action( 'woocommerce_api_connect', array( $this, 'webhook' ) );

     }       
       public function init_form_fields(){

        $this->form_fields = array(
            'enabled' => array(
                'title'       => __( 'Enable/Disable', 'text-domain' ),
                'label'       => __( 'Enable Besmart Gateway', 'text-domain' ),
                'type'        => 'checkbox',
                'description' => __( 'Powered by BeSmart Pay .', 'text-domain' ),
                'default'     => 'no',
                'desc_tip'    => true
            ),
            'title' => array(
                'title'       => __( 'Title', 'text-domain'),
                'type'        => 'text',
                'description' => __( 'This controls the title which the user sees during checkout.', 'text-domain' ),
                'default'     => __( 'Credit/Debit Card', 'text-domain' ),
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => __( 'Description', 'text-domain' ),
                'type'        => 'textarea',
                'description' => __( 'This controls the description which the user sees during checkout.', 'text-domain' ),
                'default'     => __( 'Pay with your credit card via our super-cool payment gateway.', 'text-domain' ),
            ),
            'test_mode' => array(
                'title'       => __( 'Test mode', 'text-domain' ),
                'label'       => __( 'Enable Test Mode', 'text-domain' ),
                'type'        => 'checkbox',
                'description' => __( 'Place the payment gateway in test mode using test API keys.', 'text-domain' ),
                'default'     => 'yes',
                'desc_tip'    => true,
            ),
            'test_publish_key' => array(
                'title'       => __( 'Test Publish Key', 'text-domain' ),
                'type'        => 'text'

            ),
            'test_private_key' => array(
                'title'       => __( 'Test Private Key', 'text-domain' ),
                'type'        => 'password',
            ),
            'publish_key' => array(
                'title'       => __( 'Live Publish Key', 'text-domain' ),
                'type'        => 'text'
            ),
            'private_key' => array(
                'title'       => __( 'Live Private Key', 'text-domain' ),
                'type'        => 'password'

            ),
            'customer_id' => array(
                'title'       => __( 'Customer Id', 'text-domain' ),
                'type'        => 'password'
            ),
         );
       } 
       public function validate_fields(){
            if( empty( $_POST[ 'card_name' ]) ) {
                wc_add_notice(  'please Enter the card Number', 'error' );
                return false;
            }
            if( empty( $_POST[ 'expiry_date' ]) ) {
                wc_add_notice( 'please Enter the Expiry Date', 'error' );
                return false;
            }
             if( empty( $_POST[ 'security_code' ]) ) {
                wc_add_notice(  'please Enter the Card Security Code', 'error' );
                return false;
            }
            return true;
        }
       public function payment_fields() {

            if ( $this->description ) {
                if ( $this->test_mode ) {
                    $this->description .= ''; 
                }
                echo wpautop( wp_kses_post( $this->description ) );
            }
            ?>
            <fieldset id="wc-<?php echo esc_attr( $this->id ); ?>-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">                  

                <?php do_action( 'woocommerce_credit_card_form_start', $this->id ); ?>   
                 
                <div class="form-row form-row-wide">
                    <label>Card Number <span class="required">*</span></label>
                    <input id="card-number" name="card_name" type="text" placeholder="1234 1234 1234 1234" autocomplete="off" maxlength="19">
                </div>
                <div class="form-row form-row-first">
                    <label>Expiry Date <span class="required">*</span></label>
                    <input id="card-exp" name="expiry_date" type="text" autocomplete="off" placeholder="MM / YY" maxlength='5'>
                </div>
                <div class="form-row form-row-last">
                    <label>Security Code <span class="required">*</span></label>
                    <input id="card-ccv" type="password" name="security_code" autocomplete="off" placeholder="CVC" maxlength="3">
                </div>
                <div class="clear"></div>
                 <span class="powered"> Powered by BeSmart Pay </span>
                <img src="/wp-content/plugins/beSmartPay/icon/logo.png" height="50px" width="50px">
                <?php do_action( 'woocommerce_credit_card_form_end', $this->id ); ?>
                <div class="clear"></div>
                <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
                 <script>
                  $(document).ready(function(){
                    $('#card-number').on('keypress change blur', function () {
                    $(this).val(function (index, value) {
                    return value.replace(/[^a-z0-9]+/gi, '').replace(/(.{4})/g, '$1 ');
                    });
                    });

                    $('#card-number').on('copy cut paste', function () {
                    setTimeout(function () {
                    $('#card-number').trigger("change");
                    });
                    });

                    $('#card-exp').on('input',function(){
                    var curLength = $(this).val().length;
                    if(curLength === 2){
                    var newInput = $(this).val();
                    newInput += '/';
                    $(this).val(newInput);
                    }
                    });
                  });
                 </script>
            </fieldset>
            <?php         
        }

     public function payment_gateway_scripts() {
        if ( ! is_cart() && ! is_checkout() && ! isset( $_GET['pay_for_order'] ) ) {
        return;
        }
        if ( 'no' === $this->enabled ) {
        return;
        }
        if ( empty( $this->private_key ) || empty( $this->publishable_key ) ) {
        return;
        }
        if ( ! $this->test_mode ) {
        return;
        }
        if ( ! is_ssl() ) {
        return;
        }
        wp_enqueue_script( 'ybc_js', 'https://www.example.com/api/get-token.js' );
        wp_register_script( 'woocommerce_pay_ybc', plugins_url( 'token-script.js', __FILE__ ), array( 'jquery', 'ybc_js' ) );
        wp_localize_script( 'woocommerce_pay_ybc', 'ybc_params', array('publishKey' => $this->publish_key) );
        wp_enqueue_script( 'woocommerce_pay_ybc' );
    }  
      public function process_payment( $order_id ){

       global $woocommerce;

       /****** Get The Customer Order Details  ******/ 

       $customer_order = new WC_Order($order_id);
      
      /**** Get Card Expiry Month and Year ******/

      $str = $_POST['expiry_date'];
      $date_data = explode("/",$str);
      $month = $date_data[0];
      $year = $date_data[1];
      
      /***** Get Statement Descriptor ******/  

      $order   = "Order";
      $statement =  $order.''.$order_id;
      $getsitetitle = get_bloginfo();
      $arr_data = array($getsitetitle,$statement);
      $main_statement = implode("-",$arr_data);

       /*  Get the same price with the stripe  */

      $get_rate  = floatval($customer_order->order_total)*100;
        
     /***** Get application Fee Amount Percentage ******/

      $application_fee = (4.5/100)*$get_rate;

      /****** Get the Customer id From the backend *******/

      $this->customer_id =  $this->get_option( 'customer_id' );
     
      $environment = ($this->environment == "yes") ? 'TRUE' : 'FALSE';
      $environment_url = ("FALSE" == $environment)
          ? 'https://buildmyownweb.com/webhook'
          : 'https://buildmyownweb.com/webhook';

       echo '<script src="https://js.stripe.com/v3/"></script>'; 
          require_once('stripe/vendor/autoload.php');

        \Stripe\Stripe::setApiKey('sk_test_51IGQxoAVYXU25aJ6Je71QDkHF2vkUMB0ZWhBBf9oGsKblbaAYxVDrSp0hy8cZtPnNFxqb8TsmP1nuBVZuZ2mCjre00Y7qm2uKn');
        //$token = $_GET['stripeToken'];
       $customer = \Stripe\Customer::create(
       [ 
      'email' => $customer_order->billing_email,
      'name' => $customer_order->billing_first_name,
       ] 
     ); 

      $method = \Stripe\PaymentMethod::create([
     'type' => 'card',
     'card' => [
      'number' => $_POST['card_name'],
      'exp_month' => $month,
      'exp_year' => $year,
      'cvc' => $_POST['security_code'],
     ],
    ]);
       
    $maincreate = \Stripe\PaymentIntent::create([ 
      'customer' => $customer->Id,
      'setup_future_usage' => 'off_session',
      'payment_method_types' => ['card'],
      'off_session' => true,
      'confirm' => true,
      'payment_method' => $method->id,
      'amount' =>  $get_rate,
      'currency' => 'usd',
      'statement_descriptor' => $main_statement,
      'metadata' => [
      'order_id' => $order_id,
       ],
      'application_fee_amount' => $application_fee,
      'transfer_data' => [
      'destination' => $this->customer_id,
      ],
    ]);

      /*$charge = \Stripe\Charge::create([
      "amount" => floatval($customer_order->order_total),
      "currency" => "usd",
      "source" => $maincreate->payment_method,
      "application_fee_amount" => $application_fee,
      "transfer_data" => [
      "destination" => $this->customer_id,
      //"source" => $maincreate->payment_method,
      ],
     ]);
    */
  $stripes = new \Stripe\StripeClient(
    'sk_test_51IGQxoAVYXU25aJ6Je71QDkHF2vkUMB0ZWhBBf9oGsKblbaAYxVDrSp0hy8cZtPnNFxqb8TsmP1nuBVZuZ2mCjre00Y7qm2uKn'
    );
  $mainconfirm = $stripes->paymentIntents->confirm(
     $maincreate->id,
    ['payment_method' => 'pm_card_visa']
    );
      echo $mainconfirm;
      $response = wp_remote_post($environment_url, [
          'method' => 'POST',
          'body' => http_build_query($mainconfirm),
          'timeout' => 90,
          'sslverify' => false,
      ]);
     
      if (is_wp_error($response)) {
          throw new Exception(__('There is issue for connectin payment gateway. Sorry for the inconvenience.',
              'wc-gateway-nequi'));
      }
      if (empty($response['body'])) {
          throw new Exception(__('Authorize.net\'s Response was not get any data.', 'wc-gateway-nequi'));
      }
      $response_body = wp_remote_retrieve_body($response);
      foreach (preg_split("/\r?\n/", $response_body) as $line) {
          $resp = explode("|", $line);
      }
      $r['StatusCode'] = $resp[0];
      $r['StatusDesc'] = $resp[1];
      // 1 or 4 means the transaction was a success
      if (($r['StatusCode'] == 0)) {
          $customer_order->add_order_note(__('Authorize.net complete payment.', 'wc-gateway-nequi'));
          $customer_order->payment_complete();
          $woocommerce->cart->empty_cart();
          return [
              'result' => 'success',
              'redirect' => $this->get_return_url($customer_order),
          ]; 
       }
     }
        public function webhook() {
        $order = wc_get_order( $_GET['id'] );
        $order->payment_complete();
        $order->reduce_order_stock();
        update_option('webhook_debug', $_GET);
        }
   }
}
add_filter( 'woocommerce_payment_gateways', 'beSmart_Pay' );
function beSmart_Pay( $gateways ) {
    $gateways[] = 'WC_besmart_Gateway';
    return $gateways;
}


