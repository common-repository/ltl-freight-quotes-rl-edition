<?php

/**
 * R+L WooComerce Get R+L LTL Quotes Rate Class
 * @package     Woocommerce R+L Edition
 * @author      <https://eniture.com/>
 * @copyright   Copyright (c) 2017, Eniture
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get R+L LTL Quotes Rate Class
 */
class RNL_Freight_Get_Shipping_Quotes extends Rnl_Quotes_Liftgate_As_Option
{
    public $en_wd_origin_array;
    public $InstorPickupLocalDelivery;
    public $quote_settings;
    public $en_accessorial_excluded;

    function __construct()
    {
        $this->quote_settings = array();
    }

    /**
     * Create Shipping Package
     * @param $packages
     * @return array
     */
    function rnl_shipping_array($packages, $package_plugin = '')
    {
        // FDO
        $EnRLfreightFdo = new EnRLfreightFdo();
        $en_fdo_meta_data = array();

        $destinationAddressRnL = $this->destinationAddressRnL();
        $residential_detecion_flag = get_option("en_woo_addons_auto_residential_detecion_flag");
        $this->en_wd_origin_array = (isset($packages['origin'])) ? $packages['origin'] : array();

        // Cuttoff Time
        $shipment_week_days = "";
        $order_cut_off_time = "";
        $shipment_off_set_days = "";
        $modify_shipment_date_time = "";
        $store_date_time = "";
        $rnl_delivery_estimates = get_option('rnl_delivery_estimates');
        $shipment_week_days = $this->rnl_shipment_week_days();
        if ($rnl_delivery_estimates == 'delivery_days' || $rnl_delivery_estimates == 'delivery_date') {
            $order_cut_off_time = $this->quote_settings['orderCutoffTime'];
            $shipment_off_set_days = $this->quote_settings['shipmentOffsetDays'];
            $modify_shipment_date_time = ($order_cut_off_time != '' || $shipment_off_set_days != '' || (is_array($shipment_week_days) && count($shipment_week_days) > 0)) ? 1 : 0;
            $store_date_time = $today = date('Y-m-d H:i:s', current_time('timestamp'));
        }
        $lineItem = array();
        $product_name = array();
        $hazardous = array();

        $nested_plan = apply_filters('rnl_quotes_quotes_plans_suscription_and_features', 'nested_material');
        $nestingPercentage = $nestedDimension = $nestedItems = $stakingProperty = [];
        $doNesting = false;
        $product_markup_shipment = 0;

        foreach ($packages['items'] as $item) {
            //pallet per product
            $ship_as_own_pallet = isset($item['ship_as_own_pallet']) && $item['ship_as_own_pallet'] == 'yes' ? 1 : 0;
            $vertical_rotation_for_pallet = isset($item['vertical_rotation_for_pallet']) && $item['vertical_rotation_for_pallet'] == 'yes' ? 1 : 0;
            $rnl_counter = (isset($item['variantId']) && $item['variantId'] > 0) ? $item['variantId'] : $item['productId'];

            $lineItem[$rnl_counter] = array(
                'lineItemClass' => $item['productClass'],
                'lineItemWeight' => $item['productWeight'],
                'lineItemHeight' => $item['productHeight'],
                'lineItemWidth' => $item['productWidth'],
                'lineItemLength' => $item['productLength'],
                'lineItemDescription' => $item['productName'],
                'piecesOfLineItem' => $item['productQty'],

                // Nesting
                'nestingPercentage' => $item['nestedPercentage'],
                'nestingDimension' => $item['nestedDimension'],
                'nestedLimit' => $item['nestedItems'],
                'nestedStackProperty' => $item['stakingProperty'],

                // Shippable handling units
                'lineItemPalletFlag' => $item['lineItemPalletFlag'],
                'lineItemPackageType' => $item['lineItemPackageType'],

                // Standard Packaging
                'shipPalletAlone' => $ship_as_own_pallet,
                'vertical_rotation' => $vertical_rotation_for_pallet
            );

            $lineItem[$rnl_counter] = apply_filters('en_fdo_carrier_service', $lineItem[$rnl_counter], $item);

            $product_name[] = $item['product_name'];

            // Nesting
            isset($item['nestedMaterial']) && !empty($item['nestedMaterial']) &&
            $item['nestedMaterial'] == 'yes' && !is_array($nested_plan) ? $doNesting = 1 : "";
//            $rnl_counter++;

            if(!empty($item['markup']) && is_numeric($item['markup'])){
                $product_markup_shipment += $item['markup'];
            }
        }

        $domain = rnl_quotes_get_domain();

        // FDO
        $en_fdo_meta_data = $EnRLfreightFdo->en_cart_package($packages);

        // Version numbers
        $plugin_versions = $this->en_version_numbers();

        $post_data = array(
            // Version numbers
            'plugin_version' => $plugin_versions["en_current_plugin_version"],
            'wordpress_version' => get_bloginfo('version'),
            'woocommerce_version' => $plugin_versions["woocommerce_plugin_version"],

            'licence_key' => get_option('wc_settings_rnl_plugin_licence_key'),
            'sever_name' => $this->rnl_parse_url($domain),
            'requestKey' => md5(microtime() . rand()),
            'carrierName' => 'rnl',
            'carrier_mode' => 'pro',
            'UserName' => get_option('wc_settings_rnl_username'),
            'Password' => get_option('wc_settings_rnl_password'),
            'APIKey' => get_option('wc_settings_rnl_api_key'),
            'ApiVersion' => '2.0',
            'suspend_residential' => get_option('suspend_automatic_detection_of_residential_addresses'),
            'residential_detecion_flag' => $residential_detecion_flag,
            'plateform' => 'WordPress',
            'QuoteType' => 'Domestic',
            'CODAmount' => '0',
            'senderCity' => $packages['origin']['city'],
            'senderState' => $packages['origin']['state'],
            'senderZip' => $packages['origin']['zip'],
            'senderCountryCode' => $this->rnl_get_country_code($packages['origin']['country']),
            'receiverCity' => $destinationAddressRnL['city'],
            'receiverState' => $destinationAddressRnL['state'],
            'receiverZip' => $destinationAddressRnL['zip'],
            'receiverCountryCode' => $this->rnl_get_country_code($destinationAddressRnL['country']),
            'liftgateDelivery' => (get_option('wc_settings_rnl_liftgate') == 'yes') ? "Y" : "N",
            'residentialDelivery' => (get_option('wc_settings_rnl_residential') == 'yes') ? "Y" : "N",
            'collectOnDeliveryAmount' => '0',
            'handlingUnitWeight' => get_option('rnl_freight_handling_weight'),
            'maxWeightPerHandlingUnit' => get_option('rnl_freight_maximum_handling_weight'),
            'commdityDetails' => array(
                'handlingUnitDetails' => $lineItem
            ),
            'sender_origin' => $packages['origin']['location'] . ": " . $packages['origin']['city'] . ", " . $packages['origin']['state'] . " " . $packages['origin']['zip'],
            'product_name' => $product_name,
            'overDimensionPcs' => '0',
            'DeclaredValue' => '0',
            'en_fdo_meta_data' => $en_fdo_meta_data,
            // Nesting
            'doNesting' => $doNesting,
            // Cuttoff Time
            'modifyShipmentDateTime' => $modify_shipment_date_time,
            'OrderCutoffTime' => $order_cut_off_time,
            'shipmentOffsetDays' => $shipment_off_set_days,
            'storeDateTime' => $store_date_time,
            'shipmentWeekDays' => $shipment_week_days,
            'palletCode' => get_option('en_rnl_pallets_dropdown') ?? '',
            'palletWeight' => get_option('en_rnl_max_weight_per_pallet') ?? '',
            'origin_markup' => (isset($packages['origin']['origin_markup'])) ? $packages['origin']['origin_markup'] : 0,
            'product_level_markup' => $product_markup_shipment
        );

        // Liftgate exclude limit based on the liftgate weight restrictions shipping rule
        $shipping_rules_obj = new EnRnlShippingRulesAjaxReq();
        $liftGateExcludeLimit = $shipping_rules_obj->get_liftgate_exclude_limit();
        if (!empty($liftGateExcludeLimit) && $liftGateExcludeLimit > 0) {
            $post_data['liftgateExcludeLimit'] = $liftGateExcludeLimit;
        }

        // Micro Warehouse
        $post_data = apply_filters('en_request_handler', $post_data, 'rnl');

        if ($this->quote_settings['liftgate_delivery_option'] == "yes") {
            $post_data['liftgateDelivery'] = 'Y';
        }

        if (get_option('rnl_quotes_store_type') == "1") {
            // Hazardous Material
            $hazardous_material = apply_filters('rnl_quotes_quotes_plans_suscription_and_features', 'hazardous_material');
            if (!is_array($hazardous_material)) {
                $post_data['hazmat'] = ($packages['hazardousMaterial'] == 'yes') ? 'Y' : 'N';
                ($packages['hazardousMaterial'] == 'yes') ? $hazardous[] = 'H' : '';
            }

            // FDO
            $post_data['en_fdo_meta_data'] = array_merge($post_data['en_fdo_meta_data'], $EnRLfreightFdo->en_package_hazardous($packages, $en_fdo_meta_data));

        } else {
            $post_data['hazmat'] = ($packages['hazardousMaterial'] == 'yes') ? 'Y' : 'N';
            ($packages['hazardousMaterial'] == 'yes') ? $hazardous[] = 'H' : '';
        }

//      Hold At Terminal
        $hold_at_terminal = apply_filters('rnl_quotes_quotes_plans_suscription_and_features', 'rnl_hold_at_terminal');

        if (!is_array($hold_at_terminal)) {

            (isset($this->quote_settings['HAT_status']) && ($this->quote_settings['HAT_status'] == 'yes')) ? $post_data['holdAtTerminal'] = '1' : '';
        }

        $post_data = $this->rnl_quotes_update_carrier_service($post_data);
        $post_data = apply_filters("en_woo_addons_carrier_service_quotes_request", $post_data, en_woo_plugin_rnl_quotes);

//      In-store pickup and local delivery
        $instore_pickup_local_devlivery_action = apply_filters('rnl_quotes_quotes_plans_suscription_and_features', 'instore_pickup_local_devlivery');

        if (!is_array($instore_pickup_local_devlivery_action)) {
            $post_data = apply_filters('en_wd_standard_plans_rl', $post_data, $post_data['receiverZip'], $this->en_wd_origin_array, $package_plugin);
        }

        $post_data['hazardous'] = $hazardous;

        // Standard Packaging
        // Configure standard plugin with pallet packaging addon
        $post_data = apply_filters('en_pallet_identify', $post_data);

        // Eniture debug mood
        do_action("eniture_debug_mood", "R+L Plugin Features", get_option('eniture_plugin_14'));
        do_action("eniture_debug_mood", "Quotes Request (R+L)", $post_data);

        // Error management
        $post_data = $this->applyErrorManagement($post_data);

        return $post_data;
    }

