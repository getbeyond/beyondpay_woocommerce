<?php
/*
 * Plugin Name: Beyond Pay for WooCommerce
 * Description: Accept credit cards on your WooCommerce store with Beyond.
 * Author: Beyond
 * Author URI: https://getbeyond.com
 * Plugin URI: https://developer.getbeyond.com
 * Version: 1.4.1
 * Text Domain: beyond-pay-for-woocommerce
 *
 * Tested up to: 5.6.0
 * WC tested up to: 4.8.0
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
	$beyond_pay_gateway->capture_authorised_payment($order);
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

add_action('woocommerce_scheduled_subscription_payment_beyondpay', 'beyond_pay_process_sub_payment', 10, 2 );

function beyond_pay_process_sub_payment( $amount_to_charge, $order ) {
    $beyond_pay_gateway = wc_get_payment_gateway_by_order($order);
    $beyond_pay_gateway->process_subscription_payment( $amount_to_charge, $order );
}
