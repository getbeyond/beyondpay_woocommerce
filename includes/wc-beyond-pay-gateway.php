<?php

class WC_Beyond_Pay_Gateway extends WC_Payment_Gateway {

    /**
     * Class constructor, more about it in Step 3
     */
    public function __construct() {

	$this->id = 'beyondpay';
	$this->icon = ''; // URL of the icon that will be displayed on checkout page near your gateway name
	$this->has_fields = true;
	$this->method_title = 'Beyond Pay Gateway';
	$this->method_description = 'Securely accept credit card payments using Beyond Pay gateway and optimize your B2B interchange with support for Level III processing.'; // will be displayed on the options page
	$this->supports = array(
	    'products',
	    'refunds',
	    'subscriptions',
	    'subscription_cancellation',
	    'subscription_suspension',
	    'subscription_reactivation',
	    'subscription_amount_changes',
	    'subscription_date_changes',
	    'subscription_payment_method_change',
	    'subscription_payment_method_change_customer',
	    // 'subscription_payment_method_change_admin',
	    'multiple_subscriptions',
	    'tokenization',
	    'add_payment_method'
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
	$this->transaction_mode = $this->get_option('transaction_mode') == "sale" ? "sale" : "sale-auth";
	$this->merchant_code = $this->get_option('merchant_code');
	$this->merchant_account_code = $this->get_option('merchant_account_code');

	$additional_data = $this->get_option('additional_data', 'off');
	$this->use_level_2_data = $additional_data !== 'off';
	$this->use_level_3_data = $additional_data == 'level3';
	$this->connect_subscription_payments_with_users = true;

	add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
	add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));
    }

    function admin_options() {
	parent::admin_options();
	wp_enqueue_script('beyondpay_admin_options', plugins_url('assets/js/beyondpay-admin-options.js', dirname(__FILE__)));
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
		'default' => 'Pay with your credit or debit card.',
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
		'description' => 'Test API Keys may be obtained from '
		. '<a target="_blank" href="https://developer.getbeyond.com">'
		. 'developer.getbeyond.com'
		. '</a>',
		'default' => 'yes'
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
		'title' => 'Test Username',
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
		'title' => 'Live Username',
		'type' => 'text'
	    ),
	    'password' => array(
		'title' => 'Live Password',
		'type' => 'password',
	    ),
	    'merchant_code' => array(
		'title' => 'Merchant Code',
		'type' => 'text'
	    ),
	    'merchant_account_code' => array(
		'title' => 'Merchant Account Code',
		'type' => 'text'
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
		'description' => 'Select the level of transaction data to '
		. 'be automatically sent. Level II includes reference '
		. 'number and tax amount, while Level III includes '
		. 'line-item details. Set to Level III to ensure you always '
		. 'qualify for the best rates on eligible corporate '
		. 'purchasing cards.',
	    ),
	    'use_custom_styling' => array(
		'title' => 'Advanced Styling',
		'type' => 'checkbox',
		'label' => 'Enable to apply custom css rules to the '
		. 'payment fields.'
		. '</a>',
		'default' => 'no'
	    ),
	    'styling' => array(
		'title' => 'Payment Fields styling',
		'type' => 'textarea',
		'description' => 'You can set the CSS rules here which will '
		. 'apply to the payment fields.',
		'default' => file_get_contents(dirname(__DIR__) . '/assets/css/payment-styling.css')
	    ),
	);
    }

    public function payment_fields() {
	if ($this->description) {
	    if ($this->testmode) {
		$this->description .= '<br/> TEST MODE ENABLED. In test mode, you can use the card numbers listed on the <a href="https://developer.getbeyond.com/#test-cards-and-checks" target="_blank" rel="noopener noreferrer">Beyond Pay developer portal</a>.';
	    }
	    echo wpautop(wp_kses_post(trim($this->description)));
	}
	$css = $this->get_option('use_custom_styling') === 'yes' ?
	    $this->get_option('styling') :
	    file_get_contents(dirname(__DIR__) . '/assets/css/payment-styling.css');
	
	if ( is_checkout() ) {
	    $this->tokenization_script();
	    $this->saved_payment_methods();
	}

	$form_event = isset($_GET['change_payment_method']) || is_add_payment_method_page() ? 'submit' : 'checkout_place_order';
	?>
	<fieldset id="wc-beyond_pay-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">

	    <?php do_action('woocommerce_credit_card_form_start', 'beyond_pay'); ?> 

	    <div id="card"></div>
	    <div id="errorMessage"></div>
	    <?php if (!empty($css)) { ?>
	        <div style="display: none" id="customStyles"><?php echo $css ?></div>
	    <?php } ?>
	    <div class="clear"></div>

	    <input type="hidden" value="" id="beyond_pay_token" name="beyond_pay_token" />
	    <?php do_action('woocommerce_credit_card_form_end', $this->id); ?>

	    <div class="clear"></div>
	</fieldset>
	<script type="text/javascript">
	    attachBeyondPay('<?php echo $this->public_key ?>','<?php echo $form_event ?>');
	</script>
	<?php
	if ( is_checkout() && !isset($_GET['change_payment_method'])) {
	    if($this->cart_has_subscription()){
		echo "<p>Your payment method will be saved to process subscription payments.</p>";
	    } else {
		$this->save_payment_method_checkbox();
	    }
	}
    }

    public function payment_scripts() {

	if (!is_cart() && !is_checkout() && !is_add_payment_method_page() ) {
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
	wp_register_script('woocommerce_beyondpay', plugins_url('assets/js/beyondpay.js', dirname(__FILE__)));
	wp_register_script('woocommerce_tokenpay', plugins_url('assets/js/tokenpay.js', dirname(__FILE__)));

	wp_enqueue_script('woocommerce_beyondpay');
	wp_enqueue_script('woocommerce_tokenpay');
    }

    public function validate_fields() {

	if (
	    !is_add_payment_method_page() && 
	    empty($_REQUEST['pay_for_order']) && // Change payment method page
	    empty($_POST['billing_first_name'])
	) {
	    wc_add_notice('First name is required!', 'error');
	    return false;
	}
	if (
	    empty($_POST['beyond_pay_token']) && 
	    ( // Saved token
		empty($_POST['wc-beyondpay-payment-token']) ||
		$_POST['wc-beyondpay-payment-token'] === 'new'
	    )
	) {
	    wc_add_notice('Unable to generate payment token.', 'error');
	    return false;
	}
	return true;
    }
    
    public function process_refund($order_id, $amount = null, $reason = '') {
	$order = wc_get_order($order_id);
	$request = $this->build_payment_request(
	    'refund',
	    $amount,
	    $order->get_transaction_id(),
	    null
	);
	$conn = new BeyondPay\BeyondPayConnection();
	$response = $conn->processRequest($this->api_url, $request);

	if ($response->ResponseCode == '00000') {
	    return true;
	} else {
	    $order->add_order_note("Error refunding order: $response->ResponseDescription");
	    return false;
	}
    }

    public function process_payment($order_id) {

	global $woocommerce;

	$order = wc_get_order($order_id);
	$pay_with_token = !empty($_POST['wc-beyondpay-payment-token']) && is_numeric($_POST['wc-beyondpay-payment-token']) ?
	    intval($_POST['wc-beyondpay-payment-token']) :
	    false;
	$save_token = !empty($_POST['wc-beyondpay-new-payment-method']) && $_POST['wc-beyondpay-new-payment-method'] === 'true';
	$change_payment_for_order = !empty($_POST['woocommerce_change_payment']) ?
	    intval($_POST['woocommerce_change_payment']) :
	    false;
	
	if($change_payment_for_order) {
	    if($pay_with_token){
		$token = $this->get_token($pay_with_token);
	    } else {
		$request = $this->build_payment_request(
		    'save_payment_method',
		    null,
		    null,
		    sanitize_key($_POST['beyond_pay_token'])
		);

		$conn = new BeyondPay\BeyondPayConnection();
		$response = $conn->processRequest($this->api_url, $request);

		if ($response->ResponseCode == '00000') {
		    // Not passing order - don't want to add this token to order, but update_payment_token_ids().
		    $token = $this->save_token_from_response($response, get_current_user_id());
		} else {
		    wc_add_notice('Failed to change payment method: '.$response->ResponseDescription);
		}
	    }
	    $subscription = new WC_Subscription($order_id);
	    if(!empty($token)){
		$order->get_data_store()->update_payment_token_ids($order, array($token->get_id()));
		return array(
		    'result' => 'success',
		    'redirect' => $subscription->get_view_order_url()
		);
	    } else {
		return array(
		    'result' => 'failure',
		    'redirect' => $subscription->get_view_order_url()
		);
	    }
	}
	
	if($pay_with_token){
	    $token = $this->get_token($pay_with_token);
	    $request = $this->build_payment_request(
		'token_payment',
		$order->get_total(),
		$order->get_transaction_id(),
		$token
	    );
	} else {
	    $request = $this->build_payment_request(
		'payment',
		$order->get_total(),
		$order->get_transaction_id(),
		sanitize_key($_POST['beyond_pay_token'])
	    );
	}

	$address = $order->get_address('billing');
	if (!empty($address)) {
	    $name = trim($address['first_name'] . ' ' . $address['last_name']);
	    $request->requestMessage->AccountHolderName = $name;
	    $request->requestMessage->AccountStreet = trim($address['address_1']);
	    if (!empty($address['phone'])) {
		$address['phone'] = str_replace([' ', '-', '#', '+'], '', $address['phone']);
		while (strlen($address['phone']) < 10) {
		    $address['phone'] = '0' . $address['phone'];
		}
		if (strlen($address['phone']) < 12) {
		    $request->requestMessage->AccountPhone = trim($address['phone']);
		}
	    }
	    if (!empty($address['postcode'])) {
		$postcode = str_replace('-', '', $address['postcode']);
		if (is_numeric($postcode) && strlen($postcode) === 5) {
		    $request->requestMessage->AccountZip = $postcode;
		}
	    }
	}

	$customer_id = $order->get_user_id();
	if (!empty($customer_id)) {
	    $request->requestMessage->CustomerAccountCode = $customer_id;
	}
	$request->requestMessage->InvoiceNum = $order_id;
	$this->fill_level_2_3_data($request, $order);
	
	$conn = new BeyondPay\BeyondPayConnection();
	$response = $conn->processRequest($this->api_url, $request);

	if ($response->ResponseCode == '00000') {
	    
	    if (
		$this->has_subscription($order) ||
		$save_token
	    ) {
		$this->save_token_from_response(
		    $response,
		    $save_token || $this->connect_subscription_payments_with_users ? $order->get_user_id() : null,
		    $order
		);
	    } elseif ($pay_with_token) {
		if (!empty($response->responseMessage->CardType) && $token->get_card_type() === 'Card'){
		    $token->set_card_type($response->responseMessage->CardType);
		    $token->save();
		}
		$order->add_payment_token($token);
	    }

	    if ($request->TransactionType === "sale-auth") {
		$order->add_meta_data('_beyond_pay_authorized', 1);
		$order->add_order_note('Payment was authorized and will be captured when order status is changed to complete.');
	    } else {
		$order->add_meta_data('_beyond_pay_processed', 1);
	    }
	    $order->payment_complete($response->responseMessage->GatewayTransID);
	    wc_reduce_stock_levels($order);
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

    /**
     * Creates and saves a token based on response data. Reuses existing tokens if possible.
     * @param BeyondPay\BeyondPayResponse $response
     * @param int $user_id
     * @param WC_Order $order
     * @return WC_Payment_Token
     */
    private function save_token_from_response($response, $user_id = null, $order = null){
	$token = $this->find_existing_token($response->responseMessage->Token);
	
	if(!$token) {
	    $token = new WC_Payment_Token_CC();
	    $token->set_token($response->responseMessage->Token);
	    $token->set_last4(substr($response->responseMessage->Token, -4));
	    $token->set_expiry_month(substr($response->responseMessage->ExpirationDate, 0, 2));
	    $token->set_expiry_year('20'.substr($response->responseMessage->ExpirationDate, -2));
	    $card_type = !empty($response->responseMessage->CardType) ?
		$response->responseMessage->CardType :
		'Card';
	    $token->set_card_type($card_type);
	    $token->set_gateway_id($this->id);
	    if(!empty($user_id)){
		$token->set_user_id($user_id);
	    }
	    $token->save();
	} elseif (!empty($response->responseMessage->CardType) && $token->get_card_type()==='Card'){
	    $token->set_card_type($response->responseMessage->CardType);
	    $token->save();
	}
	
	if(!empty($order)){
	    $order->add_payment_token($token);
	}
	
	return $token;
    }
    
    /**
     * If exists returns users token object matching the raw token string.
     * @param string $token_str The raw token string
     * @return WC_Payment_Token | null
     */
    private function find_existing_token($token_str){
	$tokens = $this->get_tokens();
	foreach ($tokens as $t){
	    if($token_str === $t->get_token()){
		return $t;
	    }
	}
	return null;
    }
    
    /**
     * If exists returns users token object with given id.
     * @param string $token_id The token id
     * @return WC_Payment_Token | null
     */
    private function get_token($token_id){
	$tokens = $this->get_tokens();
	foreach ($tokens as $t){
	    if($token_id === $t->get_id()){
		return $t;
	    }
	}
	return null;
    }

    /**
     * Check if the order has subscriptions (WooCommerce Subscriptions)
     * @param type $order
     * @return boolean
     */
    private function has_subscription($order){
	return function_exists('wcs_order_contains_subscription') && 
	    wcs_order_contains_subscription( $order );
    }

    /**
     * Check if the cart has subscriptions (WooCommerce Subscriptions)
     * @param type $order
     * @return boolean
     */
    private function cart_has_subscription(){
	return function_exists('wcs_order_contains_subscription') && 
	(
	    WC_Subscriptions_Cart::cart_contains_subscription() ||
	    wcs_cart_contains_renewal()
	);
    }

    /**
     * Update request with Level 2/3 data if needed (checks settings).
     * @param BeyondPay\BeyondPayRequest $request The request to update
     * @param WC_Order $order Order to fetch data from
     */
    private function fill_level_2_3_data($request, $order){
	$request->requestMessage->InvoiceNum = $order->get_id();
	if ($this->use_level_2_data) {
	    $request->requestMessage->PONum = $order->get_id();
	    $localTaxIndicator = 'N';
	    $tax = $order->get_total_tax();
	    if (!empty($tax)) {
		$request->requestMessage->TaxAmount = round($tax * 100);
		$localTaxIndicator = 'P';
	    }
	    $request->requestMessage->LocalTaxIndicator = $localTaxIndicator;
	}
	if ($this->use_level_3_data) {
	    $request->requestMessage->ItemCount = $order->get_item_count();
	    $items = $order->get_items();
	    $itemsParsed = [];
	    foreach ($items as $i) {
		$product = $i->get_product();
		$itemParsed = new BeyondPay\Item();
		$itemParsed->ItemCode = $product->get_id();
		$itemParsed->ItemCommodityCode = "1234";
		$itemParsed->ItemDescription = substr($i->get_name(), 0, 35);
		$itemParsed->ItemQuantity = $i->get_quantity();
		$itemParsed->ItemUnitMeasure = "EA";
		$itemParsed->ItemUnitCostAmt = round(floatval($product->get_price()) * 100);
		$itemParsed->ItemTotalAmount = round($order->get_line_total($i, true) * 100);
		if (!empty($i->get_total_tax())) {
		    $itemParsed->ItemTaxAmount = round($order->get_line_tax($i) * 100);
		    $itemParsed->ItemTaxIndicator = 'P';
		} else {
		    $itemParsed->ItemTaxIndicator = 'N';
		}
		array_push($itemsParsed, $itemParsed);
	    }
	    $request->requestMessage->Item = $itemsParsed;
	}
    }


    /**
     * @param float $amount_to_charge
     * @param WC_Order $order
     */
    public function process_subscription_payment($amount_to_charge, $order) {
	$subscriptions = array_values(wcs_get_subscriptions_for_order( 
	    $order->get_id(), 
	    array( 'order_type' => array( 'parent', 'renewal' ) ) 
	));
	if(empty($subscriptions)){
	    $order->add_order_note('Tried to process subscription for order, but found none.');
	    return;
	}
	
	foreach ($subscriptions as $sub_id){
	    $subscription = new WC_Subscription($sub_id);
	    $parents = $subscription->get_related_orders('all','parent');
	    if(empty($parents)){
		$order->add_order_note(
			'No parent order for: ' . $subscription->get_id(). 
			' (parent id: '.$subscription->get_parent_id().')'
		    );
		$subscription->payment_failed();
		continue;
	    }
	    $parent = array_values($parents)[0];
	    $tokens = $parent->get_payment_tokens();

	    if(empty($tokens)){
		$order->add_order_note('No payment token found, can\'t process subscription payment.');
		$subscription->payment_failed();
		continue;
	    } else {
		try{
		    $token = new WC_Payment_Token_CC($tokens[0]);
		} catch(exception $e) {
		    $order->add_order_note('Missing valid payment method to process subscription.');
		    $subscription->payment_failed();
		    return;
		}
		$request = $this->build_payment_request(
		    'scheduled_subscription_payment',
		    $amount_to_charge,
		    $order->get_transaction_id(),
		    $token
		);
		$this->fill_level_2_3_data($request, $order);

		$conn = new BeyondPay\BeyondPayConnection();
		$response = $conn->processRequest($this->api_url, $request);

		if ($response->ResponseCode == '00000') {
		    if ($request->TransactionType === "sale-auth") {
			$order->add_meta_data('_beyond_pay_authorized', 1);
			$order->add_order_note('Payment was authorized and will be captured when order status is changed to complete.');
		    } else {
			$order->add_meta_data('_beyond_pay_processed', 1);
			$order->add_order_note('Subscription payment was processed.');
		    }
		    $order->set_transaction_id($response->responseMessage->GatewayTransID);
		    $order->save();
		    $order->save_meta_data();
		    $subscription->payment_complete();
		} else {
		    $order->add_order_note(
			'Subscription payment was not processed, due to: ' . 
			$response->ResponseDescription. 
			' (code '.$response->ResponseCode.')'
		    );
		    $subscription->payment_failed();
		}
	    }
	}
    }
    
    /**
     * Capture a previously authorised payment when using 'auth-sale' transaction mode.
     * @param WC_Order $order The order to capture
     */
    public function capture_authorised_payment($order){
	$request = $this->build_payment_request('capture', $order->get_total(), $order->get_transaction_id());

	$conn = new BeyondPay\BeyondPayConnection();
	$response = $conn->processRequest($this->api_url, $request);
	if ($response->ResponseCode == '00000') {
	    $order->update_meta_data('_beyond_pay_processed', 1);
	    $order->save_meta_data();
	    $order->add_order_note('Payment for this order was captured.');
	} else {
	    $order->add_order_note(
		'Error capturing payment with Beyond Pay, capture response: '.
		$response->ResponseDescription.
		' (code '.$response->ResponseCode.')'
	    );
	}
    }
    
    /**
     * Build a base for the payment gateway request.
     * @param string $payment_type payment|capture|save_payment_method|scheduled_subscription_payment
     * @param float $amount_to_charge Amount in dollars (will be converted to cents).
     * @param string $reference_number Transaction reference number.
     * @param string|WC_Payment_Token $token Single use authentication token provided by token pay or the stored payment token.
     * @return BeyondPay\BeyondPayRequest
     */
    public function build_payment_request($payment_type, $amount_to_charge = null, $reference_number = null, $token = null){
	$configs = array(
	    'capture' => array(
		'request_type' => '019',
		'auth_with_token' => false,
		'transaction_type' => 'capture'
	    ),
	    'payment' => array(
		'request_type' => '004',
		'auth_with_token' => true,
		'transaction_type' => $this->transaction_mode
	    ),
	    'save_payment_method' => array(
		'request_type' => '001',
		'auth_with_token' => true,
		'transaction_type' => null
	    ),
	    'scheduled_subscription_payment' => array(
		'request_type' => '004',
		'auth_with_token' => false,
		'transaction_type' => 'sale'
	    ),
	    'token_payment' => array(
		'request_type' => '004',
		'auth_with_token' => false,
		'transaction_type' => $this->transaction_mode
	    ),
	    'refund' => array(
		'request_type' => '012',
		'auth_with_token' => false,
		'transaction_type' => 'refund'
	    )
	);
	if(empty($configs[$payment_type])) {
	    throw new Exception("Payment type must be one of " . join(', ',array_keys($configs)));
	}
	$config = $configs[$payment_type];
	
	$request = new BeyondPay\BeyondPayRequest();
	$request->ClientIdentifier = 'SOAP';
	$request->RequestType = $config['request_type'];
	$request->TransactionID = time();
	
	$request->requestMessage = new BeyondPay\RequestMessage();
	
	if($token){
	    if(is_string($token)){
		$request->AuthenticationTokenId = $token;
	    } elseif ($token instanceof WC_Payment_Token) {
		$request->requestMessage->Token = $token->get_token();
		$request->requestMessage->ExpirationDate = $token->get_expiry_month() . substr($token->get_expiry_year(),-2);
	    }
	}
	
	if($config['auth_with_token']){
	    $request->PrivateKey = $this->private_key;
	}else{
	    $request->User = $this->login;
	    $request->Password = $this->password;
	}
	
	$request->requestMessage->AcctType = "R";
	if($amount_to_charge !== null){
	    $request->requestMessage->Amount = round($amount_to_charge * 100);
	}
	$request->requestMessage->HolderType = "O";
	$request->requestMessage->MerchantCode = $this->merchant_code;
	$request->requestMessage->MerchantAccountCode = $this->merchant_account_code;
	if($reference_number){
	    $request->requestMessage->ReferenceNumber = $reference_number;
	}
	$request->requestMessage->SoftwareVendor = 'WooCommerce Beyond Pay Plugin';
	if($config['transaction_type']){
	    $request->requestMessage->TransactionType = $config['transaction_type'];
	}
	
	$request->requestMessage->TransIndustryType = "EC";
	
	return $request;
    }
    
    /**
     * Add payment method via account screen.
     */
    public function add_payment_method() {
	if(!is_user_logged_in()){
	    wc_add_notice( 'User not logged in', 'error' );
	    return array(
		'result'   => 'failure',
		'redirect' => wc_get_endpoint_url( 'payment-methods' ),
	    );
	}
	$request = $this->build_payment_request(
	    'save_payment_method',
	    null,
	    null,
	    sanitize_key($_POST['beyond_pay_token'])
	);
	
	$conn = new BeyondPay\BeyondPayConnection();
	$response = $conn->processRequest($this->api_url, $request);

	if ($response->ResponseCode == '00000') {
	    $this->save_token_from_response($response, get_current_user_id());
	    return array(
		'result'   => 'success',
		'redirect' => wc_get_endpoint_url( 'payment-methods' )
	    );
	} else {
	    wc_add_notice( 'Failed to add payment method: '.$response->ResponseDescription.' ('.$response->ResponseCode.')', 'error' );
	    return array(
		'result'   => 'failure',
		'redirect' => wc_get_endpoint_url( 'add-payment-method' )
	    );
	}
    }
    /**
     * Override the default when on subscription's change payment method page, 
     * to check payment method linked to subscription rather than default.
     */
    public function get_saved_payment_method_option_html($token) {
	if(!isset($_GET['change_payment_method'])){
	    return parent::get_saved_payment_method_option_html($token);
	} else {
	    $order = wc_get_order(intval($_REQUEST['change_payment_method']));
	    $tokens = $order->get_payment_tokens();
	    if(empty($tokens)) {
		$checked = checked($token->is_default(), true, false);
	    } else {
		$checked = checked($token->get_id() === $tokens[0], true, false);
	    }
	    $html = sprintf(
	       '<li class="woocommerce-SavedPaymentMethods-token">
		       <input id="wc-%1$s-payment-token-%2$s" type="radio" name="wc-%1$s-payment-token" value="%2$s" style="width:auto;" class="woocommerce-SavedPaymentMethods-tokenInput" %4$s />
		       <label for="wc-%1$s-payment-token-%2$s">%3$s</label>
	       </li>', esc_attr($this->id), esc_attr($token->get_id()), esc_html($token->get_display_name()), $checked
	    );

	   return apply_filters('woocommerce_payment_gateway_get_saved_payment_method_option_html', $html, $token, $this);   
	}
    }

}
