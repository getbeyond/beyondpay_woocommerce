<?php
/*
 * Plugin Name: Beyond Pay for WooCommerce
 * Description: Accept credit cards on your WooCommerce store with Beyond.
 * Author: Beyond
 * Author URI: https://getbeyond.com
 * Version: 1.1.1
 * Text Domain: beyond_pay-for-woocommerce
 *
 * Tested up to: 5.4.2
 * WC tested up to: 4.2.2
 *
 * Copyright (c) 2020 Above and Beyond Business Tools and Services for Entrepreneurs, Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

require_once( dirname(__FILE__) . '/BeyondPay.php' );

/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter('woocommerce_payment_gateways', 'beyond_pay_add_gateway_class');
add_action('woocommerce_update_order', 'beyond_pay_order_update');

function beyond_pay_add_gateway_class($gateways) {
    $gateways[] = 'WC_Beyond_Pay_Gateway';
    return $gateways;
}

function beyond_pay_no_wc() {
    echo '<div class="error"><p><strong> Beyond Pay requires WooCommerce to be installed and active. </strong></p></div>';
}

function beyond_pay_order_update($order_id){
    $order = wc_get_order($order_id);

    if(
	!$order->meta_exists('_beyond_pay_processed') && // Not fully processed
	$order->meta_exists('_beyond_pay_authorized') && // but authorized
	$order->has_status('completed') // and complete.
    ){
	
	$beyond_pay_gateway = wc_get_payment_gateway_by_order($order);
	
	$request = new BeyondPayRequest();
	$request->RequestType = "019";
	$request->TransactionID = time();

	$request->User = $beyond_pay_gateway->login;
	$request->Password = $beyond_pay_gateway->password;

	$request->requestMessage = new RequestMessage();
	$request->requestMessage->SoftwareVendor = 'WooCommerce BeyondPay Plugin';
	$request->requestMessage->TransactionType = 'capture';
	$request->requestMessage->Amount = $order->get_total() / 0.01;
	$request->requestMessage->MerchantCode = $beyond_pay_gateway->merchant_code;
	$request->requestMessage->MerchantAccountCode = $beyond_pay_gateway->merchant_account_code;
	$request->requestMessage->ReferenceNumber = $order->get_transaction_id();

	$conn = new BeyondPayConnection();
	$response = $conn->processRequest($beyond_pay_gateway->api_url, $request);
	if ($response->ResponseCode == '00000') {
	    $order->update_meta_data('_beyond_pay_processed', 1);
	    $order->save_meta_data();
	    $order->add_order_note('Payment for this order was captured.');
	} else {
	    wc_add_notice('Error capturing payment with Beyond Pay', 'error');
	    $order->add_order_note('Beyond Pay Capture Response: '.htmlentities(BeyondPayConnection::Serialize($response)));
	}
    }
}

add_action('plugins_loaded', 'beyond_pay_init_gateway_class');

function beyond_pay_init_gateway_class() {
    if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	add_action( 'admin_notices', 'beyond_pay_no_wc' );
	return;
    }

    class WC_Beyond_Pay_Gateway extends WC_Payment_Gateway {

	/**
	 * Class constructor, more about it in Step 3
	 */
	public function __construct() {

	    $this->id = 'beyondpay';
	    $this->icon = ''; // URL of the icon that will be displayed on checkout page near your gateway name
	    $this->has_fields = true;
	    $this->method_title = 'Beyond Pay Gateway';
	    $this->method_description = 'Description of Beyond Pay Gateway'; // will be displayed on the options page
	    // TODO: add subscriptions, refunds, saved payment methods,
	    $this->supports = array(
		'products'
	    );

	    // Method with all the options fields
	    $this->init_form_fields();

	    // Load the settings.
	    $this->init_settings();
	    $this->title = $this->get_option('title');
	    $this->description = $this->get_option('description');
	    $this->custom_error_message = $this->get_option('custom_error_message');
	    $this->enabled = $this->get_option('enabled');
	    $this->testmode = 'yes' === $this->get_option('testmode');
	    $this->api_url = $this->testmode ? 
		"https://api-test.getbeyondpay.com/paymentservice/requesthandler.svc" : 
		"https://api.getbeyondpay.com/PaymentService/RequestHandler.svc";
	    $this->private_key = $this->testmode ? 
		$this->get_option('test_private_key') :
		$this->get_option('private_key');
	    $this->public_key = $this->testmode ?
		$this->get_option('test_public_key') :
		$this->get_option('public_key');
	    $this->login = $this->testmode ?
		$this->get_option('test_login') :
		$this->get_option('login');
	    $this->password = $this->testmode ?
		$this->get_option('test_password') :
		$this->get_option('password');
	    $this->transaction_mode  = $this->get_option('transaction_mode') == "sale" ? "sale" : "sale-auth";
	    $this->merchant_code = $this->get_option('merchant_code');
	    $this->merchant_account_code = $this->get_option('merchant_account_code');

	    $additional_data = $this->get_option('additional_data','off');
	    $this->use_level_2_data = $additional_data !== 'off';
	    $this->use_level_3_data = $additional_data == 'level3';

	    add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
	    add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));
	}
	
	function admin_options() {
	    parent::admin_options();
?>
<script type="text/javascript">
    function onBeyondPayTestModeChanged(isTestMode) {
	var fields = ["public_key","private_key","login","password"];
	var trs = jQuery("tr");
	var testAction = isTestMode ? 'show' : 'hide';
	var liveAction = isTestMode ? 'hide' : 'show';
	fields.forEach(function(f){
	    trs.has("#woocommerce_beyondpay_test_"+f)[testAction]();
	    trs.has("#woocommerce_beyondpay_"+f)[liveAction]();
	});
    }
    var testModeCheckbox = document.getElementById('woocommerce_beyondpay_testmode');
    testModeCheckbox.addEventListener(
      'change',
      function(e){onBeyondPayTestModeChanged(e.target.checked);}
    );
    onBeyondPayTestModeChanged(testModeCheckbox.checked);
</script>
<?php
	}

	public function init_form_fields() {

	    $this->form_fields = array(
		'enabled' => array(
		    'title' => 'Enable/Disable',
		    'label' => 'Enable Beyond Pay Gateway',
		    'type' => 'checkbox',
		    'description' => '',
		    'default' => 'no'
		),
		'title' => array(
		    'title' => 'Title',
		    'type' => 'text',
		    'description' => 'This controls the title which the user sees during checkout.',
		    'default' => 'Credit/Debit Card',
		    'desc_tip' => true,
		),
		'description' => array(
		    'title' => 'Description',
		    'type' => 'textarea',
		    'description' => 'This controls the description which the user sees during checkout.',
		    'default' => 'Pay with your credit card via our super-cool payment gateway.',
		),
		'custom_error_message' => array(
		    'title' => 'Detailed Error Messages',
		    'type' => 'textarea',
		    'description' => 'This allows you to set custom error messages. %S '
		    . 'will be replaced with an error returned by the BeyondPay API.',
		    'default' => 'Something went wrong: %S. Please try again.',
		),
		'testmode' => array(
		    'title' => 'Test mode',
		    'label' => 'Enable Test Mode',
		    'type' => 'checkbox',
		    'description' => 'Place the payment gateway in test mode using test API keys.',
		    'default' => 'yes',
		    'desc_tip' => true,
		),
		'test_public_key' => array(
		    'title' => 'Test Public Key',
		    'type' => 'text'
		),
		'test_private_key' => array(
		    'title' => 'Test Private Key',
		    'type' => 'password',
		),
		'test_login' => array(
		    'title' => 'Test Login',
		    'type' => 'text'
		),
		'test_password' => array(
		    'title' => 'Test Password',
		    'type' => 'password',
		),
		'public_key' => array(
		    'title' => 'Live Public Key',
		    'type' => 'text'
		),
		'private_key' => array(
		    'title' => 'Live Private Key',
		    'type' => 'password'
		),
		'login' => array(
		    'title' => 'Live Login',
		    'type' => 'text'
		),
		'password' => array(
		    'title' => 'Live Password',
		    'type' => 'password',
		),
		'transaction_mode' => array(
		    'title' => 'Transaction Mode',
		    'type' => 'select',
		    'options' => [
			'sale' => 'Sale',
			'authorization' => 'Authorization'
		    ],
		    'description' => 'Sale mode will capture the payment instantly, '
		    . 'authorization will only authorize when order is placed and capture'
		    . ' once order status changes to completed.',
		),
		'additional_data' => array(
		    'title' => 'Level II/III Data',
		    'type' => 'select',
		    'options' => [
			'off' => 'Do not send additional data',
			'level2' => 'Send Level II Data',
			'level3' => 'Send Level II and Level III Data'
		    ],
		    'description' => 'Some business cards may be eligible for '
		    . 'lower interchange rates if you send additional data with'
		    . ' the transaction.',
		),
		'merchant_code' => array(
		    'title' => 'Merchant Code',
		    'type' => 'text'
		),
		'merchant_account_code' => array(
		    'title' => 'Merchant Account Code',
		    'type' => 'text'
		)
	    );
	}

	public function payment_fields() {
	    if ($this->description) {
		if ($this->testmode) {
		    $this->description .= '<br/> TEST MODE ENABLED. In test mode, you can use the card numbers listed on the <a href="https://developer.getbeyond.com/#test-cards-and-checks" target="_blank" rel="noopener noreferrer">Beyond Pay developer portal</a>.';
		}
		echo wpautop(wp_kses_post(trim($this->description)));
	    }
?>
<fieldset id="wc-beyond_pay-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">

    <?php do_action('woocommerce_credit_card_form_start', 'beyond_pay'); ?> 

    <div id="card"></div>
    <div id="errorMessage"></div>
    <div style="display: none" id="customStyles"> 
    body {
    margin: 8px 0;
    }
    #payment-form {
    border: 2px solid #003b5c; 
    padding: 5px 10px; 
    border-radius: 5px; 
    background: white;
    color: #333;
    }
    </div>
    <div class="clear"></div>

    <input type="hidden" value="" id="beyond_pay_token" name="beyond_pay_token" />
    <?php do_action('woocommerce_credit_card_form_end', $this->id); ?>

    <div class="clear"></div>
