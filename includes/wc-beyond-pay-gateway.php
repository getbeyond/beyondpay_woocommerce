<?php

use BeyondPay\BeyondPaySDKException;

class Credentials
{
    public $UserName;
    public $Password;
}

class PublicGetTransactionsByFilter
{
    public $MerchantAccountID;
    public $Skip = 0;
    public $Take = 3;
    public $InvoiceNumber;
    public $DateRangeFrom;
    public $DateRangeTo;

    public function __construct($params)
    {
        $this->MerchantAccountID = $params['merchant_account_id'];
        $this->InvoiceNumber = $params['invoice_number'];
        //$this->DateRangeFrom = (clone $params['date_created'])->sub(new DateInterval('P1D'))->format('Y-m-d');
        //$this->DateRangeTo = (clone $params['date_created'])->add(new DateInterval('P1D'))->format('Y-m-d');
    }
}


class WC_Beyond_Pay_Gateway extends WC_Payment_Gateway
{
    public static $cronOptionName = 'beyondpay_woo_automatic_transaction_status_updates';

    public function __construct()
    {
        $this->id = 'beyondpay';
        $this->icon
            = ''; // URL of the icon that will be displayed on checkout page near your gateway name
        $this->has_fields = true;
        $this->method_title = 'Beyond Pay Gateway';
        $this->method_description
            = 'Securely accept credit card payments using Beyond Pay gateway and optimize your B2B interchange with support for Level III processing.'; // will be displayed on the options page
        $this->supports = [
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
            'add_payment_method',
        ];

        // Method with all the options fields
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->custom_error_message = $this->get_option('custom_error_message');
        $this->enabled = $this->get_option('enabled');
        $this->testmode = 'yes' === $this->get_option('testmode');
        $this->debug_valid_requests = 'all' === $this->get_option('debug_mode');
        $this->debug_mode = $this->debug_valid_requests
            || 'fail_only' === $this->get_option('debug_mode');

        $this->api_url = $this->testmode
            ?
            "https://api-test.getbeyondpay.com/paymentservice/requesthandler.svc"
            :
            "https://api.getbeyondpay.com/PaymentService/RequestHandler.svc";
        $this->private_key = $this->testmode
            ?
            $this->get_option('test_private_key')
            :
            $this->get_option('private_key');
        $this->public_key = $this->testmode
            ?
            $this->get_option('test_public_key')
            :
            $this->get_option('public_key');
        $this->login = $this->testmode
            ?
            $this->get_option('test_login')
            :
            $this->get_option('login');
        $this->password = $this->testmode
            ?
            $this->get_option('test_password')
            :
            $this->get_option('password');
        $mode_mapping = [
            "sale" => "sale",
            "authorization" => "sale-auth",
            "tokenize_only" => "tokenize_only",
        ];
        	$transaction_mode  = $this->get_option( 'transaction_mode' );
		if ( array_key_exists( $transaction_mode, $mode_mapping ) ) {
			$this->transaction_mode = $mode_mapping[ $this->get_option( 'transaction_mode' ) ];
		} else {
			$this->transaction_mode = "sale";
		}

        $this->merchant_code = $this->get_option('merchant_code');
        $this->merchant_account_code
            = $this->get_option('merchant_account_code');

        $additional_data = $this->get_option('additional_data',
            'off');
        $this->use_level_2_data = $additional_data
            !== 'off';
        $this->use_level_3_data = $additional_data
            == 'level3';
        $this->connect_subscription_payments_with_users = true;

        add_action('woocommerce_update_options_payment_gateways_' . $this->id,
            [$this, 'process_admin_options']);
        add_action('wp_enqueue_scripts', [$this, 'payment_scripts']);

        // cron option is propagated to global WP settings as gateway is not always loaded
        $gatewayCronSetting = $this->get_option(self::$cronOptionName);
        $globalCronSetting = get_option(self::$cronOptionName);
        if (!$globalCronSetting) {
            add_option(self::$cronOptionName, $gatewayCronSetting);
        } else {
            if ($gatewayCronSetting != $globalCronSetting) {
                update_option(self::$cronOptionName, $gatewayCronSetting);
            }
        }
    }

    function admin_options()
    {
        parent::admin_options();
        wp_enqueue_script(
            'beyondpay_admin_options',
            plugins_url('assets/js/beyondpay-admin-options.js',
                dirname(__FILE__))
        );
    }

