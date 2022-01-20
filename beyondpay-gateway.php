<?php
/*
 * Plugin Name: Beyond Pay for WooCommerce
 * Description: Accept credit cards on your WooCommerce store with Beyond.
 * Author: Beyond
 * Author URI: https://getbeyond.com
 * Plugin URI: https://developer.getbeyond.com
 * Version: 1.5.3
 * Text Domain: beyond-pay-for-woocommerce
 *
 * Tested up to: 5.8
 * WC tested up to: 5.8.0
 *
 * Copyright (c) 2020 Above and Beyond Business Tools and Services for Entrepreneurs, Inc.
 *
 * Review the LICENSE file for licensing information.
 */

/** Check if the class wasn't loaded by a different plugin */

use BeyondPay\Constanst;

error_reporting(E_ALL);
ini_set('display_errors', 1);


if (!class_exists('BeyondPay\\BeyondPayRequest')) {
    require(dirname(__FILE__) . '/includes/beyond-pay.php');
}

/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter('woocommerce_payment_gateways', 'beyond_pay_add_gateway_class');
add_action('woocommerce_update_order', 'beyond_pay_order_update');

function beyond_pay_add_gateway_class($gateways)
{
    $gateways[] = 'WC_Beyond_Pay_Gateway';

    return $gateways;
}

function beyond_pay_no_wc()
{
    echo '<div class="error"><p><strong> Beyond Pay requires WooCommerce to be installed and active. </strong></p></div>';
}

function beyond_pay_order_update($order_id)
{
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


function beyond_pay_init_gateway_class()
{
    if (!in_array(
        'woocommerce/woocommerce.php',
        apply_filters('active_plugins', get_option('active_plugins'))
    )) {
        add_action('admin_notices', 'beyond_pay_no_wc');

        return;
    }
    require_once dirname(__FILE__) . '/includes/wc-beyond-pay-gateway.php';
}

add_action('woocommerce_scheduled_subscription_payment_beyondpay', 'beyond_pay_process_sub_payment', 10, 2);

function beyond_pay_process_sub_payment($amount_to_charge, $order)
{
    $beyond_pay_gateway = wc_get_payment_gateway_by_order($order);
    $beyond_pay_gateway->process_subscription_payment($amount_to_charge, $order);
}

add_filter('woocommerce_register_shop_order_post_statuses', 'beyond_pay_add_saved_card_status');

function beyond_pay_add_saved_card_status($statuses)
{
    $statuses['wc-bp-tokenized'] = array(
        'label' => 'Saved Card',
        'public' => false,
        'exclude_from_search' => false,
        'show_in_admin_all_list' => true,
        'show_in_admin_status_list' => true,
        'label_count' => _n_noop(
            'Saved Card <span class="count">(%s)</span>',
            'Saved Card <span class="count">(%s)</span>',
            'beyond-pay-gateway'
        ),
    );

    return $statuses;
}

add_filter('wc_order_statuses', 'beyond_pay_add_saved_card_to_order_statuses');

function beyond_pay_add_saved_card_to_order_statuses($order_statuses)
{
    $order_statuses['wc-bp-tokenized'] = 'Saved Card';

    return $order_statuses;
}


add_filter('woocommerce_order_is_pending_statuses', 'beyond_pay_mark_saved_card_as_pending_status');

function beyond_pay_mark_saved_card_as_pending_status($statuses)
{
    array_push($statuses, 'wc-bp-tokenized');

    return $statuses;
}

add_action('woocommerce_order_actions_end', 'beyond_pay_add_process_order_button');

function beyond_pay_add_process_order_button($order_id)
{
    $order = wc_get_order($order_id);

    if ($order->get_meta('_beyond_pay_tokenized')) {
        ?>
        <li class="wide">
            <button type="button" class="button"
                    onclick="beyondPayProcessTokenizedOrder('<?php
                    echo esc_url(get_edit_post_link($order_id)); ?>',<?php
                    echo $order_id ?>)">
                Process Payment
            </button>
        </li>
        <?php
    }
}

add_action('wp_ajax_beyond_pay_process_tokenized_order', 'beyond_pay_handle_saved_card_processing');

function beyond_pay_handle_saved_card_processing()
{
    $order_id = intval($_POST['order_id']);
    if ($order_id) {
        $beyond_pay_gateway = wc_get_payment_gateway_by_order($order_id);
        if ($beyond_pay_gateway) {
            $result = $beyond_pay_gateway->process_tokenized_payment($order_id);
            die(json_encode($result));
        }
    }
    die(
    json_encode(array(
        'success' => false,
        'message' => 'Unable to deterimine order.'
    ))
    );
}

