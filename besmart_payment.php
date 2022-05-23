<?php
class WC_besmart_Gateway extends WC_Payment_Gateway
{

    public function __construct()
    {  

        $this->id = 'besmart'; // payment gateway ID
        $this->icon = ''; // payment gateway icon
        $this->has_fields = true; // for custom credit card form
        $this->title = __('Besmart Gateway', 'text-domain'); // vertical tab title
        $this->method_title = __('Besmart Gateway', 'text-domain'); // payment method name
        $this->method_description = __('In order to use our plugin you need to register on https://besmart.pay/register --> this is an example Then go to My Profile --> Copy AccountID Then start using your BeSmart Payment Gateway', 'text-domain');
        $this->supports = array(
            'default_credit_card_form'
        );
        // load backend options fields
        $this->init_form_fields();
        $this->init_settings();
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->test_mode = 'yes' === $this->get_option('test_mode');
        $this->save_card = 'yes' === $this->get_option('save_card');
        $this->private_key = $this->test_mode ? $this->get_option('test_private_key') : $this->get_option('private_key');
        $this->publish_key = $this->test_mode ? $this->get_option('test_publish_key') : $this->get_option('publish_key');
        if (is_admin())
        {
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(
                $this,
                'process_admin_options'
            ));
        }
        add_action('wp_enqueue_scripts', array(
            $this,
            'payment_gateway_scripts'
        ));
        add_action('woocommerce_api_connect', array(
            $this,
            'webhook'
        ));

    }
    public function init_form_fields()
    {

        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'text-domain') ,
                'label' => __('Enable Besmart Gateway', 'text-domain') ,
                'type' => 'checkbox',
                'description' => __('Powered by BeSmart Pay .', 'text-domain') ,
                'default' => 'no',
                'desc_tip' => true
            ) ,
            'title' => array(
                'title' => __('Title', 'text-domain') ,
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'text-domain') ,
                'default' => __('Credit/Debit Card', 'text-domain') ,
                'desc_tip' => true,
            ) ,
            'description' => array(
                'title' => __('Description', 'text-domain') ,
                'type' => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'text-domain') ,
                'default' => __('Pay with your credit card via our super-cool payment gateway.', 'text-domain') ,
            ) ,
            'test_mode' => array(
                'title' => __('Test mode', 'text-domain') ,
                'label' => __('Enable Test Mode', 'text-domain') ,
                'type' => 'checkbox',
                'description' => __('Place the payment gateway in test mode using test API keys.', 'text-domain') ,
                'default' => 'yes',
                'desc_tip' => true,
            ) ,
            'statement' => array(
                'title' => __('Customer Bank Statement', 'text-domain') ,
                'type' => 'text'

            ) ,
            'customer_id' => array(
                'title' => __('Customer Id', 'text-domain') ,
                'type' => 'text'
            ) ,
            'save_card' => array(
                'title' => __('Save Card', 'text-domain') ,
                'label' => __('Enable Save Card', 'text-domain') ,
                'type' => 'checkbox',
                //    'description' => __( 'Place the payment gateway in test mode using test API keys.', 'text-domain' ),
                'default' => 'yes',
                'desc_mode' => true,
            ) ,

        );
    }

    public function validate_fields()
    {
        if ($_POST['search_type'] != "B")
        {
            return true;
        }
        else
        {
            if (empty($_POST['card_name']))
            {
                wc_add_notice('please Enter the card Number', 'error');
                return false;
            }
            if (empty($_POST['expiry_date']))
            {
                wc_add_notice('please Enter the Expiry Date', 'error');
                return false;
            }
            if (empty($_POST['security_code']))
            {
                wc_add_notice('please Enter the Card Security Code', 'error');
                return false;
            }
            return true;
        }
    }
    public function payment_fields()
    {

        if ($this->description)
        {
            if ($this->test_mode)
            {
                $this->description .= '';
            }
            echo wpautop(wp_kses_post($this->description));
        }

        if ($this->test_mode == 1)
        {

        }
        global $wpdb, $current_user;
        $userid = $current_user->ID;
        $retrieve_data = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "besmart_token_meta where user_id = " . $userid);
        $i = 0;
        foreach ($retrieve_data as $retrieved_data)
        {
            $customer_id = $retrieved_data->customer_id;
            $user_uniqe_id = $retrieved_data->user_uniqe_id;
            echo '<script src="https://js.stripe.com/v3/"></script>';
            require_once ('stripe/vendor/autoload.php');

            $stripe = new \Stripe\StripeClient('sk_test_51IGQxoAVYXU25aJ6Je71QDkHF2vkUMB0ZWhBBf9oGsKblbaAYxVDrSp0hy8cZtPnNFxqb8TsmP1nuBVZuZ2mCjre00Y7qm2uKn');
            $x = $stripe->customers->allSources($customer_id, ['object' => 'card', 'limit' => 3]);
			$last4 = $x->data[0]->last4;
            $card_id = $x->data[0]->id;
            $exp_month = $x->data[0]->exp_month;
            $exp_year = $x->data[0]->exp_year;
            $brand = $x->data[0]->brand;

?>
<label for="cardid<?=$i
?>" class="wc_card_class" > <input type="radio" name="search_type" id="cardid<?=$i
?>" value="<?=$user_uniqe_id; ?>"/>  &nbsp;<b><?=$brand ?> ending in <?=$last4 ?> (expires <?=$exp_month ?>/<?=$exp_year ?>)</b></label>
<script>
	$(document).ready(function(){
		$('input:radio[name="search_type"]').change(function() {
			if ($(this).val() == '<?=$user_uniqe_id; ?>'){
				$("#wc-<?php echo esc_attr($this->id); ?>-cc-form").hide();       
			}
			if ($(this).val() == 'B'){
				$("#wc-<?php echo esc_attr($this->id); ?>-cc-form").show(); 
			}
		});
	});
	     
</script>
<?php $i++;
        }
        if (empty($retrieve_data))
        {
            echo "<style> #besmart-card_label{display:none;}</style>";
        }