    public function init_form_fields()
    {
        $this->form_fields = [
            'enabled' => [
                'title' => 'Enable/Disable',
                'label' => 'Enable Beyond Pay Gateway',
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no',
            ],
            'title' => [
                'title' => 'Title',
                'type' => 'text',
                'description' => 'This controls the title which the user sees during checkout.',
                'default' => 'Credit/Debit Card',
                'desc_tip' => true,
            ],
            'description' => [
                'title' => 'Description',
                'type' => 'textarea',
                'description' => 'This controls the description which the user sees during checkout.',
                'default' => 'Pay with your credit or debit card.',
            ],
            'custom_error_message' => [
                'title' => 'Detailed Error Messages',
                'type' => 'textarea',
                'description' => 'This allows you to set custom error messages. %S '
                    . 'will be replaced with an error returned by the BeyondPay API.',
                'default' => 'Something went wrong: %S. Please try again.',
            ],
            'testmode' => [
                'title' => 'Test mode',
                'label' => 'Enable Test Mode',
                'type' => 'checkbox',
                'description' => 'Test API Keys may be obtained from '
                    . '<a target="_blank" href="https://developer.getbeyond.com">'
                    . 'developer.getbeyond.com'
                    . '</a>',
                'default' => 'yes',
            ],
            'test_public_key' => [
                'title' => 'Test Public Key',
                'type' => 'text',
            ],
            'test_private_key' => [
                'title' => 'Test Private Key',
                'type' => 'password',
            ],
            'test_login' => [
                'title' => 'Test Username',
                'type' => 'text',
            ],
            'test_password' => [
                'title' => 'Test Password',
                'type' => 'password',
            ],
            'public_key' => [
                'title' => 'Live Public Key',
                'type' => 'text',
            ],
            'private_key' => [
                'title' => 'Live Private Key',
                'type' => 'password',
            ],
            'login' => [
                'title' => 'Live Username',
                'type' => 'text',
            ],
            'password' => [
                'title' => 'Live Password',
                'type' => 'password',
            ],
            'merchant_code' => [
                'title' => 'Merchant Code',
                'type' => 'text',
            ],
            'merchant_account_code' => [
                'title' => 'Merchant Account Code',
                'type' => 'text',
            ],
            'transaction_mode' => [
                'title' => 'Transaction Mode',
                'type' => 'select',
                'options' => [
                    'sale' => 'Sale',
                    'authorization' => 'Authorization',
                    'tokenize_only' => 'Save Card ONLY',
                ],
                'description' =>
                    '<ul>'
                    . '<li>Sale mode will capture the payment instantly;</li> '
                    . '<li>Authorization will only authorize when order is placed and capture once order status changes to completed;</li>'
                    . '<li>Save Card ONLY allows you to securely store card numbers without any initial authorization and then later charge the card from the Order Details page. NOTE: You must select “Process Payment” on the Order Details page in order to get paid in Save Card Only mode.</li>'
                    . '</ul>',
            ],
            'additional_data' => [
                'title' => 'Level II/III Data',
                'type' => 'select',
                'options' => [
                    'off' => 'Do not send additional data',
                    'level2' => 'Send Level II Data',
                    'level3' => 'Send Level II and Level III Data',
                ],
                'description' => 'Select the level of transaction data to '
                    . 'be automatically sent. Level II includes reference '
                    . 'number and tax amount, while Level III includes '
                    . 'line-item details. Set to Level III to ensure you always '
                    . 'qualify for the best rates on eligible corporate '
                    . 'purchasing cards.',
            ],
            'use_custom_styling' => [
                'title' => 'Advanced Styling',
                'type' => 'checkbox',
                'label' => 'Enable to apply custom css rules to the '
                    . 'payment fields.'
                    . '</a>',
                'default' => 'no',
            ],
            'styling' => [
                'title' => 'Payment Fields styling',
                'type' => 'textarea',
                'description' => 'You can set the CSS rules here which will '
                    . 'apply to the payment fields.',
                'default' => file_get_contents(dirname(__DIR__)
                    . '/assets/css/payment-styling.css'),
            ],
            'debug_mode' => [
                'title' => 'Verbose Logging',
                'label' => 'Enable Verbose Logging',
                'type' => 'select',
                'options' => [
                    'no' => 'Off',
                    'fail_only' => 'Enabled for failed requests',
                    'all' => 'Enabled for all requests',
                ],
                'description' => 'Log details from payment gateway API requests and responses on the order details page. All card data is securely tokenized and never stored or logged. Should be enabled only for troubleshooting or development.',
                'default' => 'no',
            ],
//            'beyondpay_woo_show_manual_update_button' => [
//                'title' => 'Display manual Update Payment Status button',
//                'label' => '',
//                'type' => 'select',
//                'options' => [
//                    'off' => 'Off',
//                    'on' => 'On',
//                ],
//                'description' => 'Should be enabled only for troubleshooting or development. If you experience issues with payment status not being updated, this option will enable a button in order details that will let you manually trigger a payment status update.',
//                'default' => 'off',
//            ],
            'beyondpay_woo_automatic_transaction_status_updates' => [
                'title' => 'Automatic transaction status updates',
                'label' => '[Experimental] Enable it if you\'re experiencing issues with order statuses not being updated after payment',
                'type' => 'select',
                'options' => [
                    'off' => 'Off',
                    'on' => 'On',
                ],
                'description' => 'Should be enabled only for troubleshooting or development.',
                'default' => 'off',
            ],
        ];
    }