    /**
     * @return shipment days of a week  - Cuttoff time
     */
    public function rnl_shipment_week_days()
    {
        $shipment_days_of_week = array();

        if (get_option('all_shipment_days_rnl') == 'yes') {
            return $shipment_days_of_week;
        }
        if (get_option('monday_shipment_day_rnl') == 'yes') {
            $shipment_days_of_week[] = 1;
        }
        if (get_option('tuesday_shipment_day_rnl') == 'yes') {
            $shipment_days_of_week[] = 2;
        }
        if (get_option('wednesday_shipment_day_rnl') == 'yes') {
            $shipment_days_of_week[] = 3;
        }
        if (get_option('thursday_shipment_day_rnl') == 'yes') {
            $shipment_days_of_week[] = 4;
        }
        if (get_option('friday_shipment_day_rnl') == 'yes') {
            $shipment_days_of_week[] = 5;
        }

        return $shipment_days_of_week;
    }

    /**
     * Return version numbers
     * @return int
     */
    function en_version_numbers()
    {
        if (!function_exists('get_plugins'))
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');

        $plugin_folder = get_plugins('/' . 'woocommerce');
        $plugin_file = 'woocommerce.php';
        $wc_plugin = (isset($plugin_folder[$plugin_file]['Version'])) ? $plugin_folder[$plugin_file]['Version'] : "";
        $get_plugin_data = get_plugin_data(RNL_MAIN_FILE);
        $plugin_version = (isset($get_plugin_data['Version'])) ? $get_plugin_data['Version'] : '';

        $versions = array(
            "woocommerce_plugin_version" => $wc_plugin,
            "en_current_plugin_version" => $plugin_version
        );

        return $versions;
    }

