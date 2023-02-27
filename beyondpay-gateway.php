<?php
/*
 * Plugin Name: Beyond Pay for WooCommerce
 * Description: Accept credit cards on your WooCommerce store with Beyond.
 * Author: Beyond
 * Author URI: https://getbeyond.com
 * Plugin URI: https://developer.getbeyond.com
 * Version: 1.7.1
 * Text Domain: beyond-pay-for-woocommerce
 *
 * Tested up to: 6.1.1
 * WC tested up to: 7.0.0
 *
 * Copyright (c) 2020 Above and Beyond Business Tools and Services for Entrepreneurs, Inc.
 *
 * Review the LICENSE file for licensing information.
 */

/** Check if the class wasn't loaded by a different plugin */

use BeyondPay\Constanst;

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
    $site_plugins = apply_filters('active_plugins', get_option('active_plugins', []));
    $network_plugins = apply_filters('active_plugins', get_site_option('active_sitewide_plugins', []));
    $woocommerce_plugin = 'woocommerce/woocommerce.php';
    if (!in_array($woocommerce_plugin, $site_plugins) && !array_key_exists($woocommerce_plugin, $network_plugins)) {
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
    $statuses['wc-bp-tokenized'] = [
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
    ];

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
                echo $order_id ?>)"
        >
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
    json_encode([
        'success' => false,
        'message' => 'Unable to deterimine order.',
    ])
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
            echo $icon; ?>"
            ></span>
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
    <a href="#" class="button save_order button-primary"
       id="beyond-pay-update-payment-status-btn"
       onclick="beyond_pay_update_payment_status()"
    >Update Payment Status
    </a>
  </p>
  <script>
    function beyond_pay_update_payment_status() {
      const button = jQuery('#beyond-pay-update-payment-status-btn');
      if (button.hasClass('disabled')) {
        return;
      }
      button.addClass('disabled');
      jQuery.post(
        ajaxurl,
        {
          // TODO
          //security: ajax_object.ajax_nonce,
          action: 'beyond_pay_update_payment_status',
          order_id: <?php echo $order->get_id() ?>
        },
        function (response) {
          alert(response.message);
          button.removeClass('disabled');
          if (response.success) {
            window.location.reload();
          }
        },
        'json'
      );
    }
  </script>
    <?php
}

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
            $message = "Payment status synchronized.";
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

function beyond_pay_cron_schedules($schedules)
{
    $schedules['half_hour'] = [
        'interval' => 30 * 60,
        'display' => esc_html__('Every Half Hour'),
    ];

    return $schedules;
}

add_filter('cron_schedules', 'beyond_pay_cron_schedules');

function beyond_pay_beyond_pay_cron_recheck_pending_payments()
{
    $orders = wc_get_orders([
            'limit' => -1,
            'type' => 'shop_order',
            'status' => ['wc-pending'],
        ]
    );

    if (!empty($orders)) {
        // to avoid long-running cron jobs, let's limit to processing random 10 orders that are pending payments.
        // Invalid state due to gateway payment issue is a rare situation and at time there shouldn't be more than a few of such cases.
        $max_orders_to_process = 10;
        $orders_processed = 0;
        shuffle($orders);
        foreach ($orders as $order) {
            try {
                if ($order->get_payment_method() != "beyondpay") {
                  continue;
                };

                if ($order->meta_exists('_beyond_pay_authorized')) {
                    continue;
                }
                $beyond_pay_gateway = wc_get_payment_gateway_by_order($order);
                $beyond_pay_gateway->update_order_payment_status($order);
            } catch (Exception $e) {
                // ignore
                error_log($e->getMessage());
            }
            $orders_processed++;
            if ($orders_processed >= $max_orders_to_process) {
                break;
            }
        }
    }
}

$statusUpdateHookName = 'beyond_pay_hook_recheck_pending_payments';
if (get_option('beyondpay_woo_automatic_transaction_status_updates') == 'on') {
    add_action($statusUpdateHookName, 'beyond_pay_beyond_pay_cron_recheck_pending_payments');
    if (!wp_next_scheduled($statusUpdateHookName)) {
        wp_schedule_event(time(), 'half_hour', $statusUpdateHookName);
    }
} else {
    $safety = 0;
    while ($safety++ < 10) {
        $timestamp = wp_next_scheduled($statusUpdateHookName);
        if (!$timestamp) {
            break;
        }
        wp_unschedule_event($timestamp, $statusUpdateHookName);
    }
}