    public function payment_fields()
    {
        if ($this->description) {
            if ($this->testmode) {
                $this->description .= '<br/> TEST MODE ENABLED. In test mode, you can use the card numbers listed on the <a href="https://developer.getbeyond.com/#test-cards-and-checks" target="_blank" rel="noopener noreferrer">Beyond Pay developer portal</a>.';
            }
            echo wpautop(wp_kses_post(trim($this->description)));
        }
        $css = $this->get_option('use_custom_styling') === 'yes'
            ?
            $this->get_option('styling')
            :
            file_get_contents(dirname(__DIR__)
                . '/assets/css/payment-styling.css');

        if (is_checkout()) {
            $this->tokenization_script();
            $this->saved_payment_methods();
        }

       	if (isset($_GET['pay_for_order']) || isset($_GET['change_payment_method']) || is_add_payment_method_page()) {
			$form_event = 'submit';
		} else {
			$form_event = 'checkout_place_order';
        }
        ?>
      <fieldset id="wc-beyond_pay-cc-form"
                class="wc-credit-card-form wc-payment-form"
                style="background:transparent;"
      >

          <?php
          do_action('woocommerce_credit_card_form_start', 'beyond_pay'); ?>

        <div id="card"></div>
        <div id="errorMessage"></div>
          <?php
          if (!empty($css)) { ?>
            <div style="display: none" id="customStyles"><?php
                echo $css ?></div>
              <?php
          } ?>
        <div class="clear"></div>

        <input type="hidden" value="" id="beyond_pay_token"
               name="beyond_pay_token"
        />
          <?php
          do_action('woocommerce_credit_card_form_end', $this->id); ?>

        <div class="clear"></div>
      </fieldset>
      <script type="text/javascript">
        attachBeyondPay('<?php echo $this->public_key ?>', '<?php echo $form_event ?>', <?php echo $this->testmode
            ? 'true' : 'false' ?>);
      </script>
        <?php
        if (is_checkout() && !isset($_GET['change_payment_method'])) {
            if ($this->cart_has_subscription()) {
                echo "<p>Your payment method will be saved to process subscription payments.</p>";
            } elseif ($this->transaction_mode == 'tokenize_only') {
                echo "<p>Your payment method will be securely saved and charged when your order is completed.</p>";
            } else {
                $this->save_payment_method_checkbox();
            }
        }
    }