?>
<style>
	.wc_card_class {
	width: 96%;
	margin: 0 auto;
	box-shadow: 0 0 6px #666;
	padding: 20px;
	border-radius: 4px;
	margin-top: 15px;
	margin-bottom: 13px;
	background: #f2f2f2;
	}	fieldset#wc-besmart-cc-form {
	padding: 20px !important;
	margin: 10px !important;
	}
</style>
<label for="besmart-card-id" class="wc_card_class" id="besmart-card_label">	<input type="radio" name="search_type" id="besmart-card-id" checked value="B"/> &nbsp;<b>Use a new payment method</b></label><br/>     
<fieldset id="wc-<?php echo esc_attr($this->id); ?>-cc-form" class="wc-credit-card-form wc-payment-form wc_card_class" style="background:transparent;">
	<?php do_action('woocommerce_credit_card_form_start', $this->id); ?>   
	<div class="form-row form-row-wide">
		<label>Card Number <span class="required">*</span></label>
		<input id="card-number" name="card_name" type="text" placeholder="1234 1234 1234 1234" autocomplete="off" maxlength="19" value="">
	</div>
	<div class="form-row form-row-first">
		<label>Expiry Date <span class="required">*</span></label>
		<input id="card-exp" name="expiry_date" type="text" autocomplete="off" placeholder="MM / YY" maxlength='5' value="">
	</div>
	<div class="form-row form-row-last">
		<label>Security Code <span class="required">*</span></label>
		<input id="card-ccv" type="password" name="security_code" autocomplete="off" placeholder="CVC" maxlength="3" value="">
	</div>
	<?php if ($this->save_card == 1)
        { ?>
	<br><br><br><br>
	<div style="display:flex;">
		<input type="checkbox" name="save_card" value="save_card" id="save_card" style="margin-top: 5px !important;">	
		&nbsp;&nbsp;&nbsp;<label for="save_card"> Save this card for previous payments</label>
	</div>
	<?php
        } ?>
	<div class="clear"></div>
	<span class="powered"> Powered by BeSmart Pay </span>
	<img src="<?php echo plugin_dir_url( __FILE__ ); ?>icon/logo.png" height="50px" width="50px">
	<?php do_action('woocommerce_credit_card_form_end', $this->id); ?>
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
    public function payment_gateway_scripts()
    {
        if (!is_cart() && !is_checkout() && !isset($_GET['pay_for_order']))
        {
            return;
        }
        if ('no' === $this->enabled)
        {
            return;
        }
        if (empty($this->private_key) || empty($this->publishable_key))
        {
            return;
        }
        if (!$this->test_mode)
        {
            return;
        }
        if (!is_ssl())
        {
            return;
        }
        wp_enqueue_script('ybc_js', 'https://www.example.com/api/get-token.js');
        wp_register_script('woocommerce_pay_ybc', plugins_url('token-script.js', __FILE__) , array(
            'jquery',
            'ybc_js'
        ));
        wp_localize_script('woocommerce_pay_ybc', 'ybc_params', array(
            'publishKey' => $this->publish_key
        ));
        wp_enqueue_script('woocommerce_pay_ybc');
    }
    public function process_payment($order_id)
    {
        global $woocommerce, $current_user;
        $userid = $current_user->ID;
        /****** Get The Customer id Details  ******/
        /****** Get The Customer Order Details  ******/
        $customer_order = new WC_Order($order_id);
        $customerid = $customer_order->get_customer_id();
        /**** Get Card Expiry Month and Year ******/
        $str = $_POST['expiry_date'];
        $date_data = explode("/", $str);
        $month = $date_data[0];
        $year = $date_data[1];
        $save_card = $_POST['save_card'];
        $user_uniqe_id = $_POST['search_type'];
        /***** Get Statement Descriptor ******/
        $order = "Order";
        $statement = $order . '' . $order_id;
        $getsitetitle = get_bloginfo();
        $arr_data = array(
            $getsitetitle,
            $statement
        );
        $main_statement = implode("-", $arr_data);
        /*  Get the same price with the stripe  */
        $get_rate = floatval($customer_order->order_total)*100;
		$get_rate = round($get_rate);
        /***** Get application Fee Amount Percentage ******/
        $application_fee = (4.5 / 100) * $get_rate;
		$application_fee = round($application_fee);
        /****** Get the Customer id From the backend *******/
        $this->customer_id = $this->get_option('customer_id');
        $environment = ($this->environment == "yes") ? 'TRUE' : 'FALSE';
        $environment_url = ("FALSE" == $environment) ? 'https://buildmyownweb.com/webhook' : 'https://buildmyownweb.com/webhook';
        echo '<script src="https://js.stripe.com/v3/"></script>';
        require_once ('stripe/vendor/autoload.php');
        \Stripe\Stripe::setApiKey('sk_test_51IGQxoAVYXU25aJ6Je71QDkHF2vkUMB0ZWhBBf9oGsKblbaAYxVDrSp0hy8cZtPnNFxqb8TsmP1nuBVZuZ2mCjre00Y7qm2uKn');
        $stripe = new \Stripe\StripeClient('sk_test_51IGQxoAVYXU25aJ6Je71QDkHF2vkUMB0ZWhBBf9oGsKblbaAYxVDrSp0hy8cZtPnNFxqb8TsmP1nuBVZuZ2mCjre00Y7qm2uKn');
        /* payment start here */
        if ($_POST['search_type'] != "B")
        {
            /* Payment with save card */
            global $wpdb;
            global $current_user;
            $userid = $current_user->ID;
            $retrieve_data = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "besmart_token_meta where  user_uniqe_id = '$user_uniqe_id'");
            foreach ($retrieve_data as $retrieved_data)
            {
                $customer_id = $retrieved_data->customer_id;
                $x = $stripe->customers->allSources($customer_id, ['object' => 'card', 'limit' => 3]);
                $last4 = $x->data[0]->last4;
                $card_id = $x->data[0]->id;
                $exp_month = $x->data[0]->exp_month;
                $exp_year = $x->data[0]->exp_year;
                $brand = $x->data[0]->brand;
            }
            $method = $stripe->customers->retrieveSource($customer_id, $card_id, []);
            $card_id = $method->id;
            \Stripe\Stripe::setApiKey('sk_test_51IGQxoAVYXU25aJ6Je71QDkHF2vkUMB0ZWhBBf9oGsKblbaAYxVDrSp0hy8cZtPnNFxqb8TsmP1nuBVZuZ2mCjre00Y7qm2uKn');
            $maincreate = \Stripe\PaymentIntent::create(['customer' => $customer_id, 'setup_future_usage' => 'off_session', 'payment_method_types' => ['card'], 'payment_method' => $card_id, 'amount' => $get_rate, 'description' => $main_statement, 'currency' => 'usd', 'statement_descriptor' => $this->statement = $this->get_option('statement') , 'metadata' => ['order_id' => $order_id, ], 'application_fee_amount' => $application_fee, 'transfer_data' => ['destination' => $this->customer_id, ], ]);
            $stripes = new \Stripe\StripeClient('sk_test_51IGQxoAVYXU25aJ6Je71QDkHF2vkUMB0ZWhBBf9oGsKblbaAYxVDrSp0hy8cZtPnNFxqb8TsmP1nuBVZuZ2mCjre00Y7qm2uKn');
            $mainconfirm = $stripes->paymentIntents->confirm($maincreate->id, ['payment_method' => $card_id]);
            echo $mainconfirm;
            $response = wp_remote_post($environment_url, ['method' => 'POST', 'body' => http_build_query($mainconfirm) , 'timeout' => 90, 'sslverify' => false, ]);
            if (is_wp_error($response))
            {
                throw new Exception(__('There is issue for connectin payment gateway. Sorry for the inconvenience.', 'wc-gateway-nequi'));
            }
            if (empty($response['body']))
            {
                throw new Exception(__('Authorize.net\'s Response was not get any data.', 'wc-gateway-nequi'));
            }
            $response_body = wp_remote_retrieve_body($response);
            foreach (preg_split("/\r?\n/", $response_body) as $line)
            {
                $resp = explode("|", $line);
            }
            $r['StatusCode'] = $resp[0];
            $r['StatusDesc'] = $resp[1];
            // 1 or 4 means the transaction was a success
            if (($r['StatusCode'] == 0))
            {
                $customer_order->add_order_note(__('Authorize.net complete payment.', 'wc-gateway-nequi'));
                $customer_order->payment_complete();
                $woocommerce->cart->empty_cart();
                return ['result' => 'success', 'redirect' => $this->get_return_url($customer_order) , ];
            }
        }
        else
        {
            /* New payment*/
            $method = \Stripe\PaymentMethod::create(['type' => 'card','description' => $main_statement ,'card' => ['number' => $_POST['card_name'], 'exp_month' => $month, 'exp_year' => $year, 'cvc' => $_POST['security_code'], ], ]);
            $card_id = $method->id;
            $stripe = new \Stripe\StripeClient('sk_test_51IGQxoAVYXU25aJ6Je71QDkHF2vkUMB0ZWhBBf9oGsKblbaAYxVDrSp0hy8cZtPnNFxqb8TsmP1nuBVZuZ2mCjre00Y7qm2uKn');
            $token = $stripe->tokens->create(['card' => ['number' => $_POST['card_name'], 'exp_month' => $month, 'exp_year' => $year, 'cvc' => $_POST['security_code'], ], ]);
            $token_id = $token->id;
            $customer = \Stripe\Customer::create(['email' => $customer_order->billing_email, 'name' => $customer_order->billing_first_name, ['source' => $token_id], ]);
            $customer_id = $customer->id;
            $maincreate = \Stripe\PaymentIntent::create(['customer' => $customer->Id, 'setup_future_usage' => 'off_session', 'payment_method_types' => ['card'], 'payment_method' => $method->id, 'amount' => $get_rate, 'description' => $main_statement, 'currency' => 'usd', 'statement_descriptor' => $this->statement = $this->get_option('statement') , 'metadata' => ['order_id' => $order_id, ], 'application_fee_amount' => $application_fee, 'transfer_data' => ['destination' => $this->customer_id, ], ]);
            $stripes = new \Stripe\StripeClient('sk_test_51IGQxoAVYXU25aJ6Je71QDkHF2vkUMB0ZWhBBf9oGsKblbaAYxVDrSp0hy8cZtPnNFxqb8TsmP1nuBVZuZ2mCjre00Y7qm2uKn');
            $mainconfirm = $stripes->paymentIntents->confirm($maincreate->id, ['payment_method' => $card_id] );
            echo $mainconfirm;
            global $wpdb;
            if ($mainconfirm)
            {
                $randomString = uniqid();
                $transaction_id = $mainconfirm->id;
                $card_ids = $mainconfirm->data->id;
                if ($save_card == 'save_card')
                {
                    $table_name = $wpdb->prefix . 'besmart_token_meta';
                    $insert = $wpdb->insert("$table_name", array(
                        "customer_id" => $customer_id,
                        "transaction_id" => $transaction_id,
                        "user_id" => $userid,
                        "user_uniqe_id" => $randomString,
                    ));
                }
            }
            $response = wp_remote_post($environment_url, ['method' => 'POST', 'body' => http_build_query($mainconfirm) , 'timeout' => 90, 'sslverify' => false, ]);
            if (is_wp_error($response))
            {
                throw new Exception(__('There is issue for connectin payment gateway. Sorry for the inconvenience.', 'wc-gateway-nequi'));
            }
            if (empty($response['body']))
            {
                throw new Exception(__('Authorize.net\'s Response was not get any data.', 'wc-gateway-nequi'));
            }
            $response_body = wp_remote_retrieve_body($response);
            foreach (preg_split("/\r?\n/", $response_body) as $line)
            {
                $resp = explode("|", $line);
            }
            $r['StatusCode'] = $resp[0];
            $r['StatusDesc'] = $resp[1];
            // 1 or 4 means the transaction was a success
            if (($r['StatusCode'] == 0))
            {
                $customer_order->add_order_note(__('Authorize.net complete payment.', 'wc-gateway-nequi'));
                $customer_order->payment_complete();
                $woocommerce->cart->empty_cart();
                return ['result' => 'success', 'redirect' => $this->get_return_url($customer_order) , ];
            }
        }
    }
    public function webhook()
    {
        $order = wc_get_order($_GET['id']);
        $order->payment_complete();
        $order->reduce_order_stock();
        update_option('webhook_debug', $_GET);
    }
}
add_filter('woocommerce_payment_gateways', 'beSmart_Pay');
function beSmart_Pay($gateways)
{
    $gateways[] = 'WC_besmart_Gateway';
    return $gateways;
}