    /**
     * R+L Line Items
     * @param $packages
     * @return array
     */
    function rnl_get_line_items($packages)
    {
        $lineItem = array();
        foreach ($packages['items'] as $item) {
            $lineItem[] = array(
                'lineItemClass' => $item['productClass'],
                'lineItemWeight' => $item['productWeight'],
                'lineItemHeight' => $item['productHeight'],
                'lineItemWidth' => $item['productWidth'],
                'lineItemLength' => $item['productLength'],
                'lineItemDescription' => $item['productName'],
                'piecesOfLineItem' => $item['productQty']
            );
        }
        return $lineItem;
    }

    function destinationAddressRnL()
    {
        $en_order_accessories = apply_filters('en_order_accessories', []);
        if (isset($en_order_accessories) && !empty($en_order_accessories)) {
            return $en_order_accessories;
        }

        $rnl_woo_obj = new rnl_Woo_Update_Changes();
        $freight_zipcode = (strlen(WC()->customer->get_shipping_postcode()) > 0) ? WC()->customer->get_shipping_postcode() : $rnl_woo_obj->rnl_postcode();
        $freight_state = (strlen(WC()->customer->get_shipping_state()) > 0) ? WC()->customer->get_shipping_state() : $rnl_woo_obj->rnl_getState();
        $freight_country = (strlen(WC()->customer->get_shipping_country()) > 0) ? WC()->customer->get_shipping_country() : $rnl_woo_obj->rnl_getCountry();
        $freight_city = (strlen(WC()->customer->get_shipping_city()) > 0) ? WC()->customer->get_shipping_city() : $rnl_woo_obj->rnl_getCity();
        return array(
            'city' => $freight_city,
            'state' => $freight_state,
            'zip' => $freight_zipcode,
            'country' => $freight_country
        );
    }

    /**
     * Get Country Code
     * @param $sCountryCode
     * @return string
     */
    function rnl_get_country_code($sCountryCode)
    {
        switch (trim($sCountryCode)) {
            case 'CN':
                $sCountryCode = "CAN";
                break;
            case 'CAN':
                $sCountryCode = "CAN";
                break;
            case 'CA':
                $sCountryCode = "CAN";
                break;
            case 'US':
                $sCountryCode = "USA";
                break;
            case 'USA':
                $sCountryCode = "USA";
                break;
        }
        return $sCountryCode;
    }

    /**
     * Get Nearest Address If Multiple Warehouses
     * @param $warehous_list
     * @param $receiverZipCode
     * @return Warehouse Address
     */
    function rnl_multi_warehouse($warehous_list, $receiverZipCode)
    {
        if (count($warehous_list) == 1) {
            $warehous_list = reset($warehous_list);
            return $this->rnl_origin_array($warehous_list);
        }

        $rnl_distance_request = new Get_rnl_quotes_distance();
        $accessLevel = "MultiDistance";
        $response_json = $rnl_distance_request->rnl_quotes_get_distance($warehous_list, $accessLevel, $this->destinationAddressRnL());
        $response_json = json_decode($response_json);

        return $this->rnl_origin_array($response_json->origin_with_min_dist);
    }

    /**
     * Create Origin Array
     * @param $origin
     * @return Warehouse Address Array
     */
    function rnl_origin_array($origin)
    {
        //      In-store pickup and local delivery
        if (has_filter("en_wd_origin_array_set_rl")) {
            return apply_filters("en_wd_origin_array_set_rl", $origin);
        }
        return array(
            'locationId' => $origin->id,
            'zip' => $origin->zip,
            'city' => $origin->city,
            'state' => $origin->state,
            'location' => $origin->location,
            'country' => $origin->country
        );
    }

    /**
     * Refine URL
     * @param $domain
     * @return Domain URL
     */
    function rnl_parse_url($domain)
    {
        $domain = trim($domain);
        $parsed = parse_url($domain);
        if (empty($parsed['scheme'])) {
            $domain = 'http://' . ltrim($domain, '/');
        }
        $parse = parse_url($domain);
        $refinded_domain_name = $parse['host'];
        $domain_array = explode('.', $refinded_domain_name);
        if (in_array('www', $domain_array)) {
            $key = array_search('www', $domain_array);
            unset($domain_array[$key]);
            if(phpversion() < 8) {
                $refinded_domain_name = implode($domain_array, '.'); 
            }else {
                $refinded_domain_name = implode('.', $domain_array);
            }
        }
        return $refinded_domain_name;
    }

