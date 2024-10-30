<?php

/**
 * R+L WooComerce Get R+L Shipping Calculation Class
 * @package     Woocommerce R+L Edition
 * @author      <https://eniture.com/>
 * @copyright   Copyright (c) 2017, Eniture
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * R+L Freight Initialize
 */
function rnl_logistics_init()
{
    if (!class_exists('RNL_Freight_Shipping_Class')) {

        /**
         * R+L Shipping Calculation Class
         */
        class RNL_Freight_Shipping_Class extends WC_Shipping_Method
        {

            public $forceAllowShipMethod = array();
            public $getPkgObj;
            public $Rnl_Quotes_Liftgate_As_Option;
            public $instore_pickup_and_local_delivery;
            public $web_service_inst;
            public $package_plugin;
            public $woocommerce_package_rates;
            public $InstorPickupLocalDelivery;
            public $shipment_type;
            public $quote_settings;
            public $minPrices;
            public $accessorials;
            // FDO
            public $en_fdo_meta_data = [];
            public $en_fdo_meta_data_third_party = [];
            // Micro Warehouse
            public $min_prices;

            /**
             * WooCommerce Shipping Field Attributes
             * @param $instance_id
             */
            public function __construct($instance_id = 0)
            {
                error_reporting(0);
                $this->id = 'rnl';
                $this->instance_id = absint($instance_id);
                $this->method_title = __('R+L Freight');
                $this->method_description = __('Shipping rates from R+L Freight.');
                $this->supports = array(
                    'shipping-zones',
                    'instance-settings',
                    'instance-settings-modal',
                );
                $this->enabled = "yes";
                $this->title = "LTL Freight Quotes - R+L Edition";
                $this->init();
                $this->Rnl_Quotes_Liftgate_As_Option = new Rnl_Quotes_Liftgate_As_Option();
            }

            /**
             * quote settings data
             * @global $wpdb $wpdb
             */
            function rnl_quote_settings()
            {
                $this->web_service_inst->quote_settings['label'] = get_option('wc_settings_rnl_label_as');
                $this->web_service_inst->quote_settings['guaranteed_pm_label_as'] = get_option('guaranteed_pm_label_as');
                $this->web_service_inst->quote_settings['guaranteed_am_label_as'] = get_option('guaranteed_am_label_as');
                $this->web_service_inst->quote_settings['guaranteed_hourly_label_as'] = get_option('guaranteed_hourly_label_as');
                $this->web_service_inst->quote_settings['handling_fee'] = get_option('wc_settings_rnl_handling_fee');
                $this->web_service_inst->quote_settings['liftgate_delivery'] = get_option('wc_settings_rnl_liftgate');
                $this->web_service_inst->quote_settings['liftgate_delivery_option'] = get_option('rnl_quotes_liftgate_delivery_as_option');
                $this->web_service_inst->quote_settings['residential_delivery'] = get_option('wc_settings_rnl_residential');
                $this->web_service_inst->quote_settings['liftgate_resid_delivery'] = get_option('en_woo_addons_liftgate_with_auto_residential');
                $this->web_service_inst->quote_settings['limited_access_delivery'] = get_option('rnl_limited_access_delivery');
                $this->web_service_inst->quote_settings['transit_time'] = get_option('wc_settings_rnl_delivey_estimate');
                $this->web_service_inst->quote_settings['HAT_status'] = get_option('rnl_hold_at_terminal_checkbox_status');
                $this->web_service_inst->quote_settings['HAT_fee'] = get_option('rnl_hold_at_terminal_fee');
                // Cuttoff Time
                $this->web_service_inst->quote_settings['delivery_estimates'] = get_option('rnl_delivery_estimates');
                $this->web_service_inst->quote_settings['orderCutoffTime'] = get_option('rnl_freight_order_cut_off_time');
                $this->web_service_inst->quote_settings['shipmentOffsetDays'] = get_option('rnl_freight_shipment_offset_days');

                $this->web_service_inst->quote_settings['handling_weight'] = get_option('rnl_freight_handling_weight');
                $this->web_service_inst->quote_settings['maximum_handling_weight'] = get_option('rnl_freight_maximum_handling_weight');
            }

            /**
             * WooCommerce Shipping Fields init
             */
            function init()
            {
                $this->init_form_fields();
                $this->init_settings();
                add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
            }

            /**
             * Enable WooCommerce Shipping Form Fields
             */
            function init_form_fields()
            {
                $this->instance_form_fields = array(
                    'enabled' => array(
                        'title' => __('Enable / Disable', 'rnl'),
                        'type' => 'checkbox',
                        'label' => __('Enable This Shipping Service', 'rnl'),
                        'default' => 'no',
                        'id' => 'rnl_enable_disable_shipping'
                    )
                );
            }

            public function forceAllowShipMethod($forceShowMethods)
            {
                if (!empty($this->getPkgObj->ValidShipmentsArrRnL) && (!in_array("ltl_freight", $this->getPkgObj->ValidShipmentsArrRnL))) {
                    $this->forceAllowShipMethod[] = "free_shipping";
                    $this->forceAllowShipMethod[] = "valid_third_party";
                } else {
                    $this->forceAllowShipMethod[] = "ltl_shipment";
                }

                $forceShowMethods = array_merge($forceShowMethods, $this->forceAllowShipMethod);
                return $forceShowMethods;
            }

            /**
             * Virtual Products
             */
            public function en_virtual_products()
            {
                global $woocommerce;
                $products = $woocommerce->cart->get_cart();
                $items = $product_name = [];
                foreach ($products as $key => $product_obj) {
                    $product = $product_obj['data'];
                    $is_virtual = $product->get_virtual();

                    if ($is_virtual == 'yes') {
                        $attributes = $product->get_attributes();
                        $product_qty = $product_obj['quantity'];
                        $product_title = str_replace(array("'", '"'), '', $product->get_title());
                        $product_name[] = $product_qty . " x " . $product_title;

                        $meta_data = [];
                        if (!empty($attributes)) {
                            foreach ($attributes as $attr_key => $attr_value) {
                                $meta_data[] = [
                                    'key' => $attr_key,
                                    'value' => $attr_value,
                                ];
                            }
                        }

                        $items[] = [
                            'id' => $product_obj['product_id'],
                            'name' => $product_title,
                            'quantity' => $product_qty,
                            'price' => $product->get_price(),
                            'weight' => 0,
                            'length' => 0,
                            'width' => 0,
                            'height' => 0,
                            'type' => 'virtual',
                            'product' => 'virtual',
                            'sku' => $product->get_sku(),
                            'attributes' => $attributes,
                            'variant_id' => 0,
                            'meta_data' => $meta_data,
                        ];
                    }
                }

                $virtual_rate = [];

                if (!empty($items)) {
                    $virtual_rate = [
                        'id' => 'en_virtual_rate',
                        'label' => 'Virtual Quote',
                        'cost' => 0,
                    ];

                    $virtual_fdo = [
                        'plugin_type' => 'ltl',
                        'plugin_name' => 'wwe_quests',
                        'accessorials' => '',
                        'items' => $items,
                        'address' => '',
                        'handling_unit_details' => '',
                        'rate' => $virtual_rate,
                    ];

                    $meta_data = [
                        'sender_origin' => 'Virtual Product',
                        'product_name' => wp_json_encode($product_name),
                        'en_fdo_meta_data' => $virtual_fdo,
                    ];

                    $virtual_rate['meta_data'] = $meta_data;

                }

                return $virtual_rate;
            }

            /**
             * Calculate Shipping Rates For R+L
             * @param string $package
             * @return boolean|string
             */
            public function calculate_shipping($package = array(), $eniture_admin_order_action = false)
            {
                if (is_admin() && !wp_doing_ajax() && !$eniture_admin_order_action) {
                    return [];
                }

                $this->package_plugin = get_option('rnl_quotes_packages_quotes_package');

                $this->instore_pickup_and_local_delivery = FALSE;

                $coupn = WC()->cart->get_coupons();
                if (isset($coupn) && !empty($coupn)) {
                    $free_shipping = $this->rnl_shipping_coupon_rate($coupn);
                    if ($free_shipping == 'y')
                        return FALSE;
                }
                $freight_zipcode = "";
                $rnl_woo_obj = new RNL_Woo_Update_Changes();
                (strlen(WC()->customer->get_shipping_postcode()) > 0) ? $freight_zipcode = WC()->customer->get_shipping_postcode() : $freight_zipcode = $rnl_woo_obj->rnl_postcode();
                $obj = new RNL_Freight_Shipping_Get_Package();

                $this->getPkgObj = $obj;

                $rnl_res_inst = new RNL_Freight_Get_Shipping_Quotes();
                $this->web_service_inst = $rnl_res_inst;

                $this->rnl_quote_settings();

                if (isset($this->web_service_inst->quote_settings['handling_fee']) &&
                    ($this->web_service_inst->quote_settings['handling_fee'] == "-100%")
                ) {
                    $rates = array(
                        'id' => $this->id . ':' . 'free',
                        'label' => 'Free Shipping',
                        'cost' => 0,
                        'plugin_name' => 'rnl',
                        'plugin_type' => 'ltl',
                        'owned_by' => 'eniture'
                    );
                    $this->add_rate($rates);
                    
                    return [];
                }

                $rnl_package = $obj->group_rnl_shipment($package, $rnl_res_inst, $freight_zipcode);
                $shipping_rule_obj = new EnRnlShippingRulesAjaxReq();
                $shipping_rules_applied = $shipping_rule_obj->apply_shipping_rules($rnl_package);
                if ($shipping_rules_applied) {
                    return [];
                }
                $handlng_fee = get_option('wc_settings_rnl_handling_fee');
                $quotes = array();
                $rate = array();

                add_filter('force_show_methods', array($this, 'forceAllowShipMethod'));

                $eniturePluigns = json_decode(get_option('EN_Plugins'));
                $calledMethod = array();
                $smallPluginExist = 0;
                $smallQuotes = array();

                $small_products = [];
                $ltl_products = [];

                if (isset($rnl_package) && !empty($rnl_package)) {
                    foreach ($rnl_package as $locId => $sPackage) {
                        if (array_key_exists('rnl', $sPackage)) {
                            $ltl_products[] = $sPackage;
                            $web_service_arr = $rnl_res_inst->rnl_shipping_array($sPackage, $this->package_plugin);
                            $response = $rnl_res_inst->rnl_get_web_quotes($web_service_arr, $rnl_package, $locId);
                            
                            // Add backup rates in the shipping rates
                            if ((empty($response) && get_option('rnl_ltl_backup_rates_carrier_returns_error') == 'yes') || (!empty($response) && isset($response->backupRate) && get_option('rnl_ltl_backup_rates_carrier_fails_to_return_response') == 'yes')) {
                                $this->rnl_backup_rates();
                                return [];
                            }

                            if (empty($response)) {
                                return [];
                            }

                            $quotes[] = $response;
                            continue;
                        } elseif (array_key_exists('small', $sPackage)) {
                            $small_products[] = $sPackage;
                        }
                    }

                    if (isset($small_products) && !empty($small_products) && !empty($ltl_products)) {
                        foreach ($eniturePluigns as $enIndex => $enPlugin) {
                            $freightSmallClassName = 'WC_' . $enPlugin;
                            if (!in_array($freightSmallClassName, $calledMethod)) {
                                if (class_exists($freightSmallClassName)) {
                                    $smallPluginExist = 1;
                                    $SmallClassNameObj = new $freightSmallClassName();
                                    $package['itemType'] = 'ltl';
                                    $package['sPackage'] = $small_products;
                                    $smallQuotesResponse = $SmallClassNameObj->calculate_shipping($package, true);
                                    $smallQuotes[] = $smallQuotesResponse;
                                }
                                $calledMethod[] = $freightSmallClassName;
                            }
                        }
                    }
                }

                if (count($quotes) < 1) {
                    return 'error';
                }

                // Micro Warehouse
                $en_check_action_warehouse_appliance = apply_filters('en_check_action_warehouse_appliance', FALSE);
                if (!$en_check_action_warehouse_appliance && count($quotes) < 1) {
                    return 'error';
                }

                foreach ($smallQuotes as $small_key => $small_quote) {
                    if (empty($small_quote)) {
                        unset($smallQuotes[$small_key]);
                    }
                }

                $smallQuotes = $this->en_spq_sort($smallQuotes);

                $smallQuotes = (is_array($smallQuotes) && (!empty($smallQuotes))) ? reset($smallQuotes) : $smallQuotes;
                $smallMinRate = (is_array($smallQuotes) && (!empty($smallQuotes))) ? current($smallQuotes) : $smallQuotes;

                // Virtual products
                $virtual_rate = $this->en_virtual_products();

                //FDO
                if (isset($smallMinRate['meta_data']['en_fdo_meta_data'])) {

                    if (!empty($smallMinRate['meta_data']['en_fdo_meta_data']) && !is_array($smallMinRate['meta_data']['en_fdo_meta_data'])) {
                        $en_third_party_fdo_meta_data = json_decode($smallMinRate['meta_data']['en_fdo_meta_data'], true);
                        isset($en_third_party_fdo_meta_data['data']) ? $smallMinRate['meta_data']['en_fdo_meta_data'] = $en_third_party_fdo_meta_data['data'] : '';
                    }
                    $this->en_fdo_meta_data_third_party = (isset($smallMinRate['meta_data']['en_fdo_meta_data']['address'])) ? [$smallMinRate['meta_data']['en_fdo_meta_data']] : $smallMinRate['meta_data']['en_fdo_meta_data'];
                }

                $smpkgCost = (isset($smallMinRate['cost'])) ? $smallMinRate['cost'] : 0;

                if (isset($smallMinRate) && (!empty($smallMinRate))) {
                    switch (TRUE) {
                        case (isset($smallMinRate['minPrices'])):
                            $small_quotes = $smallMinRate['minPrices'];
                            break;
                        default :
                            $shipment_zipcode = key($smallQuotes);
                            $small_quotes = array($shipment_zipcode => $smallMinRate);
                            break;
                    }
                }

                $this->quote_settings = $this->web_service_inst->quote_settings;
                $handling_fee = $this->quote_settings['handling_fee'];
                $this->accessorials = array();

                ($this->quote_settings['liftgate_delivery'] == "yes") ? $this->accessorials[] = "L" : "";
                ($this->quote_settings['residential_delivery'] == "yes") ? $this->accessorials[] = "R" : "";
                ($this->quote_settings['limited_access_delivery'] == "yes") ? $this->accessorials[] = "LA" : "";

                // Virtual products
                if (count($quotes) > 1 || $smpkgCost > 0 || !empty($virtual_rate)) {

                    // Multiple Shipment
                    $multi_cost = 0;
                    $s_multi_cost = 0;
                    $access_multi_cost = 0;
                    $hold_at_terminal_fee = 0;
                    $_label = "";
                    $access_label = [];
                    $access_append_label = "";
                    $this->minPrices = array();

                    $this->quote_settings['shipment'] = "multi_shipment";
                    $shipment_numbers = 0;

                    (isset($small_quotes) && count($small_quotes) > 0) ? $this->minPrices['RNL_LIFT'] = $small_quotes : "";
                    (isset($small_quotes) && count($small_quotes) > 0) ? $this->minPrices['RNL_NOTLIFT'] = $small_quotes : "";
                    (isset($small_quotes) && count($small_quotes) > 0) ? $this->minPrices['RNL_ACCESS'] = $small_quotes : "";
                    (isset($small_quotes) && count($small_quotes) > 0) ? $this->minPrices['RNL_NOTACCESS'] = $small_quotes : "";
                    (isset($small_quotes) && count($small_quotes) > 0) ? $this->minPrices['RNL_HAT'] = $small_quotes : "";

                    // Virtual products
                    if (!empty($virtual_rate)) {
                        $en_virtual_fdo_meta_data[] = $virtual_rate['meta_data']['en_fdo_meta_data'];
                        $virtual_meta_rate['virtual_rate'] = $virtual_rate;
                        $this->minPrices['RNL_LIFT'] = isset($this->minPrices['RNL_LIFT']) && !empty($this->minPrices['RNL_LIFT']) ? array_merge($this->minPrices['RNL_LIFT'], $virtual_meta_rate) : $virtual_meta_rate;
                        $this->minPrices['RNL_NOTLIFT'] = isset($this->minPrices['RNL_NOTLIFT']) && !empty($this->minPrices['RNL_NOTLIFT']) ? array_merge($this->minPrices['RNL_NOTLIFT'], $virtual_meta_rate) : $virtual_meta_rate;
                        $this->minPrices['RNL_ACCESS'] = isset($this->minPrices['RNL_ACCESS']) && !empty($this->minPrices['RNL_ACCESS']) ? array_merge($this->minPrices['RNL_ACCESS'], $virtual_meta_rate) : $virtual_meta_rate;
                        $this->minPrices['RNL_NOTACCESS'] = isset($this->minPrices['RNL_NOTACCESS']) && !empty($this->minPrices['RNL_NOTACCESS']) ? array_merge($this->minPrices['RNL_NOTACCESS'], $virtual_meta_rate) : $virtual_meta_rate;
                        $this->en_fdo_meta_data_third_party = !empty($this->en_fdo_meta_data_third_party) ? array_merge($this->en_fdo_meta_data_third_party, $en_virtual_fdo_meta_data) : $en_virtual_fdo_meta_data;
                        if ($this->quote_settings['HAT_status'] == 'yes') {
                            $this->minPrices['RNL_HAT'] = isset($this->minPrices['RNL_HAT']) && !empty($this->minPrices['RNL_HAT']) ? array_merge($this->minPrices['RNL_HAT'], $virtual_meta_rate) : $virtual_meta_rate;
                        }
                    }

                    $rates = $this->get_multi_formatted_quotes($quotes, $smpkgCost);
                    $this->shipment_type = 'multiple';
                } else {

                    // Single Shipment
                    $quote = (is_array($quotes) && (!empty($quotes))) ? reset($quotes) : array();

                    if (!empty($quote)) {
                        $rates = $this->get_single_formatted_quotes($quote);
                        $cost_sorted_key = array();

                        $this->quote_settings['shipment'] = "single_shipment";
                        $this->quote_settings['shipment_numbers'] = "1";

                        if (is_array($rates) && (!empty($rates))) {

                            foreach ($rates as $key => $quote) {
                                $handling_fee = (isset($rates['markup']) && (strlen($rates['markup']) > 0)) ? $rates['markup'] : $handling_fee;
                                $_cost = (isset($quote['cost'])) ? $quote['cost'] : 0;

                                if (!isset($quote['hat_append_label'])) {
                                    (isset($rates[$key]['cost'])) ? $rates[$key]['cost'] = $this->add_handling_fee($_cost, $handling_fee) : "";
                                    (isset($rates[$key]['meta_data']['en_fdo_meta_data']['rate']['cost'])) ? $rates[$key]['meta_data']['en_fdo_meta_data']['rate']['cost'] = $this->add_handling_fee($_cost, $handling_fee) : "";
                                }

                                $cost_sorted_key[$key] = (isset($quote['cost'])) ? $quote['cost'] : 0;
                                (isset($rates[$key]['shipment'])) ? $rates[$key]['shipment'] = "single_shipment" : "";
                            }

                            // Array_multisort
                            array_multisort($cost_sorted_key, SORT_ASC, $rates);
                        }
                    }

                    $this->shipment_type = 'single';
                }

                // Sorting rates in ascending order
                $rates = $this->sort_asec_order_arr($rates);
                $rates = $this->rnl_add_rate_arr($rates);
                // Origin terminal address
                if ($this->shipment_type == 'single') {
                    (isset($this->web_service_inst->InstorPickupLocalDelivery->localDelivery) && ($this->web_service_inst->InstorPickupLocalDelivery->localDelivery->status == 1)) ? $this->local_delivery($this->web_service_inst->en_wd_origin_array['fee_local_delivery'], $this->web_service_inst->en_wd_origin_array['checkout_desc_local_delivery'], $this->web_service_inst->en_wd_origin_array) : "";
                    (isset($this->web_service_inst->InstorPickupLocalDelivery->inStorePickup) && ($this->web_service_inst->InstorPickupLocalDelivery->inStorePickup->status == 1)) ? $this->pickup_delivery($this->web_service_inst->en_wd_origin_array['checkout_desc_store_pickup'], $this->web_service_inst->en_wd_origin_array, $this->web_service_inst->InstorPickupLocalDelivery->totalDistance) : "";
                }

                return $rates;
            }

            /**
             * Multishipment
             * @return array
             */
            function arrange_multiship_freight($cost, $id, $label_sufex, $append_label)
            {
                $en_label = get_option('wc_settings_rnl_label_as');
                $en_label_as = isset($en_label) && strlen($en_label) > 0 ? $en_label : "Freight";
                $multiship = array(
                    'id' => $id,
                    'label' => $en_label_as,
                    'label' => 'Freight',
                    'cost' => $cost,
                    'label_sufex' => $label_sufex,
                    'plugin_name' => 'rnl',
                    'plugin_type' => 'ltl',
                    'owned_by' => 'eniture'
                );

                ($id == 'RNL_HAT') ? $multiship['hat_append_label'] = $append_label : $multiship['append_label'] = $append_label;

                return $multiship;
            }

            public function en_spq_sort($smallQuotes)
            {
                $spq_quotes = [];
                foreach ($smallQuotes as $key => $quote) {
                    $quote = (is_array($quote) && (!empty($quote))) ? reset($quote) : $quote;
                    !empty($quote) && isset($quote['cost']) ? $spq_quotes[] = $quote : '';
                }

                if (!empty($spq_quotes)) {
                    $rates[] = $this->sort_asec_order_arr($spq_quotes);
                    return $rates;
                }

                return $smallQuotes;
            }

            /**
             * Remove array
             * @return array
             */
            public function remove_array($quote, $remove_index)
            {
                unset($quote[$remove_index]);

                return $quote;
            }

            /**
             * Add handling fee in rate price
             * @param string type $price
             * @param string type $handling_fee
             * @return float type
             */
            function add_handling_fee($price, $handling_fee)
            {
                $handling_fee = $price > 0 ? $handling_fee : 0;
                $handelingFee = 0;
                if ($handling_fee != '' && $handling_fee != 0) {
                    if (strrchr($handling_fee, "%")) {

                        $prcnt = (float)$handling_fee;
                        $handelingFee = (float)$price / 100 * $prcnt;
                    } else {
                        $handelingFee = (float)$handling_fee;
                    }
                }

                $handelingFee = $this->smooth_round($handelingFee);

                $price = (float)$price + $handelingFee;
                return $price;
            }

            /**
             *
             * @param float type $val
             * @param int type $min
             * @param int type $max
             * @return float type
             */
            function smooth_round($val, $min = 2, $max = 4)
            {
                $result = round($val, $min);

                if ($result == 0 && $min < $max) {
                    return $this->smooth_round($val, ++$min, $max);
                } else {
                    return $result;
                }
            }

            /**
             * sort array
             * @param array type $rate
             * @return array type
             */
            public function sort_asec_order_arr($rate)
            {
                $price_sorted_key = array();
                foreach ($rate as $key => $cost_carrier) {
                    $price_sorted_key[$key] = (isset($cost_carrier['cost'])) ? $cost_carrier['cost'] : 0;
                }
                array_multisort($price_sorted_key, SORT_ASC, $rate);

                return $rate;
            }

            /**
             * filter label new update
             * @param type $label_sufex
             * @return string
             */
            public function filter_from_label_sufex($label_sufex)
            {
                $append_label = "";
                $rad_status = true;
                $all_plugins = apply_filters('active_plugins', get_option('active_plugins'));
                if (stripos(implode($all_plugins), 'residential-address-detection.php') || is_plugin_active_for_network('residential-address-detection/residential-address-detection.php')) {
                    if(get_option('suspend_automatic_detection_of_residential_addresses') != 'yes') {
                        $rad_status = get_option('residential_delivery_options_disclosure_types_to') != 'not_show_r_checkout';
                    }
                }
                switch (TRUE) {
                    case(count($label_sufex) == 1):
                        (in_array('L', $label_sufex)) ? $append_label = " with lift gate delivery " : "";
                        (in_array('R', $label_sufex) && $rad_status == true) ? $append_label = " with residential delivery " : "";
                        (in_array('LA', $label_sufex)) ? $append_label = " with limited access delivery " : "";
                        break;
                    case(count($label_sufex) > 1):
                        (in_array('L', $label_sufex)) ? $append_label = " with lift gate delivery " : "";
                        (in_array('LA', $label_sufex)) ? $append_label .= (strlen($append_label) > 0) ? " and limited access delivery " : " with limited access delivery " : "";
                        (in_array('R', $label_sufex) && $rad_status == true) ? $append_label .= (strlen($append_label) > 0) ? " and residential delivery " : " with residential delivery " : "";
                        break;
                }

                return $append_label;
            }

            /**
             * Append label in quote
             * @param array type $rate
             * @return string type
             */
            public function set_label_in_quote($rate)
            {
                $rate_label = "";
                $label_sufex = (isset($rate['label_sufex']) && (!empty($rate['label_sufex']))) ? array_unique($rate['label_sufex']) : array();
                $rate_label = (isset($rate['label'])) ? $rate['label'] : "Freight";
                $rate_label .= $this->filter_from_label_sufex($label_sufex);
                $rate_label .= (isset($rate['hat_append_label'])) ? $rate['hat_append_label'] : "";
                $rate_label .= (isset($rate['_hat_append_label'])) ? $rate['_hat_append_label'] : "";
                /*$rate_label .= ($this->quote_settings['transit_time'] == "yes" && isset($rate['transit_time'])) ? ' ( Estimated transit time of ' . $rate['transit_time'] . ' business days. )' : "";*/
                $delivery_estimate_rnl = isset($this->quote_settings['delivery_estimates']) ? $this->quote_settings['delivery_estimates'] : '';
                $shipment_type = isset($this->quote_settings['shipment']) && !empty($this->quote_settings['shipment']) ? $this->quote_settings['shipment'] : '';
                if (isset($this->quote_settings['delivery_estimates']) && !empty($this->quote_settings['delivery_estimates'])
                    && $this->quote_settings['delivery_estimates'] != 'dont_show_estimates' && $shipment_type != 'multi_shipment') {
                    if ($this->quote_settings['delivery_estimates'] == 'delivery_date') {
                        isset($rate['delivery_time_stamp']) && is_string($rate['delivery_time_stamp']) && strlen($rate['delivery_time_stamp']) > 0 ? $rate_label .= ' (Expected delivery by ' . date('m-d-Y', strtotime($rate['delivery_time_stamp'])) . ')' : '';
                    } else if ($delivery_estimate_rnl == 'delivery_days') {
                        $correct_word = (isset($rate['delivery_estimates']) && $rate['delivery_estimates'] == 1) ? 'is' : 'are';
                        isset($rate['delivery_estimates']) && is_string($rate['delivery_estimates']) && strlen($rate['delivery_estimates']) > 0 ? $rate_label .= ' (Intransit days: ' . $rate['delivery_estimates'] . ')' : '';
                    }
                }
                return $rate_label;
            }

            /**
             * rates to add_rate woocommerce
             * @param array type $add_rate_arr
             */
            public function rnl_add_rate_arr($add_rate_arr)
            {
                if (isset($add_rate_arr) && (!empty($add_rate_arr)) && (is_array($add_rate_arr))) {

                    // Images for FDO
                    $image_urls = apply_filters('en_fdo_image_urls_merge', []);

                    add_filter('woocommerce_package_rates', array($this, 'en_sort_woocommerce_available_shipping_methods'), 10, 2);

                    // In-store pickup and local delivery
                    $instore_pickup_local_devlivery_action = apply_filters('rnl_quotes_quotes_plans_suscription_and_features', 'instore_pickup_local_devlivery');

                    foreach ($add_rate_arr as $key => $rate) {

                        $rate['label'] = $this->set_label_in_quote($rate);

                        if (isset($rate['meta_data'])) {
                            $rate['meta_data']['label_sufex'] = (isset($rate['label_sufex'])) ? json_encode($rate['label_sufex']) : array();
                        }

                        if (isset($this->minPrices[$rate['id']])) {
                            $rate['meta_data']['min_prices'] = json_encode($this->minPrices[$rate['id']]);
                        }

                        $rate['id'] = (isset($rate['id'])) ? $rate['id'] : '';

                        // Micro Warehouse
                        $en_check_action_warehouse_appliance = apply_filters('en_check_action_warehouse_appliance', FALSE);
                        if ($this->shipment_type == 'multiple' && $en_check_action_warehouse_appliance && !empty($this->minPrices)) {
                            $rate['meta_data']['min_quotes'] = $this->minPrices[$rate['id']];
                        }

                        if (isset($this->minPrices[$rate['id']])) {
                            $rate['meta_data']['min_prices'] = json_encode($this->minPrices[$rate['id']]);
                            $rate['meta_data']['en_fdo_meta_data']['data'] = array_values($this->en_fdo_meta_data[$rate['id']]);
                            (!empty($this->en_fdo_meta_data_third_party)) ? $rate['meta_data']['en_fdo_meta_data']['data'] = array_merge($rate['meta_data']['en_fdo_meta_data']['data'], $this->en_fdo_meta_data_third_party) : '';
                            $rate['meta_data']['en_fdo_meta_data']['shipment'] = 'multiple';
                            $rate['meta_data']['en_fdo_meta_data'] = wp_json_encode($rate['meta_data']['en_fdo_meta_data']);
                        } else {
                            $en_set_fdo_meta_data['data'] = [$rate['meta_data']['en_fdo_meta_data']];
                            $en_set_fdo_meta_data['shipment'] = 'sinlge';
                            $rate['meta_data']['en_fdo_meta_data'] = wp_json_encode($en_set_fdo_meta_data);
                        }

                        // Images for FDO
                        $rate['meta_data']['en_fdo_image_urls'] = wp_json_encode($image_urls);
                        $rate['id'] = isset($rate['id']) && is_string($rate['id']) ? $this->id . ':' .  $rate['id'] : '';

                        if ($this->web_service_inst->en_wd_origin_array['suppress_local_delivery'] == "1" && (!is_array($instore_pickup_local_devlivery_action)) && $this->shipment_type != "multiple") {
                            $rate = apply_filters('suppress_local_delivery', $rate, $this->web_service_inst->en_wd_origin_array, $this->package_plugin, $this->web_service_inst->InstorPickupLocalDelivery);

                            if (!empty($rate)) {
                                if ($rate['cost'] > 0) {
                                    $this->add_rate($rate);
                                    $this->woocommerce_package_rates = 1;
                                    $add_rate_arr[$key] = $rate;
                                }
                            }
                        } else {

                            if ($rate['cost'] > 0) {
                                $this->add_rate($rate);
                                $add_rate_arr[$key] = $rate;
                            }
                        }
                    }
                }

                return $add_rate_arr;
            }

            /**
             * Check is free shipping or not
             * @param $coupon
             * @return string
             */
            function rnl_shipping_coupon_rate($coupon)
            {
                foreach ($coupon as $key => $value) {
                    if ($value->get_free_shipping() == 1) {
                        $rates = array(
                            'id' => $this->id . ':' . 'free',
                            'label' => 'Free Shipping',
                            'cost' => 0,
                            'plugin_name' => 'rnl',
                            'plugin_type' => 'ltl',
                            'owned_by' => 'eniture'
                        );
                        $this->add_rate($rates);
                        return 'y';
                    }
                }
                return 'n';
            }

            function en_sort_woocommerce_available_shipping_methods($rates, $package)
            {
                //  if there are no rates don't do anything
                if (!$rates) {
                    return [];
                }

                // Check the option to sort shipping methods by price on quote settings
                if (get_option('shipping_methods_do_not_sort_by_price') != 'yes') {

                    // get an array of prices
                    $prices = array();
                    foreach ($rates as $rate) {
                        $prices[] = $rate->cost;
                    }

                    // use the prices to sort the rates
                    array_multisort($prices, $rates);
                }

                // return the rates
                return $rates;
            }

            /**
             * Pickup delivery quote
             * @return array type
             */
            function pickup_delivery($label, $en_wd_origin_array, $total_distance)
            {
                $this->woocommerce_package_rates = 1;
                $this->instore_pickup_and_local_delivery = TRUE;

                $label = (isset($label) && (strlen($label) > 0)) ? $label : 'In-store pick up';
                // Origin terminal address
                $address = (isset($en_wd_origin_array['address'])) ? $en_wd_origin_array['address'] : '';
                $city = (isset($en_wd_origin_array['city'])) ? $en_wd_origin_array['city'] : '';
                $state = (isset($en_wd_origin_array['state'])) ? $en_wd_origin_array['state'] : '';
                $zip = (isset($en_wd_origin_array['zip'])) ? $en_wd_origin_array['zip'] : '';
                $phone_instore = (isset($en_wd_origin_array['phone_instore'])) ? $en_wd_origin_array['phone_instore'] : '';
                strlen($total_distance) > 0 ? $label .= ': Free | ' . str_replace("mi", "miles", $total_distance) . ' away' : '';
                strlen($address) > 0 ? $label .= ' | ' . $address : '';
                strlen($city) > 0 ? $label .= ', ' . $city : '';
                strlen($state) > 0 ? $label .= ' ' . $state : '';
                strlen($zip) > 0 ? $label .= ' ' . $zip : '';
                strlen($phone_instore) > 0 ? $label .= ' | ' . $phone_instore : '';

                $pickup_delivery = array(
                    'id' => $this->id . ':' . 'in-store-pick-up',
                    'cost' => 0,
                    'label' => $label,
                    'plugin_name' => 'rnl',
                    'plugin_type' => 'ltl',
                    'owned_by' => 'eniture'
                );

                add_filter('woocommerce_package_rates', array($this, 'en_sort_woocommerce_available_shipping_methods'), 10, 2);
                $this->add_rate($pickup_delivery);
            }

            /**
             * Local delivery quote
             * @param string type $cost
             * @return array type
             */
            function local_delivery($cost, $label, $en_wd_origin_array)
            {
                $this->woocommerce_package_rates = 1;
                $this->instore_pickup_and_local_delivery = TRUE;
                $label = (isset($label) && (strlen($label) > 0)) ? $label : 'Local Delivery';

                $local_delivery = array(
                    'id' => $this->id . ':' . 'local-delivery',
                    'cost' => $cost,
                    'label' => $label,
                    'plugin_name' => 'rnl',
                    'plugin_type' => 'ltl',
                    'owned_by' => 'eniture'
                );

                add_filter('woocommerce_package_rates', array($this, 'en_sort_woocommerce_available_shipping_methods'), 10, 2);
                $this->add_rate($local_delivery);
            }

            function get_single_formatted_quotes($quotes)
            {
                $guaranteed_quotes = !empty($quotes['guaranteed_services_quotes']) ? $quotes['guaranteed_services_quotes'] : [];
                unset($quotes['guaranteed_services_quotes']);
                $combined_quotes = [];
                $combined_quotes[] = $quotes;
                $combined_quotes = array_merge($combined_quotes, array_values($guaranteed_quotes));
                $rates = [];

                foreach ($combined_quotes as $quote) {
                    if (isset($quote['hold_at_terminal_quotes'])) {
                        $rates[] = $quote['hold_at_terminal_quotes'];
                        unset($quote['hold_at_terminal_quotes']);
                    }
    
                    $simple_quotes = (isset($quote['simple_quotes'])) ? $quote['simple_quotes'] : [];
                    $limited_access_quotes = isset($quote['limited_access_quotes']) ? $quote['limited_access_quotes'] : [];
                    unset($quote['limited_access_quotes']);
                    $lfg_quotes = $this->remove_array($quote, 'simple_quotes');
                    $rates[] = $lfg_quotes;
                    if (!empty($limited_access_quotes)) $rates[] = $limited_access_quotes;
                    
                    $en_accessorial_excluded = apply_filters('en_rnl_ltl_accessorial_excluded', []);
                    if (!empty($en_accessorial_excluded) && in_array('liftgateResidentialExcluded', $en_accessorial_excluded)) {
                        $lfg_quotes = [];
                    }
                    
                    // check for both lift gate and limited access quotes
                    if (!empty($simple_quotes) && !empty($lfg_quotes) && !empty($limited_access_quotes)) {
                        $lfg_and_limited_quotes = json_decode(json_encode($simple_quotes), true);
                        $lgFee = isset($lfg_and_limited_quotes['surcharges']['LIFT']) ? $lfg_and_limited_quotes['surcharges']['LIFT'] : 0;
                        $limitedFee = isset($lfg_and_limited_quotes['surcharges']['LA']) ? $lfg_and_limited_quotes['surcharges']['LA'] : 0;
                        $resiFee = isset($lfg_and_limited_quotes['surcharges']['RC']) ? $lfg_and_limited_quotes['surcharges']['RC'] : 0;
    
                        $lfg_and_limited_quotes['id'] .= 'WLLA';
                        $lfg_and_limited_quotes['cost'] += floatval($lgFee) + floatval($limitedFee);
                        $lfg_and_limited_quotes['cost'] -= $resiFee;
                        $lfg_and_limited_quotes['label_sfx_arr'] = $lfg_and_limited_quotes['label_sufex'] = ['L', 'LA'];
    
                        if (isset($lfg_and_limited_quotes['meta_data']['min_quotes'])) {
                            unset($lfg_and_limited_quotes['meta_data']['min_quotes']);
                        }
    
                        // FDO
                        if (isset($lfg_and_limited_quotes['meta_data']['en_fdo_meta_data']['accessorials'])) {
                            $lfg_and_limited_quotes['meta_data']['en_fdo_meta_data']['accessorials']['liftgate'] = true;
                            $lfg_and_limited_quotes['meta_data']['en_fdo_meta_data']['accessorials']['limitedaccess'] = true;
                            $lfg_and_limited_quotes['meta_data']['en_fdo_meta_data']['accessorials']['residential'] = false;
                        }
    
                        if (isset($lfg_and_limited_quotes['meta_data']['en_fdo_meta_data']['rate']['cost'])) {
                            $lfg_and_limited_quotes['meta_data']['en_fdo_meta_data']['rate']['cost'] = $lfg_and_limited_quotes['cost'];
                            $lfg_and_limited_quotes['meta_data']['en_fdo_meta_data']['rate']['label_sfx_arr'] = $lfg_and_limited_quotes['label_sufex'];
                        }
    
                        $rates[] = $lfg_and_limited_quotes;                        
                    }
    
                    // Offer lift gate delivery as an option is enabled
                    if (isset($this->quote_settings['liftgate_delivery_option']) &&
                        ($this->quote_settings['liftgate_delivery_option'] == "yes") &&
                        (!empty($simple_quotes))
                    ) {
                        $rates[] = $simple_quotes;
                    }
                }

                return $rates;
            }

            function get_multi_formatted_quotes($quotes, $smpkgCost)
            {
                // Multiple Shipment
                $multi_cost = 0;
                $s_multi_cost = 0;
                $access_multi_cost = 0;
                $hold_at_terminal_fee = 0;
                $_label = "";
                $access_label = [];
                $access_append_label = "";
                $handling_fee = $this->quote_settings['handling_fee'];
                $shipment_numbers = 0;
                $multi_rates = [];

                foreach ($quotes as $ship_key => $q) {
                    if (!empty($q)) {
                        $guaranteed_quotes = !empty($q['guaranteed_services_quotes']) ? $q['guaranteed_services_quotes'] : [];
                        unset($q['guaranteed_services_quotes']);
                        $combined_quotes = [];
                        $combined_quotes[] = $q;
                        $combined_quotes = array_merge($combined_quotes, array_values($guaranteed_quotes));

                        $key = "LTL_" . $ship_key;

                        foreach ($combined_quotes as $cmb_key => $quote) {
                            $hold_at_terminal_fee = 0;
                            $unique_id = $key . "_" . $cmb_key;

                            // Hold At Terminal is enabled
                            if (isset($quote['hold_at_terminal_quotes'])) {
                                $hold_at_terminal_quotes = $quote['hold_at_terminal_quotes'];
                                $this->minPrices['RNL_HAT'][$key] = $hold_at_terminal_quotes;

                                // FDO
                                $this->en_fdo_meta_data['RNL_HAT'][$key] = (isset($hold_at_terminal_quotes['meta_data']['en_fdo_meta_data'])) ? $hold_at_terminal_quotes['meta_data']['en_fdo_meta_data'] : [];

                                $hold_at_terminal_fee += $hold_at_terminal_quotes['cost'];
                                unset($quote['hold_at_terminal_quotes']);
                                $append_hat_label = (isset($hold_at_terminal_quotes['hat_append_label'])) ? $hold_at_terminal_quotes['hat_append_label'] : "";
                                $append_hat_label = (isset($hold_at_terminal_quotes['_hat_append_label']) && (strlen($append_hat_label) > 0)) ? $append_hat_label . $hold_at_terminal_quotes['_hat_append_label'] : $append_hat_label;
                                $hat_label = array();
                            }

                            $simple_quotes = (isset($quote['simple_quotes'])) ? $quote['simple_quotes'] : array();
                            $limited_access_quotes = isset($quote['limited_access_quotes']) ? $quote['limited_access_quotes'] : array();
                            unset($quote['limited_access_quotes']);
                            $quote = $this->remove_array($quote, 'simple_quotes');
                            $rates = (is_array($quote) && (!empty($quote))) ? $quote : array();

                            $this->minPrices['RNL_LIFT' . $unique_id][$key] = $rates;

                            // Offer limited access delivery as an option is enabled
                            if (!empty($limited_access_quotes)) {
                                $this->minPrices['RNL_ACCESS' . $unique_id][$key] = $limited_access_quotes;
                                $this->en_fdo_meta_data['RNL_ACCESS' . $unique_id][$key] = (isset($limited_access_quotes['meta_data']['en_fdo_meta_data'])) ? $limited_access_quotes['meta_data']['en_fdo_meta_data'] : [];

                                $access_cost = (isset($limited_access_quotes['cost'])) ? $limited_access_quotes['cost'] : 0;
                                $access_label = (isset($limited_access_quotes['label_sufex'])) ? $limited_access_quotes['label_sufex'] : array();
                                $access_append_label = (isset($limited_access_quotes['append_label'])) ? $limited_access_quotes['append_label'] : "";

                                // $access_multi_cost += $this->add_handling_fee($access_cost, $handling_fee);
                                $access_multi_cost = $this->add_handling_fee($access_cost, $handling_fee);
                                $this->minPrices['RNL_ACCESS' . $unique_id][$key]['cost'] = $this->add_handling_fee($access_cost, $handling_fee);
                            }

                            // FDO
                            $this->en_fdo_meta_data['RNL_LIFT' . $unique_id][$key] = (isset($rates['meta_data']['en_fdo_meta_data'])) ? $rates['meta_data']['en_fdo_meta_data'] : [];

                            $_cost = (isset($rates['cost'])) ? $rates['cost'] : 0;
                            $_label = (isset($rates['label_sufex'])) ? $rates['label_sufex'] : array();
                            $append_label = (isset($rates['append_label'])) ? $rates['append_label'] : "";
                            $handling_fee = (isset($rates['markup']) && (strlen($rates['markup']) > 0)) ? $rates['markup'] : $handling_fee;

                            // Offer lift gate delivery as an option is enabled
                            if (isset($this->quote_settings['liftgate_delivery_option']) &&
                                ($this->quote_settings['liftgate_delivery_option'] == "yes") &&
                                (!empty($simple_quotes))) {
                                $s_rates = $simple_quotes;
                                $this->minPrices['RNL_NOTLIFT' . $unique_id][$key] = $s_rates;

                                // FDO
                                $this->en_fdo_meta_data['RNL_NOTLIFT' . $unique_id][$key] = (isset($s_rates['meta_data']['en_fdo_meta_data'])) ? $s_rates['meta_data']['en_fdo_meta_data'] : [];

                                $s_cost = (isset($s_rates['cost'])) ? $s_rates['cost'] : 0;
                                $s_label = (isset($s_rates['label_sufex'])) ? $s_rates['label_sufex'] : array();
                                $s_append_label = (isset($s_rates['append_label'])) ? $s_rates['append_label'] : "";
                                // $s_multi_cost += $this->add_handling_fee($s_cost, $handling_fee);
                                $s_multi_cost = $this->add_handling_fee($s_cost, $handling_fee);
                                $this->minPrices['RNL_NOTLIFT' . $unique_id][$key]['cost'] = $this->add_handling_fee($s_cost, $handling_fee);
                            }

                            // $multi_cost += $this->add_handling_fee($_cost, $handling_fee);
                            $multi_cost = $this->add_handling_fee($_cost, $handling_fee);
                            $this->minPrices['RNL_LIFT' . $unique_id][$key]['cost'] = $this->add_handling_fee($_cost, $handling_fee);

                            $shipment_numbers++;

                            // Create Array to add_rate Woocommerce
                            
                            // Standard multi-shipment quotes
                            ($s_multi_cost > 0) ? $multi_rates[$ship_key][] = $this->arrange_multiship_freight(($s_multi_cost + $smpkgCost), 'RNL_NOTLIFT' . $unique_id, $s_label, $s_append_label) : "";
                            
                            // Excluded accessorials
                            $en_accessorial_excluded = apply_filters('en_rnl_ltl_accessorial_excluded', []);
                            if ($s_multi_cost > 0 && !empty($en_accessorial_excluded) && in_array('liftgateResidentialExcluded', $en_accessorial_excluded)) {
                                $multi_cost = 0;
                            }

                            // Lift gate delivery multi-shipment quotes
                            ($multi_cost > 0 || $smpkgCost > 0) ? $multi_rates[$ship_key][] = 
                            $this->arrange_multiship_freight(($multi_cost + $smpkgCost), 'RNL_LIFT' . $unique_id, $_label, $append_label) : "";
                            // Limited access delivery multi-shipment quotes
                            ($access_multi_cost > 0 || $smpkgCost > 0) ? $multi_rates[$ship_key][] = $this->arrange_multiship_freight(($access_multi_cost + $smpkgCost), 'RNL_ACCESS' . $unique_id, $access_label, $access_append_label) : "";
                            // HAT multi-shipment quotes
                            ($hold_at_terminal_fee > 0) ? $multi_rates[$ship_key][] = $this->arrange_multiship_freight(($hold_at_terminal_fee + $smpkgCost), 'RNL_HAT', $hat_label, $append_hat_label) : "";

                            // combined rates for lift gate with limited access delivery
                            if ($multi_cost > 0 && $access_multi_cost > 0) {
                                $combined_multi_cost = 0;
                                $combined_label = ['L', 'LA'];
                                $combined_append_label = $append_label . $access_append_label;
                                
                                if (is_array($this->minPrices['RNL_NOTLIFT' . $unique_id]) && !empty($this->minPrices['RNL_NOTLIFT' . $unique_id])) {
                                    $simple_rates = json_decode(json_encode($this->minPrices['RNL_NOTLIFT' . $unique_id]), true);

                                    foreach ($simple_rates as $key => $quote) {
                                        $shipment_cost = isset($quote['cost']) ? $quote['cost'] : 0;

                                        $resi_fee = isset($quote['surcharges']['RC']) ? $quote['surcharges']['RC'] : 0;
                                        $lg_fee = isset($quote['surcharges']['LIFT']) ? $quote['surcharges']['LIFT'] : 0;
                                        $la_fee = isset($quote['surcharges']['LA']) ? $quote['surcharges']['LA'] : 0;

                                        $shipment_cost += (floatval($lg_fee) +  floatval($la_fee));
                                        $shipment_cost -= $resi_fee;
                                        $quote['cost'] = $shipment_cost;
                                        $combined_multi_cost += $shipment_cost;

                                        $quote['label_sfx_arr'] = $lfg_and_limited_quotes['label_sufex'] = ['L', 'LA'];

                                        if (isset($quote['meta_data']['en_fdo_meta_data'])) {
                                            $quote['meta_data']['en_fdo_meta_data']['accessorials']['liftgate'] = true;
                                            $quote['meta_data']['en_fdo_meta_data']['accessorials']['limitedaccess'] = true;
                                            $quote['meta_data']['en_fdo_meta_data']['accessorials']['residential'] = false;

                                            $quote['meta_data']['en_fdo_meta_data']['rate']['cost'] = $shipment_cost;
                                        }

                                        $this->minPrices['RNL_LIFTACCESS' . $unique_id][$key] = $quote;
                                        $this->en_fdo_meta_data['RNL_LIFTACCESS' . $unique_id][$key] = isset($quote['meta_data']['en_fdo_meta_data']) ? $quote['meta_data']['en_fdo_meta_data'] : [];
                                    }
                                }

                                ($combined_multi_cost > 0 || $smpkgCost) ? $multi_rates[$ship_key][] = $this->arrange_multiship_freight(($combined_multi_cost + $smpkgCost), 'RNL_LIFTACCESS' . $unique_id, $combined_label, $combined_append_label) : "";
                            }
                        }
                    }
                }

                $this->quote_settings['shipment_numbers'] = $shipment_numbers;

                $multi_hat_rates = [];
                foreach ($multi_rates as $ship_key => $ship_rates) {
                    foreach ($ship_rates as $key => $rate) {
                        if ($rate['id'] == 'RNL_HAT' || strpos($rate['id'], 'HAT') !== false) {
                            $multi_hat_rates[] = $rate;
                            unset($multi_rates[$ship_key][$key]);
                        }
                    }
                }
                
                $hat_cheapest_rate = [];
                if (!empty($multi_hat_rates)) {
                    foreach ($multi_hat_rates as $key => $chp_rate) {
                        empty($hat_cheapest_rate) ? $hat_cheapest_rate = $chp_rate: $hat_cheapest_rate['cost'] += floatval($chp_rate['cost']);
                    }
                }

                // Find the cheapest rate for each shipments
                $cheapest_rates = [];
                foreach ($multi_rates as $key => $rate) {
                    array_multisort(array_column($rate, 'cost'), SORT_ASC, $rate);
                    $cheapest_rates[$key] = $rate[0];
                }

                $cheapest_rate = [];
                $multi_rate_key = '';
                foreach ($cheapest_rates as $key => $chp_rate) {
                    if (empty($cheapest_rate)) {
                        $cheapest_rate = $chp_rate;
                        $multi_rate_key = $chp_rate['id'];
                    } else {
                        $cheapest_rate['cost'] += floatval($chp_rate['cost']);
                        $this->minPrices[$multi_rate_key]['LTL_' . $key] = reset(array_values($this->minPrices[$chp_rate['id']]));
                        $this->en_fdo_meta_data[$multi_rate_key]['LTL_' . $key] = reset(array_values($this->en_fdo_meta_data[$chp_rate['id']]));
                    }
                }

                $final_quotes = array_merge(array($cheapest_rate), array($hat_cheapest_rate));

                return $final_quotes;
            }

            /**
            * Adds backup rates in the shipping rates
            * @return void
            * */
            function rnl_backup_rates()
            {
                if (get_option('enable_backup_rates_rnl_ltl') != 'yes' || (get_option('rnl_ltl_backup_rates_carrier_fails_to_return_response') != 'yes' && get_option('rnl_ltl_backup_rates_carrier_returns_error') != 'yes')) return;

                $backup_rates_type = get_option('rnl_ltl_backup_rates_category');
                $backup_rates_cost = 0;

                if ($backup_rates_type == 'fixed_rate' && !empty(get_option('rnl_backup_rates_fixed_rate'))) {
                    $backup_rates_cost = get_option('rnl_backup_rates_fixed_rate');
                } elseif ($backup_rates_type == 'percentage_of_cart_price' && !empty(get_option('rnl_backup_rates_cart_price_percentage'))) {
                    $cart_price_percentage = floatval(str_replace('%', '', get_option('rnl_backup_rates_cart_price_percentage')));
                    $backup_rates_cost = ($cart_price_percentage * WC()->cart->get_subtotal()) / 100;
                } elseif ($backup_rates_type == 'function_of_weight' && !empty(get_option('rnl_backup_rates_weight_function'))) {
                    $cart_weight = wc_get_weight(WC()->cart->get_cart_contents_weight(), 'lbs');
                    $backup_rates_cost = get_option('rnl_backup_rates_weight_function') * $cart_weight;
                }

                if ($backup_rates_cost > 0) {
                    $backup_rates = array(
                        'id' => $this->id . ':' . 'backup_rates',
                        'label' => get_option('rnl_ltl_backup_rates_label'),
                        'cost' => $backup_rates_cost,
                        'plugin_name' => 'rnl',
                        'plugin_type' => 'ltl',
                        'owned_by' => 'eniture'
                    );

                    $this->add_rate($backup_rates);
                }
            }
        }

    }
}