    public function payment_scripts()
    {
        if (!is_cart() && !is_checkout() && !is_add_payment_method_page()) {
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
        wp_register_script('woocommerce_beyondpay',
            plugins_url('assets/js/beyondpay.js', dirname(__FILE__)));
        wp_register_script('woocommerce_tokenpay',
            plugins_url('assets/js/tokenpay.js', dirname(__FILE__)));

        wp_enqueue_script('woocommerce_beyondpay');
        wp_enqueue_script('woocommerce_tokenpay');
    }

    public function validate_fields()
    {
        if (
            !is_add_payment_method_page() && empty($_REQUEST['pay_for_order'])
            && // Change payment method page
            empty($_POST['billing_first_name'])
        ) {
            wc_add_notice('First name is required!', 'error');

            return false;
        }
        if (
            empty($_POST['beyond_pay_token'])
            && ( // Saved token
                empty($_POST['wc-beyondpay-payment-token'])
                || $_POST['wc-beyondpay-payment-token'] === 'new'
            )
        ) {
            wc_add_notice('Unable to generate payment token.', 'error');

            return false;
        }

        return true;
    }

    /**
     * @param WC_Order $order
     *
     * @return boolean
     */
    public function can_refund_order($order)
    {
        return $order && !empty($order->get_transaction_id())
            && empty($order->get_meta('_beyond_pay_tokenized'));
    }

    public function process_refund($order_id, $amount = null, $reason = '')
    {
        $order = wc_get_order($order_id);
        if ($order->get_meta('_beyond_pay_tokenized')) {
            $order->add_order_note(
                "Order refund failed: this order was processed with 'Save Card ONLY' transaction mode and the payment was not processed, no refund should be needed."
            );

            return false;
        }
        $request = $this->build_payment_request(
            'refund',
            $amount,
            $order->get_transaction_id(),
            null
        );
        $response = $this->send_gateway_request($request, $order);

        if ($response->ResponseCode == '00000') {
            if ($reason) {
                $order->add_order_note("Order refunded, reason: $reason");
            }

            return true;
        } else {
            $order->add_order_note("Error refunding order: $response->ResponseDescription");

            return false;
        }
    }

    public function process_payment($order_id)
    {
        global $woocommerce;

        $order = wc_get_order($order_id);
        $pay_with_token = !empty($_POST['wc-beyondpay-payment-token'])
        && is_numeric(
            $_POST['wc-beyondpay-payment-token']
        )
            ?
            intval($_POST['wc-beyondpay-payment-token'])
            :
            false;
        $is_tokenize_only = $this->transaction_mode === "tokenize_only";
        $save_token = $is_tokenize_only
            || (!empty($_POST['wc-beyondpay-new-payment-method'])
                && $_POST['wc-beyondpay-new-payment-method'] === 'true');
        $change_payment_for_order = !empty($_POST['woocommerce_change_payment'])
            ?
            intval($_POST['woocommerce_change_payment'])
            :
            false;

        if ($change_payment_for_order) {
            if ($pay_with_token) {
                $token = $this->get_token($pay_with_token);
            } else {
                $request = $this->build_payment_request(
                    'save_payment_method',
                    null,
                    null,
                    sanitize_key($_POST['beyond_pay_token'])
                );

                $response = $this->send_gateway_request($request);
                if ($response->ResponseCode == '00000') {
                    // Not passing order - don't want to add this token to order, but update_payment_token_ids().
                    $token = $this->save_token_from_response($response,
                        get_current_user_id());
                } else {
                    wc_add_notice('Failed to change payment method: '
                        . $response->ResponseDescription);
                }
            }
            $subscription = new WC_Subscription($order_id);
            if (!empty($token)) {
                $order->get_data_store()
                    ->update_payment_token_ids($order, [$token->get_id()]);

                return [
                    'result' => 'success',
                    'redirect' => $subscription->get_view_order_url(),
                ];
            } else {
                return [
                    'result' => 'failure',
                    'redirect' => $subscription->get_view_order_url(),
                ];
            }
        }

        if ($pay_with_token) {
            /** @var WC_Payment_Token - stored token */
            $token = $this->get_token($pay_with_token);
            if ($is_tokenize_only) {
                $order->add_payment_token($token);

                return $this->tokenize_only_order($order);
            }
            $request = $this->build_payment_request(
                'token_payment',
                $order->get_total(),
                $order->get_transaction_id(),
                $token
            );
        } elseif ($is_tokenize_only) {
            $request = $this->build_payment_request(
                'save_payment_method',
                null,
                null,
                sanitize_key($_POST['beyond_pay_token'])
            );
        } else {
            $request = $this->build_payment_request(
                'payment',
                $order->get_total(),
                $order->get_transaction_id(),
                sanitize_key($_POST['beyond_pay_token'])
            );
        }

        $this->fill_address_data($request, $order);

        $customer_id = $order->get_user_id();
        if (!empty($customer_id)) {
            $request->requestMessage->CustomerAccountCode = $customer_id;
        }
        $request->requestMessage->InvoiceNum = $order_id;
        if (!$is_tokenize_only) {
            $this->fill_level_2_3_data($request, $order);
        }
        $response = $this->send_gateway_request($request, $order);

        if ($response->ResponseCode == '00000') {
            $this->addCreditCardMetaToOrder($response, $order);
            if (
                $this->has_subscription($order) || $save_token
            ) {
                $token = $this->save_token_from_response(
                    $response,
                    $save_token
                    || $this->connect_subscription_payments_with_users
                        ? $order->get_user_id() : null,
                    $order
                );
                if ($is_tokenize_only) {
                    return $this->tokenize_only_order($order, $token);
                }
            } elseif ($pay_with_token) {
                $this->update_card_type_on_token($response, $token);
                $order->add_payment_token($token);
            }

            if ($request->requestMessage->TransactionType === "sale-auth") {
                $order->add_meta_data('_beyond_pay_authorized', 1);
                $order->add_order_note(
                    'Payment was authorized and will be captured when order status is changed to complete.'
                );
            } else {
                $order->add_meta_data('_beyond_pay_processed', 1);
            }
            $order->payment_complete($response->responseMessage->GatewayTransID);
            wc_reduce_stock_levels($order);
            $order->add_order_note('Thank you for your payment!', true);
            $woocommerce->cart->empty_cart();

            return [
                'result' => 'success',
                'redirect' => $this->get_return_url($order),
            ];
        } else {
            $errorMsg = $this->custom_error_message
                ?
                $this->custom_error_message
                :
                'Something went wrong: %S. Please try again.';
            wc_add_notice(str_replace('%S', $response->ResponseDescription,
                $errorMsg), 'error');

            return;
        }
    }

    /**
     * Adds a debug message to the order if debug mode is turned on.
     *
     * @param BeyondPay\BeyondPayResponse $response
     * @param WC_Order $order
     * @param BeyondPay\BeyondPayRequest $request
     * @param BeyondPay\BeyondPayResponse $response
     */
    private function verbose_logging($order, $request, $response)
    {
        if ($this->debug_mode) {
            $is_successful = !empty($response->ResponseCode) && $response->ResponseCode == '00000';
            if ($is_successful && !$this->debug_valid_requests) {
                return;
            }
            if (!empty($request->PrivateKey)) {
                $request->PrivateKey = '--hidden--';
            }
            if (!empty($request->Password)) {
                $request->Password = '--hidden--';
            }
            $order_note_header = $is_successful
                ?
                'Processed payment request'
                :
                'Failed to process payment for request';

            $serializedResponse = BeyondPay\BeyondPayConnection::Serialize($response);
            if (empty($serializedResponse)) {
                $serializedResponse = json_encode($response);
            }

            $order->add_order_note(
                $order_note_header . ': <br/>' .
                '<div style="background-color: white; overflow: auto; max-height: 100px;">'
                .
                htmlentities(BeyondPay\BeyondPayConnection::Serialize($request))
                .
                '</div><br/>' .
                'Response was:<br/>' .
                '<div style="background-color: white; overflow: auto; max-height: 100px;">'
                .
                htmlentities($serializedResponse)
                .
                '</div><br/>' .
                '<br/>You are seeing this notice because you have Verbose Logging enabled in Beyond Pay Gateway settings.'
            );
        }
    }

    /**
     * Creates and saves a token based on response data. Reuses existing tokens if possible.
     *
     * @param BeyondPay\BeyondPayResponse $response
     * @param int $user_id
     * @param WC_Order $order
     *
     * @return WC_Payment_Token
     */
    private function save_token_from_response(
        $response,
        $user_id = null,
        $order = null
    )
    {
        $token = $this->find_existing_token($response->responseMessage->Token);

        if (!$token) {
            $token = new WC_Payment_Token_CC();
            $token->set_token($response->responseMessage->Token);
            $token->set_last4(substr($response->responseMessage->Token, -4));
            $token->set_expiry_month(substr($response->responseMessage->ExpirationDate,
                0, 2));
            $token->set_expiry_year('20'
                . substr($response->responseMessage->ExpirationDate, -2));
            $card_type = !empty($response->responseMessage->CardType)
                ?
                $response->responseMessage->CardType
                :
                'Card';
            $token->set_card_type($card_type);
            $token->set_gateway_id($this->id);
            if (!empty($user_id)) {
                $token->set_user_id($user_id);
            }
            $token->save();
        } else {
            $this->update_card_type_on_token($response, $token);
        }

        if (!empty($order)) {
            $order->add_payment_token($token);
        }

        return $token;
    }

    /**
     * Sets masked PAN, card type and expiry date on order based on a successful respose.
     *
     * @param BeyondPay\BeyondPayResponse $response
     * @param WC_Order $order
     */
    private function addCreditCardMetaToOrder($response, $order)
    {
        $order->add_meta_data('_beyond_pay_pan',
            substr($response->responseMessage->Token, -4));
        $order->add_meta_data('_beyond_expiration_date',
            $response->responseMessage->ExpirationDate);
        if (!empty($response->responseMessage->CardType)) {
            $order->add_meta_data('_beyond_pay_card_type',
                $response->responseMessage->CardType);
        }
    }

    /**
     * Updates the card type if token has it missing and more accurate value is provided in response.
     *
     * @param BeyondPay\BeyondPayResponse $response
     * @param WC_Payment_Token $token
     */
    private function update_card_type_on_token($response, $token)
    {
        if (!empty($response->responseMessage->CardType)
            && ($token->get_card_type() === 'Card'
                || empty($token->get_card_type()))
        ) {
            $token->set_card_type($response->responseMessage->CardType);
            $token->save();
        }
    }

    /**
     * If exists returns users token object matching the raw token string.
     *
     * @param string $token_str The raw token string
     *
     * @return WC_Payment_Token | null
     */
    private function find_existing_token($token_str)
    {
        $tokens = $this->get_tokens();
        foreach ($tokens as $t) {
            if ($token_str === $t->get_token()) {
                return $t;
            }
        }

        return null;
    }

    /**
     * If exists returns users token object with given id.
     *
     * @param string $token_id The token id
     *
     * @return WC_Payment_Token | null
     */
    public function get_token($token_id)
    {
        $tokens = $this->get_tokens();
        foreach ($tokens as $t) {
            if ($token_id === $t->get_id()) {
                return $t;
            }
        }

        return null;
    }

    /**
     * Check if the order has subscriptions (WooCommerce Subscriptions)
     *
     * @param WC_Order $order
     *
     * @return boolean
     */
    private function has_subscription($order)
    {
        return function_exists('wcs_order_contains_subscription')
            && wcs_order_contains_subscription($order);
    }

    /**
     * Check if the cart has subscriptions (WooCommerce Subscriptions)
     *
     * @return boolean
     */
    private function cart_has_subscription()
    {
        return function_exists('wcs_order_contains_subscription')
            && (
                WC_Subscriptions_Cart::cart_contains_subscription()
                || wcs_cart_contains_renewal()
            );
    }

    /**
     * Update request with Level 2/3 data if needed (checks settings).
     *
     * @param BeyondPay\BeyondPayRequest $request The request to update
     * @param WC_Order $order Order to fetch data from
     */
    private function fill_level_2_3_data($request, $order)
    {
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
                $itemParsed->ItemUnitCostAmt
                    = round(floatval($product->get_price())
                    * 100);
                $itemParsed->ItemTotalAmount
                    = round($order->get_line_total($i,
                        true) * 100);
                if (!empty($i->get_total_tax())) {
                    $itemParsed->ItemTaxAmount = round($order->get_line_tax($i)
                        * 100);
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
     * Update request with address data if available on order.
     *
     * @param BeyondPay\BeyondPayRequest $request The request to update
     * @param WC_Order $order Order to fetch data from
     */
    private function fill_address_data($request, $order)
    {
        $address = $order->get_address('billing');
        if (!empty($address)) {
            $name = trim($address['first_name']
                . ' ' . $address['last_name']);
            $request->requestMessage->AccountHolderName = $name;
            $request->requestMessage->AccountStreet
                = trim($address['address_1']);
            if (!empty($address['phone'])) {
                $address['phone'] = str_replace([' ', '-', '#', '+'], '',
                    $address['phone']);
                while (strlen($address['phone']) < 10) {
                    $address['phone'] = '0' . $address['phone'];
                }
                if (strlen($address['phone']) < 12) {
                    $request->requestMessage->AccountPhone
                        = trim($address['phone']);
                }
            }
            if (!empty($address['postcode'])) {
                $postcode = str_replace('-', '', $address['postcode']);
                if (is_numeric($postcode) && strlen($postcode) === 5) {
                    $request->requestMessage->AccountZip = $postcode;
                }
            }
        }
    }


    /**
     * @param float $amount_to_charge
     * @param WC_Order $order
     */
    public function process_subscription_payment($amount_to_charge, $order)
    {
        $subscriptions = array_values(
            wcs_get_subscriptions_for_order(
                $order->get_id(),
                ['order_type' => ['parent', 'renewal']]
            )
        );
        if (empty($subscriptions)) {
            $order->add_order_note('Tried to process subscription for order, but found none.');

            return;
        }

        foreach ($subscriptions as $sub_id) {
            $subscription = new WC_Subscription($sub_id);
            $parents = $subscription->get_related_orders('all', 'parent');
            if (empty($parents)) {
                $order->add_order_note(
                    'No parent order for: ' . $subscription->get_id() .
                    ' (parent id: ' . $subscription->get_parent_id() . ')'
                );
                $subscription->payment_failed();
                continue;
            }
            $parent = array_values($parents)[0];
            $tokens = $parent->get_payment_tokens();

            if (empty($tokens)) {
                $order->add_order_note('No payment token found, can\'t process subscription payment.');
                $subscription->payment_failed();
                continue;
            } else {
                try {
                    $token = new WC_Payment_Token_CC($tokens[0]);
                } catch (exception $e) {
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
                $this->fill_address_data($request, $order);
                $this->fill_level_2_3_data($request, $order);
                $response = $this->send_gateway_request($request, $order);

                if ($response->ResponseCode == '00000') {
                    $this->addCreditCardMetaToOrder($response, $order);
                    if ($request->requestMessage->TransactionType
                        === "sale-auth"
                    ) {
                        $order->add_meta_data('_beyond_pay_authorized', 1);
                        $order->add_order_note(
                            'Payment was authorized and will be captured when order status is changed to complete.'
                        );
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
                        $response->ResponseDescription .
                        ' (code ' . $response->ResponseCode . ')'
                    );
                    $subscription->payment_failed();
                }
            }
        }
    }

    /**
     * Capture a previously authorised payment when using 'auth-sale' transaction mode.
     *
     * @param WC_Order $order The order to capture
     */
    public function capture_authorised_payment($order)
    {
        $request = $this->build_payment_request('capture', $order->get_total(),
            $order->get_transaction_id());
        $response = $this->send_gateway_request($request, $order);
        if ($response->ResponseCode == '00000') {
            $order->update_meta_data('_beyond_pay_processed', 1);
            $order->save_meta_data();
            $order->add_order_note('Payment for this order was captured.');
        } else {
            $order->add_order_note(
                'Error capturing payment with Beyond Pay, capture response: ' .
                $response->ResponseDescription .
                ' (code ' . $response->ResponseCode . ')'
            );
        }
    }

    /**
     * Build a base for the payment gateway request.
     *
     * @param string $payment_type payment|capture|save_payment_method|scheduled_subscription_payment
     * @param float $amount_to_charge Amount in dollars (will be converted to cents).
     * @param string $reference_number Transaction reference number.
     * @param string|WC_Payment_Token $token Single use authentication token provided by token pay or the stored payment token.
     *
     * @return BeyondPay\BeyondPayRequest
     */
    public function build_payment_request(
        $payment_type,
        $amount_to_charge = null,
        $reference_number = null,
        $token = null
    )
    {
        $transaction_type = $this->transaction_mode === 'tokenize_only' ? 'sale'
            : $this->transaction_mode;
        $configs = [
            'capture' => [
                'request_type' => '019',
                'auth_with_token' => false,
                'transaction_type' => 'capture',
            ],
            'payment' => [
                'request_type' => '004',
                'auth_with_token' => true,
                'transaction_type' => $transaction_type,
            ],
            'save_payment_method' => [
                'request_type' => '001',
                'auth_with_token' => true,
                'transaction_type' => null,
            ],
            'scheduled_subscription_payment' => [
                'request_type' => '004',
                'auth_with_token' => false,
                'transaction_type' => 'sale',
            ],
            'token_payment' => [
                'request_type' => '004',
                'auth_with_token' => false,
                'transaction_type' => $transaction_type,
            ],
            'refund' => [
                'request_type' => '012',
                'auth_with_token' => false,
                'transaction_type' => 'refund',
            ],
        ];
        if (empty($configs[$payment_type])) {
            throw new Exception("Payment type must be one of " . join(', ',
                    array_keys($configs)));
        }
        $config = $configs[$payment_type];

        $request = new BeyondPay\BeyondPayRequest();
        $request->ClientIdentifier = 'SOAP';
        $request->RequestType = $config['request_type'];
        $request->TransactionID = time();

        $request->requestMessage = new BeyondPay\RequestMessage();

        if ($token) {
            if (is_string($token)) {
                $request->AuthenticationTokenId = $token;
            } elseif ($token instanceof WC_Payment_Token) {
                $request->requestMessage->Token = $token->get_token();
                $request->requestMessage->ExpirationDate
                    = $token->get_expiry_month()
                    . substr(
                        $token->get_expiry_year(),
                        -2
                    );
            }
        }

        if ($config['auth_with_token']) {
            $request->PrivateKey = $this->private_key;
        } else {
            $request->User = $this->login;
            $request->Password = $this->password;
        }

        $request->requestMessage->AcctType = "R";
        if ($amount_to_charge !== null) {
            $request->requestMessage->Amount = round($amount_to_charge * 100);
        }
        $request->requestMessage->HolderType = "O";
        $request->requestMessage->MerchantCode = $this->merchant_code;
        $request->requestMessage->MerchantAccountCode
            = $this->merchant_account_code;
        if ($reference_number) {
            $request->requestMessage->ReferenceNumber = $reference_number;
        }
        $request->requestMessage->SoftwareVendor
            = 'WooCommerce Beyond Pay Plugin';
        if ($config['transaction_type']) {
            $request->requestMessage->TransactionType
                = $config['transaction_type'];
        }

        $request->requestMessage->TransIndustryType = "EC";

        return $request;
    }

    /**
     *
     * @param BeyondPay\BeyondPayRequest $request
     * @param WC_Order $order Optional, if not provided Debug Logging will not be performed for this request.
     *
     * @return BeyondPay\BeyondPayResponse
     */
    public function send_gateway_request($request, $order = null)
    {
        $conn = new BeyondPay\BeyondPayConnection();
        $response = $conn->processRequest($this->api_url, $request);
        if ($order) {
            $this->verbose_logging($order, $request, $response);
        }

        return $response;
    }

    /**
     * Add payment method via account screen.
     */
    public function add_payment_method()
    {
        if (!is_user_logged_in()) {
            wc_add_notice('User not logged in', 'error');

            return [
                'result' => 'failure',
                'redirect' => wc_get_endpoint_url('payment-methods'),
            ];
        }
        $request = $this->build_payment_request(
            'save_payment_method',
            null,
            null,
            sanitize_key($_POST['beyond_pay_token'])
        );

        $response = $this->send_gateway_request($request);

        if ($response->ResponseCode == '00000') {
            $this->save_token_from_response($response, get_current_user_id());

            return [
                'result' => 'success',
                'redirect' => wc_get_endpoint_url('payment-methods'),
            ];
        } else {
            wc_add_notice(
                'Failed to add payment method: ' . $response->ResponseDescription
                . ' (' . $response->ResponseCode . ')',
                'error'
            );

            return [
                'result' => 'failure',
                'redirect' => wc_get_endpoint_url('add-payment-method'),
            ];
        }
    }

    /**
     * Override the default when on subscription's change payment method page,
     * to check payment method linked to subscription rather than default.
     */
    public function get_saved_payment_method_option_html($token)
    {
        if (!isset($_GET['change_payment_method'])) {
            return parent::get_saved_payment_method_option_html($token);
        } else {
            $order = wc_get_order(intval($_REQUEST['change_payment_method']));
            $tokens = $order->get_payment_tokens();
            if (empty($tokens)) {
                $checked = checked($token->is_default(), true, false);
            } else {
                $checked = checked($token->get_id() === $tokens[0], true,
                    false);
            }
            $html = sprintf(
                '<li class="woocommerce-SavedPaymentMethods-token">
		       <input id="wc-%1$s-payment-token-%2$s" type="radio" name="wc-%1$s-payment-token" value="%2$s" style="width:auto;" class="woocommerce-SavedPaymentMethods-tokenInput" %4$s />
		       <label for="wc-%1$s-payment-token-%2$s">%3$s</label>
	       </li>',
                esc_attr($this->id),
                esc_attr($token->get_id()),
                esc_html($token->get_display_name()),
                $checked
            );

            return apply_filters(
                'woocommerce_payment_gateway_get_saved_payment_method_option_html',
                $html,
                $token,
                $this
            );
        }
    }

    /**
     * Store the token for the "Save Card ONLY" transaction mode.
     *
     * @param WC_Order $order
     */
    public function tokenize_only_order($order)
    {
        global $woocommerce;

        if (empty($order->get_payment_tokens())) {
            return [
                'result' => 'failure',
                'redirect' => $order->get_view_order_url(),
            ];
        }

        $order->add_meta_data('_beyond_pay_tokenized', 1);
        $order->set_status('bp-tokenized');
        $order->payment_complete();
        $order->save_meta_data();
        $order->save();
        wc_reduce_stock_levels($order);
        $order->add_order_note('Thank you for your order!', true);
        $woocommerce->cart->empty_cart();

        return [
            'result' => 'success',
            'redirect' => $this->get_return_url($order),
        ];
    }

    /**
     * The order processing after merchant presses the "Process Order" button for a saved card.
     *
     * @param int $order_id
     *
     * @return array An array with success:bool and optional message:string property.
     */
    public function process_tokenized_payment($order_id)
    {
        $order = wc_get_order($order_id);
        $tokens = $order->get_payment_tokens();
        if (!empty($tokens)) {
            try {
                $token = new WC_Payment_Token_CC($tokens[0]);
            } catch (Exception $error) {
                $order->add_order_note(
                    'Failed to process payment, payment method connected to order no longer exists or is not valid. Internal error: '
                    . $error->getMessage()
                );

                return [
                    'success' => false,
                    'message' => 'Payment method connected to order no longer exists or is not valid.',
                ];
            }
            $request = $this->build_payment_request(
                'token_payment',
                $order->get_total(),
                $order->get_transaction_id(),
                $token
            );
            $customer_id = $order->get_user_id();
            if (!empty($customer_id)) {
                $request->requestMessage->CustomerAccountCode = $customer_id;
            }
            $request->requestMessage->InvoiceNum = $order_id;
            $this->fill_address_data($request, $order);
            $this->fill_level_2_3_data($request, $order);
            $response = $this->send_gateway_request($request, $order);
            if ($response->ResponseCode == '00000') {
                $this->update_card_type_on_token($response, $token);
                $order->add_payment_token($token);
                $order->delete_meta_data('_beyond_pay_tokenized');
                $this->addCreditCardMetaToOrder($response, $order);
                $order->save_meta_data();
                $order->add_order_note('Your card has been charged for order #'
                    . $order_id, true);
                $order->set_status('processing');
                $order->set_transaction_id($response->responseMessage->GatewayTransID);
                $order->save();
                $order->payment_complete($response->responseMessage->GatewayTransID);

                return [
                    'success' => true,
                ];
            } else {
                $reason = $response->ResponseDescription . ' ('
                    . $response->ResponseCode . ')';
                $order->add_order_note('Processing saved card has failed due to: '
                    . $reason);

                return [
                    'success' => false,
                    'message' => $reason,
                ];
            }
        }
    }

    function logUpdateRequest($order, $request, $response)
    {
        if (!$this->debug_mode || !is_array($response)) {
            return;
        }
        $is_successful = !empty($response['response']['code']) && $response['response']['code'] == 200;
        $order_note_header = $is_successful ? 'Processed payment request' : 'Failed to process payment for request';

        $serializedRequest = json_encode($request, JSON_PRETTY_PRINT);
        $serializedRequest = str_replace([$this->password, $this->login], '--hidden--', $serializedRequest);

        if (!empty($response['cookies'])) {
            unset($response['cookies']);
        }

        $order->add_order_note(
            $order_note_header . ': <br/>' .
            '<div style="background-color: white; overflow: auto; max-height: 100px;">'
            .
            htmlentities($serializedRequest)
            .
            '</div><br/>' .
            'Response was:<br/>' .
            '<div style="background-color: white; overflow: auto; max-height: 100px;">'
            .
            htmlentities(json_encode($response, JSON_PRETTY_PRINT))
            .
            '</div><br/>' .
            '<br/>You are seeing this notice because you have Verbose Logging enabled in Beyond Pay Gateway settings.'
        );
    }

    function update_order_payment_status($order, $comingFromCron = false)
    {
        if ($this->testmode) {
            $reportingApiUrl = 'https://api-test.getbeyondpay.com/Bridgepay.Reporting.API/ReportingAPI.svc/v1';
        } else {
            //$reportingApiUrl = 'https://api.getbeyondpay.com/Reporting.API/ReportingAPI.svc?wsdl';
            $reportingApiUrl = 'https://api.getbeyondpay.com/Reporting.API/ReportingAPI.svc/v1';
        }
        $params = [
            'merchant_account_id' => $this->merchant_account_code,
            'invoice_number' => $order->get_id(),
            'cardholder_first_name' => $order->get_billing_first_name(),
            'date_created' => clone $order->get_date_created(),
        ];

//        $client = new SoapClient($reportingApiUrl, ["trace" => 1, "exception" => 1, 'cache_wsdl' => WSDL_CACHE_BOTH]);
//        $credentials = new Credentials();
//        $credentials->UserName = $this->login;
//        $credentials->Password = $this->password;
//        $apiResult = $client->__soapCall(
//            'PublicGetTransactionsByFilter',
//            [
//                'PublicGetTransactionsByFilter' => [
//                    'credentials' => $credentials,
//                    'filterObject' => $getTransactionsByFilter
//                ]
//            ]
//        );

        // to avoid issues with SOAP extension not being present on the machine, we're building the request manually
        $soapRequest = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $soapRequest .= '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="http://schemas.datacontract.org/2004/07/Bridgepay.Core.Framework" xmlns:ns2="http://bridgepaynetsecuretx.com/reportingapi_v1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:ns3="http://schemas.datacontract.org/2004/07/Bridgepay.Reporting.Core.DataLayer.Filters"><SOAP-ENV:Body><ns2:PublicGetTransactionsByFilter><ns2:credentials><ns1:Password>' . $this->password . '</ns1:Password><ns1:UserName>' . $this->login . '</ns1:UserName></ns2:credentials><ns2:filterObject><ns3:DateRangeFrom xsi:nil="true"/><ns3:DateRangeTo xsi:nil="true"/><ns3:InvoiceNumber>' . $params['invoice_number'] . '</ns3:InvoiceNumber><ns3:MerchantAccountID>' . $params['merchant_account_id'] . '</ns3:MerchantAccountID><ns3:Skip>0</ns3:Skip><ns3:Take>3</ns3:Take></ns2:filterObject></ns2:PublicGetTransactionsByFilter></SOAP-ENV:Body></SOAP-ENV:Envelope>';

        $res = wp_remote_post(
            $reportingApiUrl,
            [
                'headers' => [
                    'SOAPAction' => 'http://bridgepaynetsecuretx.com/reportingapi_v1/IReportingAPIV1/PublicGetTransactionsByFilter',
                    'Content-Type' => 'text/xml; charset=utf-8',
                ],
                'body' => $soapRequest,
            ]
        );

        $this->logUpdateRequest($order, $soapRequest, $res);

        if (is_array($res) && $res['response']['code'] == 200) {
            $apiResponse = simplexml_load_string($res['body']);
            $filterResult = $apiResponse->xpath('//s:Body')[0]
                ->PublicGetTransactionsByFilterResponse
                ->PublicGetTransactionsByFilterResult
                ->children('a', true);
            $recordCount = (int)$filterResult->RecordCount;

            if ($recordCount >= 1) {
                $row = $filterResult->TransactionList->children('b', true)->TransactionRow[0];
                if ((string)$row->ResponseCode == 'A01') {
                    $order->add_meta_data('_beyond_pay_pan', (string)$row->LastFour);
                    $order->add_meta_data('_beyond_expiration_date', (string)$row->ExpirationDate);
                    if ($row->CardBrand) {
                        $order->add_meta_data('_beyond_pay_card_type', (string)$row->CardBrand);
                    }

                    if ($row->TransactionType === "Sale-Auth") {
                        $order->add_meta_data('_beyond_pay_authorized', 1);
                        $order->add_order_note(
                            'Payment was authorized and will be captured when order status is changed to complete.'
                        );
                    } else {
                        $order->add_meta_data('_beyond_pay_authorized', 1);
                        $order->add_meta_data('_beyond_pay_processed', 1);
                        $order->add_order_note('Subscription payment was processed.');
                        $order->set_status('processing');
                    }
                    $order->set_transaction_id((string)$row->TransactionId);
                    $order->save();
                    $order->save_meta_data();
                } else {
                    $order->add_order_note(
                        'Payment was not processed, due to: ' . $row->ProcessorResponse .
                        ' (code ' . (string)$row->ResponseCode . ')'
                    );
                    return false;
                }
                return true;
            }
        }
        return false;
    }
}