    /**
     * Curl Request To Get Quotes
     * @param $request_data
     * @return json/array
     */
    function rnl_get_web_quotes($request_data, $rnl_package = [], $loc_id = '')
    {
        // Check response from session
        $srequest_data = $request_data;
        $srequest_data['requestKey'] = "";
        $currentData = md5(json_encode($srequest_data));
        $requestFromSession = WC()->session->get('previousRequestData');
        $requestFromSession = ((is_array($requestFromSession)) && (!empty($requestFromSession))) ? $requestFromSession : array();

        if (isset($requestFromSession[$currentData]) && (!empty($requestFromSession[$currentData]))) {
            $this->InstorPickupLocalDelivery = (isset(json_decode($requestFromSession[$currentData])->InstorPickupLocalDelivery) ? json_decode($requestFromSession[$currentData])->InstorPickupLocalDelivery : NULL);
//          Eniture debug mood
            do_action("eniture_debug_mood", "Build Query Session (R+L)", http_build_query($request_data));
            do_action("eniture_debug_mood", "Quotes Response Session (R+L)", json_decode($requestFromSession[$currentData]));

            return $this->parse_rnl_output($requestFromSession[$currentData], $request_data, $rnl_package, $loc_id);
        }

        if (is_array($request_data) && count($request_data) > 0) {
            $rnl_curl_obj = new RNL_Curl_Request();
            $output = $rnl_curl_obj->rnl_get_curl_response(RNL_FREIGHT_DOMAIN_HITTING_URL . '/index.php', $request_data);

//          set response in session 
            $response = json_decode($output);
            if (isset($response->q) &&
                (empty($response->error))) {

                $aServicesResult = ((!is_array($response->q->soapBody->GetRateQuoteResponse->GetRateQuoteResult->Result->ServiceLevels->ServiceLevel)) ? array($response->q->soapBody->GetRateQuoteResponse->GetRateQuoteResult->Result->ServiceLevels->ServiceLevel) : $response->q->soapBody->GetRateQuoteResponse->GetRateQuoteResult->Result->ServiceLevels->ServiceLevel);

                if (isset($response->autoResidentialSubscriptionExpired) &&
                    ($response->autoResidentialSubscriptionExpired == 1)) {
                    $flag_api_response = "no";
                    $srequest_data['residential_detecion_flag'] = $flag_api_response;
                    $currentData = md5(json_encode($srequest_data));
                }

                if (is_array($aServicesResult) && count($aServicesResult) > 0) {

                    $requestFromSession[$currentData] = $output;
                    WC()->session->set('previousRequestData', $requestFromSession);
                }
            }

            $response = json_decode($output);
            $this->InstorPickupLocalDelivery = (isset($response->InstorPickupLocalDelivery) ? $response->InstorPickupLocalDelivery : NULL);

//          Eniture debug mood
            do_action("eniture_debug_mood", "Quotes Response (R+L)", json_decode($output));

            return $this->parse_rnl_output($output, $request_data, $rnl_package, $loc_id);
        }
    }

