<?php
/**
 * Order page rates when click on "Save" OR "Recalculate".
 */
if (!class_exists('EnRnlOrderRates')) {

    class EnRnlOrderRates
    {
        public $shipping_address = [];

        public function __construct()
        {
            add_action('wp_ajax_eniture_calculate_shipping_rates_admin', [$this, 'eniture_calculate_shipping_rates_admin']);
            add_filter('en_order_accessories', [$this, 'en_order_accessories']);
        }

        // Receiver address along order page.
        public function en_order_accessories($shipping_address)
        {
            return array_merge($this->shipping_address, $shipping_address);
        }

        // Calculate shipping
        public function eniture_calculate_shipping_rates_admin()
        {
            $products = isset($_POST['products']) ? (array) $_POST['products'] : array();
            $orderItems = array_map(function($productId) {
                return new WC_Order_Item_Product((int) $productId);
            }, $products);

            $old_cart = WC()->cart->get_cart();
            
            $cart = WC()->cart;
            $cart->set_cart_contents(array());
            foreach($orderItems as $orderItem)
            {
                $cart->add_to_cart($orderItem->get_product_id(), $orderItem->get_quantity());
            }

            $package = [
                'destination' => [
                    'country' => isset($_POST['country']) ? sanitize_text_field($_POST['country']) : '',
                    'state' => isset($_POST['state']) ? sanitize_text_field($_POST['state']) : '',
                    'postcode' => isset($_POST['postcode']) ? sanitize_text_field($_POST['postcode']) : '',
                    'city' => isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '',
                    'address' => isset($_POST['address_line_1']) ? sanitize_text_field($_POST['address_line_1']) : '',
                    'address_1' => isset($_POST['address_line_1']) ? sanitize_text_field($_POST['address_line_1']) : '',
                    'address_2' => isset($_POST['address_line_2']) ? sanitize_text_field($_POST['address_line_2']) : '',
                ],
                'contents' => array_map(function($orderItem) {
                    return [
                        'quantity' => (int) $orderItem->get_quantity(),
                        'data' => $orderItem->get_product(),
                        'line_total' => $orderItem->get_total(),
                        'line_tax' => $orderItem->get_total_tax(),
                        'line_subtotal' => $orderItem->get_subtotal(),
                        'line_subtotal_tax' => $orderItem->get_subtotal_tax()
                    ];
                }, $orderItems),
                'contents_cost' => array_sum(array_map(function (WC_Order_Item_Product $orderItem) {
                    return $orderItem->get_total();
                }, $orderItems))
            ];

            // old shipping address
            $old_shipping_postcode = WC()->customer->get_shipping_postcode();
            $old_shipping_state = WC()->customer->get_shipping_state();
            $old_shipping_county = WC()->customer->get_shipping_country();
            $old_shipping_city = WC()->customer->get_shipping_city();
            $old_shipping_address = WC()->customer->get_billing_address_1();

            WC()->customer->set_shipping_postcode($package['destination']['postcode']);
            WC()->customer->set_shipping_state($package['destination']['state']);
            WC()->customer->set_shipping_country($package['destination']['country']);
            WC()->customer->set_shipping_city($package['destination']['city']);
            WC()->customer->set_billing_address_1($package['destination']['address']);

            $shippingZone = WC_Shipping_Zones::get_zone_matching_package($package);
            /** @var WC_Shipping_Method[] $shippingMethods */
            $shippingMethods = $shippingZone->get_shipping_methods(true);

            $prices = array();
            foreach($shippingMethods as $shippingMethod)
            {
                if(!empty($shippingMethod->id) && !empty($_POST['shipping']) && $shippingMethod->id == $_POST['shipping']){
                    $rates = $shippingMethod->get_rates_for_package($package);
                    foreach($rates as $rate)
                    {
                        $prices[] = [
                            'id' => wp_kses($rate->get_id(), array()),
                            'method' => wp_kses($rate->get_method_id(), array()),
                            'total' => (float) $rate->get_cost()
                        ];
                    }
                    break;
                }
            }


            WC()->customer->set_shipping_postcode($old_shipping_postcode);
            WC()->customer->set_shipping_state($old_shipping_state);
            WC()->customer->set_shipping_country($old_shipping_county);
            WC()->customer->set_shipping_city($old_shipping_city);
            WC()->customer->set_billing_address_1($old_shipping_address);
            WC()->cart->set_cart_contents($old_cart);

            // Remove backup Rates in case other rates are available
            if (get_option('enable_backup_rates_rnl_ltl') == 'yes' && count($prices) > 1) {
                $backup_rate_id = 'rnl:backup_rates';
                foreach ($prices as $key => $value) {
                    if (isset($value['id']) && $value['id'] == $backup_rate_id) {
                        unset($prices[$key]);
                        $prices = array_values($prices);
                        break;
                    }
                }
            }
            
            wp_send_json_success([
                'shipping' => $prices
            ]);
        }
    }

    new EnRnlOrderRates();
}