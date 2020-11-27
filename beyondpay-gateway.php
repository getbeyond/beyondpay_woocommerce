<?php
/*
 * Plugin Name: Beyond Pay for WooCommerce
 * Description: Accept credit cards on your WooCommerce store with Beyond.
 * Author: Beyond
 * Author URI: https://getbeyond.com
 * Plugin URI: https://developer.getbeyond.com
 * Version: 1.3.1
 * Text Domain: beyond-pay-for-woocommerce
 *
 * Tested up to: 5.5.3
 * WC tested up to: 4.6.1
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

/** Check if the class wasn't loaded by a different plugin */
if (!class_exists('BeyondPay\\BeyondPayRequest')) {
    require( dirname(__FILE__) . '/includes/beyond-pay.php' );
}

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

function beyond_pay_order_update($order_id) {
    $order = wc_get_order($order_id);

    if (
	    !$order->meta_exists('_beyond_pay_processed') && // Not fully processed
	    $order->meta_exists('_beyond_pay_authorized') && // but authorized
	    $order->has_status('completed') // and complete.
    ) {

	$beyond_pay_gateway = wc_get_payment_gateway_by_order($order);

	$request = new BeyondPay\BeyondPayRequest();
	$request->RequestType = "019";
	$request->TransactionID = time();

	$request->User = $beyond_pay_gateway->login;
	$request->Password = $beyond_pay_gateway->password;

	$request->requestMessage = new BeyondPay\RequestMessage();
	$request->requestMessage->SoftwareVendor = 'WooCommerce Beyond Pay Plugin';
	$request->requestMessage->TransactionType = 'capture';
	$request->requestMessage->Amount = round($order->get_total() * 100);
	$request->requestMessage->MerchantCode = $beyond_pay_gateway->merchant_code;
	$request->requestMessage->MerchantAccountCode = $beyond_pay_gateway->merchant_account_code;
	$request->requestMessage->ReferenceNumber = $order->get_transaction_id();

	$conn = new BeyondPay\BeyondPayConnection();
	$response = $conn->processRequest($beyond_pay_gateway->api_url, $request);
	if ($response->ResponseCode == '00000') {
	    $order->update_meta_data('_beyond_pay_processed', 1);
	    $order->save_meta_data();
	    $order->add_order_note('Payment for this order was captured.');
	} else {
	    wc_add_notice('Error capturing payment with Beyond Pay', 'error');
	    $order->add_order_note('Beyond Pay Capture Response: ' . htmlentities(BeyondPay\BeyondPayConnection::Serialize($response)));
	}
    }
}

add_action('plugins_loaded', 'beyond_pay_init_gateway_class');

function beyond_pay_init_gateway_class() {
    if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
	add_action('admin_notices', 'beyond_pay_no_wc');
	return;
    }
    require_once dirname(__FILE__) . '/includes/wc-beyond-pay-gateway.php';
}