    /**
     * Get Shipping Array For Single Shipment
     * @param $output
     * @return string/array Single Quote Array
     */
    function parse_rnl_output($output, $request_data, $rnl_package, $loc_id)
    {
        $hat_quotes = $quotes = $accessorials = array();
        $result = json_decode($output);
        
        // API timeout or empty response
        if (isset($result->backupRate)) {
            return $result;
        }
        
        // Apply override rates shipping rules
        $odfl_shipping_rules = new EnRnlShippingRulesAjaxReq();
        $odfl_shipping_rules->apply_shipping_rules($rnl_package, true, $result, $loc_id);

        $en_fdo_meta_data = (isset($request_data['en_fdo_meta_data'])) ? $request_data['en_fdo_meta_data'] : '';
        if (isset($result->debug)) {
            $en_fdo_meta_data['handling_unit_details'] = $result->debug;
        }

        // Cuttoff Time
        $delivery_estimates = $delivery_time_stamp = '';
        $service_level = (isset($result->q->ServiceLevels)) ? $result->q->ServiceLevels : [];
        if (!empty($service_level)) {
            foreach ($service_level as $key => $service) {
                $code = (isset($service->Code)) ? $service->Code : '';
                if ($code == 'STD') {
                    $delivery_estimates = (isset($service->totalTransitTimeInDays)) ? $service->totalTransitTimeInDays : '';
                    $delivery_time_stamp = (isset($service->deliveryDate)) ? $service->deliveryDate : '';
                }
            }
        }

        // Excluded accessoarials
        $excluded = false;
        if (isset($result->liftgateExcluded) && $result->liftgateExcluded == 1) {
            $this->quote_settings['liftgate_delivery'] = 'no';
            $this->quote_settings['liftgate_resid_delivery'] = "no";
            $this->en_accessorial_excluded = ['liftgateResidentialExcluded'];
            add_filter('en_rnl_ltl_accessorial_excluded', [$this, 'en_rnl_ltl_accessorial_excluded'], 10, 1);
            $en_fdo_meta_data['accessorials']['residential'] = false;
            $en_fdo_meta_data['accessorials']['liftgate'] = false;
            $excluded = true;
        }

        ($this->quote_settings['liftgate_delivery'] == "yes") ? $accessorials[] = "L" : "";
        ($this->quote_settings['residential_delivery'] == "yes") ? $accessorials[] = "R" : "";
        ($this->quote_settings['limited_access_delivery'] == "yes") ? $accessorials[] = "LA" : "";
        $this->quote_settings['limited_access_delivery_option'] = get_option('rnl_limited_access_delivery_as_option');
        (is_array($request_data['hazardous']) && !empty($request_data['hazardous'])) ? $accessorials[] = "H" : "";

        //limited access
        in_array('LA', $accessorials) ? $en_fdo_meta_data['accessorials']['limitedaccess'] = true : '';
        
        $label_sufex_arr = $this->filter_label_sufex_array_rnl_quotes($result);

        // Standard packaging
        $standard_packaging = isset($result->standardPackagingData) ? $result->standardPackagingData : [];
        $error = isset($result->severity) && $result->severity == 'ERROR';

        if (isset($result->q) && !$error) {

            $services_result = !is_array($result->q->ServiceLevels) ? array($result->q->ServiceLevels) : $result->q->ServiceLevels;
            $surcharges = isset($result->q->Charges) ? $result->q->Charges : '';
            $active_services = $this->get_active_services();
            $standard_service_quotes = [];
            $guaranteed_services_quotes = [];

            if (is_array($services_result) && count($services_result) > 0) {
                foreach ($services_result as $key => $qoute_values) {
                    $delivery_estimates = isset($qoute_values->totalTransitTimeInDays) ? $qoute_values->totalTransitTimeInDays : '';
                    $delivery_time_stamp = isset($qoute_values->deliveryDate) ? $qoute_values->deliveryDate : '';

                    if (in_array($qoute_values->Code, $active_services) && !empty($qoute_values->NetCharge)) {
                        $meta_data['service_type'] = 'Freight';
                        $meta_data['accessorials'] = json_encode($accessorials);
                        $meta_data['sender_origin'] = $request_data['sender_origin'];
                        $meta_data['product_name'] = json_encode($request_data['product_name']);
                        // Standard Packaging
                        $meta_data['standard_packaging'] = wp_json_encode($standard_packaging);

                        // Micro Warehouse
                        $meta_data['quote_settings'] = json_encode($this->quote_settings);

                        $price = (isset($qoute_values->NetCharge)) ? str_replace(',', '', str_replace('$', '', $qoute_values->NetCharge)) : 0;
                        
                        // Add limited access delivery fee in quote price and surcharge in rates surcharges array
                        if ($this->quote_settings['limited_access_delivery_option'] == 'yes' || ($this->quote_settings['limited_access_delivery'] == 'yes' && !in_array('R', $label_sufex_arr))) {
                            $price = $this->addLimitedAccessDelFee($price);
                            $surcharges = isset($surcharges) ? $surcharges : [];
                            $surcharges = $this->addLimitedAccessDelInSurcharges($surcharges);
                        } else {
                            unset($label_sufex_arr['LA']);
                            unset($accessorials['LA']);
                            $en_fdo_meta_data['accessorials']['limitedaccess'] = '';
                        }

                        $RNL_Freight_Shipping_Class = new RNL_Freight_Shipping_Class();
                        
                        // Product level markup
                        if ( !empty($request_data['product_level_markup'])) {
                            $price = $RNL_Freight_Shipping_Class->add_handling_fee($price, $request_data['product_level_markup']);
                        }        
                        
                        // Origin level markup
                        if ( !empty($request_data['origin_markup'])) {
                            $price = $RNL_Freight_Shipping_Class->add_handling_fee($price, $request_data['origin_markup']);
                        }
                        

                        $transit = (isset($qoute_values->ServiceDays)) ? $qoute_values->ServiceDays : '';
                        $label = $this->get_service_label($qoute_values->Code);

                        $quotes = array(
                            'id' => 'rnl' . $qoute_values->Code,
                            'plugin_name' => 'rnl',

                            'cost' => $price,
                            'label' => $label,
                            'transit_time' => $transit,
                            'label_sfx_arr' => $label_sufex_arr,
                            // Cuttoff Time
                            'delivery_estimates' => $delivery_estimates,
                            'delivery_time_stamp' => $delivery_time_stamp,
                            'surcharges' => (isset($surcharges)) ? $this->update_parse_rnl_quotes_output($surcharges) : 0,
                            'meta_data' => $meta_data,
                            'markup' => $this->quote_settings['handling_fee'],
                            'plugin_name' => 'rnl',
                            'plugin_type' => 'ltl',
                            'owned_by' => 'eniture'
                        );

                        // add quote number
                        $en_fdo_meta_data['quote_number'] = (isset($qoute_values->QuoteNumber)) ? $qoute_values->QuoteNumber : '';

                        // Micro Warehouse
                        $quotes = array_merge($quotes, $meta_data);
                        $quotes = apply_filters('add_warehouse_appliance_handling_fee', $quotes, $request_data);

                        //FDO
                        $en_fdo_meta_data['rate'] = $quotes;
                        if (isset($en_fdo_meta_data['rate']['meta_data'])) {
                            unset($en_fdo_meta_data['rate']['meta_data']);
                        }
                        $en_fdo_meta_data['quote_settings'] = $this->quote_settings;
                        $quotes['meta_data']['en_fdo_meta_data'] = $en_fdo_meta_data;

                        // To Identify Auto Detect Residential address detected Or Not
                        $quotes = apply_filters("en_woo_addons_web_quotes", $quotes, en_woo_plugin_rnl_quotes);
                        $label_sufex = (isset($quotes['label_sufex'])) ? $quotes['label_sufex'] : array();
                        $label_sufex = $this->label_R_freight_view($label_sufex);
                        $quotes['label_sufex'] = $label_sufex;

                        in_array('R', $label_sufex_arr) ? $quotes['meta_data']['en_fdo_meta_data']['accessorials']['residential'] = true : '';
                        ($this->quote_settings['liftgate_resid_delivery'] == "yes") && (in_array("R", $label_sufex)) && in_array('L', $label_sufex_arr) ? $quotes['meta_data']['en_fdo_meta_data']['accessorials']['liftgate'] = true : '';

                        $simple_quotes = [];

                        // When Lift Gate As An Option Enabled
                        if (($this->quote_settings['liftgate_delivery_option'] == "yes") && (!isset($result->liftgateExcluded)) &&
                            (($this->quote_settings['liftgate_resid_delivery'] == "yes") && (!in_array("R", $label_sufex)) ||
                                ($this->quote_settings['liftgate_resid_delivery'] != "yes"))) {
                            $service = $quotes;
                            $quotes['id'] .= "WL";

                            (isset($quotes['label_sufex']) &&
                                (!empty($quotes['label_sufex']))) ?
                                array_push($quotes['label_sufex'], "L") : // IF
                                $quotes['label_sufex'] = array("L");       // ELSE

                            // FDO
                            $quotes['meta_data']['en_fdo_meta_data']['accessorials']['liftgate'] = true;
                            $quotes['append_label'] = " with lift gate delivery ";

                            $liftgate_charge = (isset($service['surcharges']['LIFT'])) ? $service['surcharges']['LIFT'] : 0;
                            $service['cost'] = (isset($service['cost'])) ? $service['cost'] - $liftgate_charge : 0;
                            (!empty($service)) && (in_array("R", $service['label_sufex'])) ? $service['label_sufex'] = array("R") : $service['label_sufex'] = array();

                            $simple_quotes = $service;
                            if (isset($simple_quotes['meta_data']['min_quotes'])) {
                                unset($simple_quotes['meta_data']['min_quotes']);
                            }
                            $simple_quotes = apply_filters('add_warehouse_appliance_handling_fee', $simple_quotes, $request_data);
                            
                            // FDO
                            if (isset($simple_quotes['meta_data']['en_fdo_meta_data']['rate']['cost'])) {
                                $simple_quotes['meta_data']['en_fdo_meta_data']['rate']['cost'] = $service['cost'];
                            }
                        } elseif ($excluded) {
                            // Excluded accessoarials
                            $simple_quotes = $quotes;
                        }

                        // When Limited Access As An Option Enabled                        
                        $limited_access_quotes = [];
                        if ($this->quote_settings['limited_access_delivery_option'] == 'yes') {
                            
                            $limited_access_quotes = $quotes;
                            $limited_access_quotes['label_sufex'] = ['LA'];
                            $limited_access_quotes['id'] .= str_contains($limited_access_quotes['id'], 'WL') ? 'A' : "WLA";
                            
                            $lg_fee = (isset($limited_access_quotes['surcharges']['LIFT'])) ? $limited_access_quotes['surcharges']['LIFT'] : 0;
                            $resi_fee = (isset($limited_access_quotes['surcharges']['RC'])) ? $limited_access_quotes['surcharges']['RC'] : 0;
                            $limited_access_quotes['cost'] -= floatval($lg_fee + $resi_fee); 
                            $la_fee = (isset($limited_access_quotes['surcharges']['LA'])) ? $limited_access_quotes['surcharges']['LA'] : 0;

                            // when lift gate as option is enabled
                            if (!empty($simple_quotes) && isset($simple_quotes['cost'])) {
                                $simple_quotes['cost'] -= floatval($la_fee);
                                $quotes['cost'] -= floatval($la_fee);
                            } else {
                                $quotes['cost'] -= floatval($la_fee);
                            }

                            $limited_access_quotes['append_label'] = " with limited access delivery ";
                            // FDO
                            $limited_access_quotes['meta_data']['en_fdo_meta_data']['accessorials']['liftgate'] = false;
                            $limited_access_quotes['meta_data']['en_fdo_meta_data']['accessorials']['residential'] = false;
                            $limited_access_quotes['meta_data']['en_fdo_meta_data']['accessorials']['limitedaccess'] = true;
                            $limited_access_quotes['meta_data']['en_fdo_meta_data']['rate']['cost'] = $limited_access_quotes['cost'];

                            if (!empty($simple_quotes)) {
                                if (isset($simple_quotes['meta_data']['min_quotes'])) {
                                    unset($simple_quotes['meta_data']['min_quotes']);
                                }
                                
                                $simple_quotes = apply_filters('add_warehouse_appliance_handling_fee', $simple_quotes, $request_data);
                                
                                // FDO
                                if (isset($simple_quotes['meta_data']['en_fdo_meta_data']['rate']['cost'])) {
                                    $simple_quotes['meta_data']['en_fdo_meta_data']['rate']['cost'] = $simple_quotes['cost'];
                                }
                            }
                        }
                    }

                    $hold_at_terminal = apply_filters('rnl_quotes_quotes_plans_suscription_and_features', 'rnl_hold_at_terminal');

                    // When Hold At Terminal Enabled
                    if (in_array($qoute_values->Code, $active_services) && $qoute_values->Code == 'STD' && isset($result->holdAtTerminalResponse, $result->holdAtTerminalResponse->serviceLevels->STD->totalNetCharge) && !is_array($hold_at_terminal) && $this->quote_settings['HAT_status'] == 'yes' || (isset($result->holdAtTerminalResponse->severity) && $result->holdAtTerminalResponse->severity != 'ERROR')) {

                        $hold_at_terminal_fee = (isset($result->holdAtTerminalResponse->serviceLevels->STD->totalNetCharge)) ? $result->holdAtTerminalResponse->serviceLevels->STD->totalNetCharge : 0;
                        if (isset($this->quote_settings['HAT_fee']) && (strlen($this->quote_settings['HAT_fee']) > 0)) {
                            
                            // Product level markup
                            if ( !empty($request_data['product_level_markup'])) {
                                $hold_at_terminal_fee = $RNL_Freight_Shipping_Class->add_handling_fee($hold_at_terminal_fee, $request_data['product_level_markup']);
                            }    

                            // Origin level markup
                            if ( !empty($request_data['origin_markup'])) {
                                $hold_at_terminal_fee = $RNL_Freight_Shipping_Class->add_handling_fee($hold_at_terminal_fee, $request_data['origin_markup']);
                            }

                            $RNL_Freight_Shipping_Class = new RNL_Freight_Shipping_Class();
                            $hold_at_terminal_fee = $RNL_Freight_Shipping_Class->add_handling_fee($hold_at_terminal_fee, $this->quote_settings['HAT_fee']);
                        }

                        $_accessorials = (in_array('H', $accessorials)) ? array('HAT', 'H') : array('HAT');

                        $meta_data['service_type'] = 'FreightHAT';
                        $meta_data['accessorials'] = json_encode($_accessorials);
                        $meta_data['sender_origin'] = $request_data['sender_origin'];
                        $meta_data['product_name'] = json_encode($request_data['product_name']);
                        $meta_data['address'] = (isset($result->holdAtTerminalResponse->address)) ? json_encode($result->holdAtTerminalResponse->address) : array();
                        $meta_data['_address'] = (isset($result->holdAtTerminalResponse->address, $result->holdAtTerminalResponse->address->Phone, $result->holdAtTerminalResponse->distance)) ? $this->get_address_terminal($result->holdAtTerminalResponse->address, $result->holdAtTerminalResponse->address->Phone, $result->holdAtTerminalResponse->distance) : '';
                        // Standard Packaging
                        $meta_data['standard_packaging'] = wp_json_encode($standard_packaging);

                        $hold_at_terminal_resp = (isset($result->holdAtTerminalResponse)) ? $result->holdAtTerminalResponse : [];
                        $hat_label = $this->get_service_label($qoute_values->Code);

                        $hat_quotes = array(
                            'id' => $meta_data['service_type'],
                            'cost' => $hold_at_terminal_fee,
                            'label' => $hat_label,
                            'address' => $meta_data['address'],
                            '_address' => $meta_data['_address'],
                            'transit_time' => $transit,
                            // Cuttoff Time
                            'delivery_estimates' => $delivery_estimates,
                            'delivery_time_stamp' => $delivery_time_stamp,
                            'sandbox' => '',
                            'label_sfx_arr' => $label_sufex_arr,
                            'hat_append_label' => ' with hold at terminal',
                            '_hat_append_label' => $meta_data['_address'],
                            'meta_data' => $meta_data,
                            'markup' => $this->quote_settings['handling_fee'],
                            'plugin_name' => 'rnl',
                            'plugin_type' => 'ltl',
                            'owned_by' => 'eniture'
                        );

                        // FDO
                        $en_fdo_meta_data['rate'] = $hat_quotes;
                        if (isset($en_fdo_meta_data['rate']['meta_data'])) {
                            unset($en_fdo_meta_data['rate']['meta_data']);
                        }

                        $en_fdo_meta_data['quote_settings'] = $this->quote_settings;
                        $en_fdo_meta_data['holdatterminal'] = $hold_at_terminal_resp;
                        $hat_quotes['meta_data']['en_fdo_meta_data'] = $en_fdo_meta_data;
                        $accessorials_hat = [
                            'holdatterminal' => true,
                            'residential' => false,
                            'liftgate' => false,
                        ];
                        if (isset($hat_quotes['meta_data']['en_fdo_meta_data']['accessorials'])) {
                            $hat_quotes['meta_data']['en_fdo_meta_data']['accessorials'] = array_merge($hat_quotes['meta_data']['en_fdo_meta_data']['accessorials'], $accessorials_hat);
                        } else {
                            $hat_quotes['meta_data']['en_fdo_meta_data']['accessorials']['holdatterminal'] = true;
                        }

                        // -100% Fee is invalid
                        if (isset($this->quote_settings['HAT_fee']) &&
                            ($this->quote_settings['HAT_fee'] == "-100%")) {
                            $hat_quotes = array();
                        }
                    }

                    if (in_array($qoute_values->Code, $active_services)) {
                        if ($qoute_values->Code == 'STD') {
                            $standard_service_quotes = $quotes;
    
                            if (!empty($simple_quotes)) $standard_service_quotes['simple_quotes'] = $simple_quotes;
                            if (!empty($limited_access_quotes)) $standard_service_quotes['limited_access_quotes'] = $limited_access_quotes;
                            if (!empty($hat_quotes)) $standard_service_quotes['hold_at_terminal_quotes'] = $hat_quotes;
                        } else {
                            $guaranteed_services_quotes[$qoute_values->Code] = $quotes;
    
                            if (!empty($simple_quotes)) $guaranteed_services_quotes[$qoute_values->Code]['simple_quotes'] = $simple_quotes;
                            if (!empty($limited_access_quotes)) $guaranteed_services_quotes[$qoute_values->Code]['limited_access_quotes'] = $limited_access_quotes;
                        }
                    }
                }
            }
        } else {
            return [];
        }

        $quotes = $standard_service_quotes;
        (!empty($guaranteed_services_quotes)) ? $quotes['guaranteed_services_quotes'] = $guaranteed_services_quotes : "";

        return $quotes;
    }