</fieldset>
<script type="text/javascript">
    if (typeof (tokenpay) === 'undefined') {
      tokenpay = TokenPay('<?php echo $this->public_key ?>');
    }
    attachBeyondPay(tokenpay);
</script>
<?php
	}

	/*
	 * Custom CSS and JS, in most cases required only when you decided to go with a custom credit card form
	 */
	public function payment_scripts() {

	    if (!is_cart() && !is_checkout() /* && ! isset( $_GET['pay_for_order'] ) */) {
		return;
	    }

	    if ('no' === $this->enabled) {
		return;
	    }

	    if (empty($this->private_key) || empty($this->public_key)) {
		return;
	    }

	    if (!$this->testmode && !is_ssl()) {
		return;
	    }

	    // and this is our custom JS in your plugin directory that works with TokenPay.js
	    wp_register_script('woocommerce_beyondpay', plugins_url('assets/js/beyondpay.js', __FILE__));
	    wp_register_script('woocommerce_tokenpay', plugins_url('assets/js/tokenpay.js', __FILE__));

	    wp_enqueue_script('woocommerce_beyondpay');
	    wp_enqueue_script('woocommerce_tokenpay');
	}

	public function validate_fields() {

	    if (empty($_POST['billing_first_name'])) {
		wc_add_notice('First name is required!', 'error');
		return false;
	    }
	    return true;
	}

	public function process_payment($order_id) {

	    global $woocommerce;

	    $order = wc_get_order($order_id);

	    $amountInCents = round($order->get_total()  * 100);

	    $request = new BeyondPayRequest();
	    $request->RequestType = "004";
	    $request->TransactionID = time();

	    $request->PrivateKey = $this->private_key;
	    $request->AuthenticationTokenId = $_POST['beyond_pay_token'];

	    $request->requestMessage = new RequestMessage();
	    $request->requestMessage->TransIndustryType = "EC";
	    $request->requestMessage->TransactionType = $this->transaction_mode;
	    $request->requestMessage->AcctType = "R";
	    $request->requestMessage->Amount = $amountInCents;
	    $request->requestMessage->HolderType = "O";
	    if($this->use_level_2_data){
		$request->requestMessage->PONum = $order_id;
		$localTaxIndicator = 'N';
		$tax = $order->get_total_tax();
		if(!empty($tax)) {
		    $request->requestMessage->TaxAmount = round($tax*100);
		    $localTaxIndicator = 'P';
		}
		$request->requestMessage->LocalTaxIndicator = $localTaxIndicator;
	    }
	    if($this->use_level_3_data){
		$request->requestMessage->ItemCount = $order->get_item_count();
		$items = $order->get_items();
		$itemsParsed = [];
		foreach ($items as $i) {
		    $product = $i->get_product();
		    $itemParsed = new Item();
		    $itemParsed->ItemCode = $product->get_id();
		    $itemParsed->ItemCommodityCode = "1234";
		    $itemParsed->ItemDescription = substr($i->get_name(),0,35);
		    $itemParsed->ItemQuantity = $i->get_quantity();
		    $itemParsed->ItemUnitMeasure = "EA";
		    $itemParsed->ItemUnitCostAmt = round(floatval($product->get_price())  * 100);
		    $itemParsed->ItemTotalAmount = round($order->get_line_total($i, true)  * 100);
		    if(!empty($i->get_total_tax())){
			$itemParsed->ItemTaxAmount = round($order->get_line_tax($i) * 100);
			$itemParsed->ItemTaxIndicator = 'P';
		    } else {
			$itemParsed->ItemTaxIndicator = 'N';
		    }
		    array_push($itemsParsed, $itemParsed);
		}
		$request->requestMessage->Item = $itemsParsed;
	    }
	    $conn = new BeyondPayConnection();
	    $response = $conn->processRequest($this->api_url, $request);

	    if ($response->ResponseCode == '00000') {

		if($this->transaction_mode === "sale-auth") {
		    $order->add_meta_data('_beyond_pay_authorized', 1);
		    $order->add_order_note('Payment was authorized and will be captured when order status is changed to complete.');
		} else {
		    $order->add_meta_data('_beyond_pay_processed', 1);
		}
		$order->payment_complete($response->responseMessage->GatewayTransID);
		$order->reduce_order_stock();
		$order->add_order_note('Thank you for your payment!', true);
		$woocommerce->cart->empty_cart();
		return array(
		    'result' => 'success',
		    'redirect' => $this->get_return_url($order)
		);
	    } else {
		$errorMsg = $this->custom_error_message ?
			$this->custom_error_message :
			'Something went wrong: %S. Please try again.';
		wc_add_notice(str_replace('%S', $response->ResponseDescription, $errorMsg), 'error');
		return;
	    }
	}

    }

}