add_action('admin_enqueue_scripts', 'beyond_pay_enqueue_woocommerce_scripts');

function beyond_pay_enqueue_woocommerce_scripts()
{
    wp_enqueue_script('beyondpay_admin_order', plugins_url('assets/js/beyondpay-admin-order.js', __FILE__));
    wp_enqueue_style(
        'beyondpay_font_awsome',
        'https://beyondone-cdn-public-assets-prd.getbeyond.cloud/vendor/font-awesome/4.5.0/css/font-awesome.css'
    );
    wp_enqueue_style('beyondpay_admin_styling', plugins_url('assets/css/admin-styling.css', __FILE__));
}

add_action('woocommerce_admin_order_data_after_billing_address', 'beyond_pay_display_update_payment_status_button');
/**
 * Display the card details with an icon.
 *
 * @param WC_Order $order
 */
function beyond_pay_display_card_brand($order)
{
    $gateway = wc_get_payment_gateway_by_order($order);
    if ($gateway instanceof WC_Beyond_Pay_Gateway && !empty($order->get_meta('_beyond_pay_pan'))) {
        switch (str_replace(' ', '-', strtolower($order->get_meta('_beyond_pay_card_type')))) {
            case 'visa':
                // case 'amazon-pay':
            case 'amex':
                // case 'apple-pay':
            case 'dinners-club':
            case 'discover':
            case 'jcb':
            case 'mastercard':
            case 'paypal':
            case 'stripe':
            case 'visa':
                $icon = 'cc-' . strtolower($order->get_meta('_beyond_pay_card_type'));
                break;
            default:
                $icon = 'credit-card';
        }
        $exp_date = str_replace('/', '', $order->get_meta('_beyond_expiration_date'));

        ?>
        <p class="beyond-pay-cc-brand">
            <span class="beyond-pay-icon fa-<?php
            echo $icon; ?>"></span>
            **** **** **** <?php
            echo esc_html($order->get_meta('_beyond_pay_pan')); ?>
            Exp: <?php
            echo esc_html(substr($exp_date, 0, 2)) . '/' . esc_html(substr($exp_date, 2)); ?>
        </p>

        <?php
    }
}

function beyond_pay_display_update_payment_status_button($order)
{
    if ($order->get_status() != 'pending') {
        return;
    }
    ?>
    <p>
        <input type='hidden' name='action' value='beyond_pay_update_payment_status'>
        <input type='hidden' name='order_id' value='<?php echo $order->id ?>'>
        <a href="#" class="button save_order button-primary"
           id="beyond-pay-update-payment-status-btn"
           onclick="beyond_pay_update_payment_status()">Update Payment Status
        </a>
    </p>

    <script>
        function beyond_pay_update_payment_status() {
            jQuery.post(
                ajaxurl,
                {
                    // TODO
                    //security: ajax_object.ajax_nonce,
                    action: 'beyond_pay_update_payment_status',
                    order_id: <?php echo $order->id ?>
                },
                function (response) {
                    alert(response.message);
                    if (response.success) {
                        window.location.reload();
                    }
                },
                'json');
        }
    </script>
    <?php
}

add_action('woocommerce_after_order_details', 'beyond_pay_display_update_payment_status_button');

function beyond_pay_update_payment_status()
{
    try {
        if (empty($_POST['order_id'])) {
            die();
        }
        $order_id = $_POST['order_id'];
        $order = wc_get_order($order_id);
        if (!$order) {
            throw new Exception("Could not find WooCommerce order");
        }

        if ($order->get_payment_method() != "beyondpay") {
            throw new Exception("Order wasn't paid using Beyond Pay.");
        };
        $beyond_pay_gateway = wc_get_payment_gateway_by_order($order);

        if ($order->meta_exists('_beyond_pay_authorized')) {
            throw new Exception("Order already authorized.");
        }

        if ($beyond_pay_gateway->update_order_payment_status($order)) {
            $message = "Payment was successful and the order status was updated.";
        } else {
            $message = "There were no changes to the payment status.";
        }
        echo json_encode(['success' => true, 'message' => $message]);
    } catch (Exception $e) {
        echo json_encode(['success' => true, 'message' => 'ERROR: ' . $e->getMessage()]);
    }
    wp_die();
}


add_action('wp_ajax_beyond_pay_update_payment_status', 'beyond_pay_update_payment_status');