    public function get_address_terminal($address, $phone_nbr, $distance)
    {

        $address_terminal = '';
        $address_terminal .= (isset($distance->text) && is_string($distance->text)) ? ' | ' . $distance->text : '';
        $address_terminal .= (isset($address->Address1) && is_string($address->Address1)) ? ' | ' . $address->Address1 : '';
        $address_terminal .= (isset($address->City) && is_string($address->City)) ? ' ' . $address->City : '';
        $address_terminal .= (isset($address->State) && is_string($address->State)) ? ' ' . $address->State : '';
        $address_terminal .= (isset($address->ZipCode) && is_string($address->ZipCode)) ? ' ' . $address->ZipCode : '';
        $address_terminal .= (strlen($phone_nbr) > 0) ? ' | T: ' . $phone_nbr : '';

        return $address_terminal;
    }

    /**
     * check "R" in array
     * @param array type $label_sufex
     * @return array type
     */
    public function label_R_freight_view($label_sufex)
    {
        if ($this->quote_settings['residential_delivery'] == 'yes' && (in_array("R", $label_sufex))) {
            $label_sufex = array_flip($label_sufex);
            unset($label_sufex['R']);
            $label_sufex = array_keys($label_sufex);
        }

        return $label_sufex;
    }

    /**
     * Return R+L LTL In-store Pickup Array
     */
    function rnl_ltl_return_local_delivery_store_pickup()
    {
        return $this->InstorPickupLocalDelivery;
    }

    function addLimitedAccessDelFee($charges) 
    {
        $is_limited_access_active = get_option('rnl_limited_access_delivery') == 'yes' || get_option('rnl_limited_access_delivery_as_option') == 'yes';
        $limited_access_fee = !empty(get_option('rnl_limited_access_delivery_fee')) ? get_option('rnl_limited_access_delivery_fee') : 0;

        if ($is_limited_access_active) {
            $charges = $charges + floatval($limited_access_fee);
        }

        return $charges;
    }

    function addLimitedAccessDelInSurcharges($surcharges)
    {
        $surcharges[] = (object) [
            'Type' => 'LA',
            'Title' => 'Limited Access Delivery',
            'Amount' => get_option('rnl_limited_access_delivery_fee')
        ];

        return $surcharges;
    }

    function get_active_services()
    {
        $services = [];
        $services_list = [
            'standard_enable_service' => 'STD',
            'guaranteed_pm_enable_service' => 'GSDS',
            'guaranteed_am_enable_service' => 'GSAM',
            'guaranteed_hourly_enable_service' => 'GSHW',
        ];

        foreach ($services_list as $key => $service) {
            if (get_option($key) == 'yes') {
                array_push($services, $service);
            }
        }

        return $services;
    }

    function get_service_label($service_code)
    {
        $labels = [
            'STD'  => !empty($this->quote_settings['label']) ? $this->quote_settings['label'] : 'Standard Service',
            'GSDS' => !empty($this->quote_settings['guaranteed_pm_label_as']) ? $this->quote_settings['guaranteed_pm_label_as'] : 'Guaranteed PM',
            'GSAM' => !empty($this->quote_settings['guaranteed_am_label_as']) ? $this->quote_settings['guaranteed_am_label_as'] : 'Guaranteed AM', 
            'GSHW' => !empty($this->quote_settings['guaranteed_hourly_label_as']) ? $this->quote_settings['guaranteed_hourly_label_as'] : 'Guaranteed Hourly Window'
        ];

        return isset($labels[$service_code]) ? $labels[$service_code] : '';
    }

    function applyErrorManagement($quotes_request)
    {
        // error management will be applied only for more than 1 product
        if (empty($quotes_request) || empty($quotes_request['commdityDetails']['handlingUnitDetails']) || (!empty($quotes_request['commdityDetails']['handlingUnitDetails']) && count($quotes_request['commdityDetails']['handlingUnitDetails']) < 2)) return $quotes_request;

        $error_option = !empty(get_option('error_management_settings_rnl_ltl')) ? get_option('error_management_settings_rnl_ltl') : 'quote_shipping';
        $dont_quote_shipping = false;
        $items_ids = [];

        foreach ($quotes_request['commdityDetails']['handlingUnitDetails'] as $key => $product) {
            $empty_dims_check = empty($product['lineItemWidth']) || empty($product['lineItemHeight']) || empty($product['lineItemLength']);
            $empty_shipping_class_check = empty($product['lineItemClass']);
            $weight = $product['lineItemWeight'];

            if (empty($weight) || ($empty_dims_check && $empty_shipping_class_check)) {
                if ($error_option == 'dont_quote_shipping') {
                    $dont_quote_shipping = true;
                    break;
                } else {
                    unset($quotes_request['commdityDetails']['handlingUnitDetails'][$key]);
                    $items_ids[] = $key;
                }
            }
        }

        $quotes_request['error_management'] = $error_option;
        // error management will be applied for all products in case of dont quote shipping option
        if ($dont_quote_shipping) $quotes_request['commdityDetails']['handlingUnitDetails'] = [];

        // set error property for items in fdo meta-data array to hide them on order widget details
        if (!empty($items_ids) && !$dont_quote_shipping && isset($quotes_request['en_fdo_meta_data']['items'])) {
            foreach ($quotes_request['en_fdo_meta_data']['items'] as $key => $item) {
                if (!isset($item['id'])) continue;

                if (in_array($item['id'], $items_ids)) {
                    $quotes_request['en_fdo_meta_data']['items'][$key]['error_management'] = true;
                }
            }
        }

        return $quotes_request;
    }

    /**
     * Accessoarials excluded
     * @param $excluded
     * @return array
    */
    function en_rnl_ltl_accessorial_excluded($excluded)
    {
        return array_merge($excluded, $this->en_accessorial_excluded);
    }
}
