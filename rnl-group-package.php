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
 * Get Shipping Package Class
 */
class RNL_Freight_Shipping_Get_Package
{
    /**
     * hasLTLShipment
     * @var int
     */
    public $hasLTLShipment = 0;
    /**
     * Errors
     * @var varchar
     */
    public $errors = [];

    public $ValidShipments = 0;

    public $ValidShipmentsArrRnL = [];

    // Micro Warehouse
    public $products = [];
    public $dropship_location_array = [];
    public $warehouse_products = [];
    public $destination_Address_rnl;
    public $origin = [];
    // Images for FDO
    public $en_fdo_image_urls = [];

    /**
     * Grouping For Shipments
     * @param $package
     * @param $rnl_res_inst
     * @param $rnl_zipcode
     * @return int/string/array
     * @global $wpdb
     */
    function group_rnl_shipment($package, $rnl_res_inst, $rnl_zipcode)
    {
        $rnl_package = [];
        if (empty($rnl_zipcode)) {
            return [];
        }
        global $wpdb;
        $weight = 0;
        $dimensions = 0;
        $rnl_freight_class = "";
        $rnl_enable = false;
        $counter = 0;

        // Micro Warehouse
        $smallPluginExist = 0;
        $rnl_package = $items = $items_shipment = [];
        $RNL_Freight_Get_Shipping_Quotes = new RNL_Freight_Get_Shipping_Quotes();
        $this->destination_Address_rnl = $RNL_Freight_Get_Shipping_Quotes->destinationAddressRnL();
        //threshold
        $weight_threshold = get_option('en_weight_threshold_lfq');
        $weight_threshold = isset($weight_threshold) && $weight_threshold > 0 ? $weight_threshold : 150;

        //pallet per product
        $en_ppp_pallet_product = apply_filters('en_ppp_existence', false);

        $wc_settings_wwe_ignore_items = get_option("en_ignore_items_through_freight_classification");
        $en_get_current_classes = strlen($wc_settings_wwe_ignore_items) > 0 ? trim(strtolower($wc_settings_wwe_ignore_items)) : '';
        $en_get_current_classes_arr = strlen($en_get_current_classes) > 0 ? array_map('trim', explode(',', $en_get_current_classes)) : [];

        $flat_rate_shipping_addon = apply_filters('en_add_flat_rate_shipping_addon', false);
        foreach ($package['contents'] as $item_id => $values) {
            $_product = $values['data'];

            // Images for FDO
            $this->en_fdo_image_urls($values, $_product);

            // Flat rate pricing
            $product_id = (isset($values['variation_id']) && $values['variation_id'] > 0) ? $values['variation_id'] : $_product->get_id();
            $parent_id = $product_id;
            if(isset($values['variation_id']) && $values['variation_id'] > 0){
                $variation = wc_get_product($values['variation_id']);
                $parent_id = $variation->get_parent_id();
            }

            $en_flat_rate_price = $this->en_get_flat_rate_price($values, $_product);
            if ($flat_rate_shipping_addon && isset($en_flat_rate_price) && strlen($en_flat_rate_price) > 0) {
                continue;
            }

            // Get product shipping class
            $en_ship_class = strtolower($values['data']->get_shipping_class());
            if (in_array($en_ship_class, $en_get_current_classes_arr)) {
                continue;
            }

            // Shippable handling units
            $values = apply_filters('en_shippable_handling_units_request', $values, $values, $_product);
            $shippable = [];
            if (isset($values['shippable']) && !empty($values['shippable'])) {
                $shippable = $values['shippable'];
            }

            // Standard Packaging
            $ppp_product_pallet = [];
            $values = apply_filters('en_ppp_request', $values, $values, $_product);
            if (isset($values['ppp']) && !empty($values['ppp'])) {
                $ppp_product_pallet = $values['ppp'];
            }

            $ship_as_own_pallet = $vertical_rotation_for_pallet = 'no';
            if (!$en_ppp_pallet_product) {
                $ppp_product_pallet = [];
            }

            extract($ppp_product_pallet);

            // Nesting
            $nestedPercentage = 0;
            $nestedDimension = "";
            $nestedItems = "";
            $StakingProperty = "";
            $height = is_numeric($_product->get_height()) ? $_product->get_height() : 0;
            $width = is_numeric($_product->get_width()) ? $_product->get_width() : 0;
            $length = is_numeric($_product->get_length()) ? $_product->get_length() : 0;
            $height = wc_get_dimension($height, 'in');
            $width = wc_get_dimension($width, 'in');
            $length = wc_get_dimension($length, 'in');
            $product_weight = wc_get_weight($_product->get_weight(), 'lbs');
            $weight = ($values['quantity'] == 1) ? $product_weight : $product_weight * $values['quantity'];
            $freightClass = $_product->get_shipping_class(); // it define either product marked as ltl or not

            if ($_product->get_shipping_class() == 'ltl_freight') {
                $ltl_freight_class = $_product->get_shipping_class();
            }

            $locationId = 0;
            (isset($values['variation_id']) && $values['variation_id'] > 0) ? $post_id = $values['variation_id'] : $post_id = $_product->get_id();
            $locations_list = $this->rnl_get_locations_list($post_id);
            $origin_address = $rnl_res_inst->rnl_multi_warehouse($locations_list, $rnl_zipcode);
            $locationId = (isset($origin_address['id'])) ? $origin_address['id'] : $origin_address['locationId'];
            $product_level_markup = $this->rl_ltl_get_product_level_markup($_product, $values['variation_id'], $values['product_id'], $values['quantity']);

            // Micro Warehouse
            (isset($values['variation_id']) && $values['variation_id'] > 0) ? $post_id = $values['variation_id'] : $post_id = $_product->get_id();
            $this->products[] = $post_id;

            $rnl_package[$locationId]['origin'] = $origin_address;
            $getFreightClassAndHazardous = $this->rnl_get_freight_class_hazardous($_product, $values['variation_id'], $values['product_id']);
            ($getFreightClassAndHazardous["freightClass_ltl_gross"] == 'Null') ? $getFreightClassAndHazardous["freightClass_ltl_gross"] = "" : "";

            $product_title = str_replace(array("'", '"'), '', $_product->get_title());

            // Hazardous Material
            $hazardous_material = $getFreightClassAndHazardous["hazardous_material"];
            $hm_plan = apply_filters('rnl_quotes_quotes_plans_suscription_and_features', 'hazardous_material');
            $hm_status = (!is_array($hm_plan) && $hazardous_material == 'yes') ? TRUE : FALSE;

            // Nesting
            $nested_material = $this->en_nested_material($values, $_product);

            if ($nested_material == "yes") {
                $post_id = (isset($values['variation_id']) && $values['variation_id'] > 0) ? $values['variation_id'] : $_product->get_id();
                $nestedPercentage = get_post_meta($post_id, '_nestedPercentage', true);
                $nestedDimension = get_post_meta($post_id, '_nestedDimension', true);
                $nestedItems = get_post_meta($post_id, '_maxNestedItems', true);
                $StakingProperty = get_post_meta($post_id, '_nestedStakingProperty', true);
            }

            // Shippable handling units
            $lineItemPalletFlag = $lineItemPackageCode = $lineItemPackageType = '0';
            extract($shippable);

            $en_items = [
                'productId' => $parent_id,
                'productName' => str_replace(array("'", '"'), '', $_product->get_name()),
                'productQty' => $values['quantity'],
                'product_name' => $values['quantity'] . " x " . str_replace(array("'", '"'), '', $_product->get_name()),
                'productPrice' => $_product->get_price(),
                'productWeight' => $product_weight,
                'productLength' => $length,
                'productWidth' => $width,
                'productHeight' => $height,
                'productClass' => $getFreightClassAndHazardous["freightClass_ltl_gross"],
                'freightClass' => $freightClass,
                'hazmat' => $getFreightClassAndHazardous["hazardous_material"],

                // FDO
                'hazardous_material' => $hm_status,
                'hazardousMaterial' => $hm_status,
                'productType' => ($_product->get_type() == 'variation') ? 'variant' : 'simple',
                'productSku' => $_product->get_sku(),
                'actualProductPrice' => $_product->get_price(),
                'attributes' => $_product->get_attributes(),
                'variantId' => ($_product->get_type() == 'variation') ? $_product->get_id() : '',
                // Nesting
                'nestedMaterial' => $nested_material,
                'nestedPercentage' => $nestedPercentage,
                'nestedDimension' => $nestedDimension,
                'nestedItems' => $nestedItems,
                'stakingProperty' => $StakingProperty,

                // Shippable handling units
                'lineItemPalletFlag' => $lineItemPalletFlag,
                'lineItemPackageCode' => $lineItemPackageCode,
                'lineItemPackageType' => $lineItemPackageType,

                // Standard Packaging
                'ship_as_own_pallet' => $ship_as_own_pallet,
                'vertical_rotation_for_pallet' => $vertical_rotation_for_pallet,
                'markup' => $product_level_markup
            ];

            // Hook for flexibility adding to package
            $en_items = apply_filters('en_group_package', $en_items, $values, $_product);
            // NMFC Number things
            $en_items = $this->en_group_package($en_items, $values, $_product);
            // Micro Warehouse
            $items[$post_id] = $en_items;

            if (!empty($origin_address)) {
                if (!$_product->is_virtual()) {
                    $_product = $values['data'];

                    $rnl_package[$locationId]['items'][$counter] = $en_items;

                    $validateProductParamsRtrn = $this->validateProductParams($rnl_package[$locationId]['items'][$counter]);
                    (isset($validateProductParamsRtrn) && ($validateProductParamsRtrn === 1)) ? $validShipmentForLtl = 1 : "";
                    $rnl_package[$locationId]['items'][$counter]['validForLtl'] = $validateProductParamsRtrn;
                }
            }
            $rnl_enable = $this->get_rnl_enable($_product);

            // Product tags
            $product_tags = get_the_terms($product_id, 'product_tag');
            $product_tags = empty($product_tags) ? get_the_terms($parent_id, 'product_tag') : $product_tags;
            if (!empty($product_tags)) {
                $product_tag_names = array_map(function($tag) { return $tag->term_id; }, $product_tags);

                if (isset($rnl_package[$locationId]['product_tags'])) {
                    $rnl_package[$locationId]['product_tags'] = array_merge($rnl_package[$locationId]['product_tags'], $product_tag_names);
                } else {
                    $rnl_package[$locationId]['product_tags'] = $product_tag_names;
                }
            } else {
                $rnl_package[$locationId]['product_tags'] = [];
            }

            // Product quantity
            if (isset($rnl_package[$locationId]['product_quantities'])) {
                $rnl_package[$locationId]['product_quantities'] += floatval($values['quantity']);
            } else {
                $rnl_package[$locationId]['product_quantities'] = floatval($values['quantity']);
            }

            // Product price
            if (isset($rnl_package[$locationId]['product_prices'])) {
                $rnl_package[$locationId]['product_prices'] += (floatval($_product->get_price()) * floatval($values['quantity']));
            } else {
                $rnl_package[$locationId]['product_prices'] = (floatval($_product->get_price()) * floatval($values['quantity']));
            }

            // Micro Warehouse
            $items_shipment[$post_id] = $rnl_enable;

            $exceedWeight = get_option('en_plugins_return_LTL_quotes');
            $rnl_package[$locationId]['shipment_weight'] = isset($rnl_package[$locationId]['shipment_weight']) ? $rnl_package[$locationId]['shipment_weight'] + $weight : $weight;
            $rnl_package[$locationId]['hazardousMaterial'] = isset($rnl_package[$locationId]['hazardousMaterial']) && $rnl_package[$locationId]['hazardousMaterial'] == 'yes' ? $rnl_package[$locationId]['hazardousMaterial'] : $getFreightClassAndHazardous["hazardous_material"];

            // ValidShipmentForLtl RNL
            $rnl_package[$locationId]['validShipmentForLtl'] = $validShipmentForLtl;
            (isset($validShipmentForLtl) && ($validShipmentForLtl === 1)) ? $this->ValidShipments = 1 : "";

            $smallPluginExist = 0;
            $calledMethod = [];
            $eniturePluigns = json_decode(get_option('EN_Plugins'));
            if (!empty($eniturePluigns)) {
                foreach ($eniturePluigns as $enIndex => $enPlugin) {
                    $freightSmallClassName = 'WC_' . $enPlugin;
                    if (!in_array($freightSmallClassName, $calledMethod)) {
                        if (class_exists($freightSmallClassName)) {
                            $smallPluginExist = 1;
                        }
                        $calledMethod[] = $freightSmallClassName;
                    }
                }
            }
            if ($rnl_enable == true || ($rnl_package[$locationId]['shipment_weight'] > $weight_threshold && $exceedWeight == 'yes')) {
                $rnl_package[$locationId]['rnl'] = 1;
                $this->hasLTLShipment = 1;
                $this->ValidShipmentsArrRnL[] = "ltl_freight"; //$freightClass;
            } elseif (isset($rnl_package[$locationId]['rnl'])) {
                $rnl_package[$locationId]['rnl'] = 1;
                $this->hasLTLShipment = 1;
                $this->ValidShipmentsArrRnL[] = "ltl_freight"; //$freightClass;
            } elseif ($smallPluginExist == 1) {
                $rnl_package[$locationId]['small'] = 1;
                $this->ValidShipmentsArrRnL[] = "small_shipment";
            } else {
                $this->ValidShipmentsArrRnL[] = "no_shipment";
            }

            $counter++;
        }

        // Micro Warehouse
        $eniureLicenceKey = get_option('wc_settings_rnl_plugin_licence_key');
        $rnl_package = apply_filters('en_micro_warehouse', $rnl_package, $this->products, $this->dropship_location_array, $this->destination_Address_rnl, $this->origin, $smallPluginExist, $items, $items_shipment, $this->warehouse_products, $eniureLicenceKey, 'rnl');
        do_action("eniture_debug_mood", "Product Detail (rnl)", $rnl_package);
        return $rnl_package;
    }

    /**
     * Set images urls | Images for FDO
     * @param array type $en_fdo_image_urls
     * @return array type
     */
    public function en_fdo_image_urls_merge($en_fdo_image_urls)
    {
        return array_merge($this->en_fdo_image_urls, $en_fdo_image_urls);
    }

    /**
     * Get images urls | Images for FDO
     * @param array type $values
     * @param array type $_product
     * @return array type
     */
    public function en_fdo_image_urls($values, $_product)
    {
        $product_id = (isset($values['variation_id']) && $values['variation_id'] > 0) ? $values['variation_id'] : $_product->get_id();
        $gallery_image_ids = $_product->get_gallery_image_ids();
        foreach ($gallery_image_ids as $key => $image_id) {
            $gallery_image_ids[$key] = $image_id > 0 ? wp_get_attachment_url($image_id) : '';
        }

        $image_id = $_product->get_image_id();
        $this->en_fdo_image_urls[$product_id] = [
            'product_id' => $product_id,
            'image_id' => $image_id > 0 ? wp_get_attachment_url($image_id) : '',
            'gallery_image_ids' => $gallery_image_ids
        ];

        add_filter('en_fdo_image_urls_merge', [$this, 'en_fdo_image_urls_merge'], 10, 1);
    }

    /**
     * Nested Material
     * @param array type $values
     * @param array type $_product
     * @return string type
     */
    function en_nested_material($values, $_product)
    {
        $post_id = (isset($values['variation_id']) && $values['variation_id'] > 0) ? $values['variation_id'] : $_product->get_id();
        return get_post_meta($post_id, '_nestedMaterials', true);
    }

    /**
     *
     * @param type $productData
     * @return int
     */

    function validateProductParams($productData)
    {
        if ((!isset($productData['freightClass']) || $productData['freightClass'] != "ltl_freight")) {
            return 0;
        }
        return 1;

    }

    /**
     * Check enable_dropship and get Locations list
     * @param $post_id
     * @return string/array
     * @global $wpdb
     */
    function rnl_get_locations_list($post_id)
    {
        global $wpdb;
        $locations_list = [];
        (isset($values['variation_id']) && $values['variation_id'] > 0) ? $post_id = $values['variation_id'] : $post_id;
        $enable_dropship = get_post_meta($post_id, '_enable_dropship', true);
        if ($enable_dropship == 'yes') {
            $get_loc = get_post_meta($post_id, '_dropship_location', true);
            if ($get_loc == '') {
                // Micro Warehouse
                $this->warehouse_products[] = $post_id;
                return array('error' => 'R+L LTL dp location not found!');
            }

//          Multi Dropship
            $multi_dropship = apply_filters('rnl_quotes_quotes_plans_suscription_and_features', 'multi_dropship');

            if (is_array($multi_dropship)) {
                $locations_list = $wpdb->get_results(
                    "SELECT * FROM " . $wpdb->prefix . "warehouse WHERE location = 'dropship' LIMIT 1"
                );
            } else {
                $get_loc = ($get_loc !== '') ? maybe_unserialize($get_loc) : $get_loc;
                $get_loc = is_array($get_loc) ? implode(" ', '", $get_loc) : $get_loc;
                $locations_list = $wpdb->get_results(
                    "SELECT * FROM " . $wpdb->prefix . "warehouse WHERE id IN ('" . $get_loc . "')"
                );
            }

            // Micro Warehouse
            $this->multiple_dropship_of_prod($locations_list, $post_id);
            $eniture_debug_name = "Dropships";
        }
        if (empty($locations_list)) {
//          Multi Warehouse
            $multi_warehouse = apply_filters('rnl_quotes_quotes_plans_suscription_and_features', 'multi_warehouse');
            if (is_array($multi_warehouse)) {
                $locations_list = $wpdb->get_results(
                    "SELECT * FROM " . $wpdb->prefix . "warehouse WHERE location = 'warehouse' LIMIT 1"
                );
            } else {
                $locations_list = $wpdb->get_results(
                    "SELECT * FROM " . $wpdb->prefix . "warehouse WHERE location = 'warehouse'"
                );
            }

            // Micro Warehouse
            $this->warehouse_products[] = $post_id;
            $eniture_debug_name = "Warehouses";
        }

        do_action("eniture_debug_mood", "Quotes $eniture_debug_name (s)", $locations_list);

        return $locations_list;
    }

    // Micro Warehouse
    public function multiple_dropship_of_prod($locations_list, $post_id)
    {
        $post_id = (string)$post_id;

        foreach ($locations_list as $key => $value) {
            $dropship_data = $this->address_array($value);

            $this->origin["D" . $dropship_data['zip']] = $dropship_data;
            if (!isset($this->dropship_location_array["D" . $dropship_data['zip']]) || !in_array($post_id, $this->dropship_location_array["D" . $dropship_data['zip']])) {
                $this->dropship_location_array["D" . $dropship_data['zip']][] = $post_id;
            }
        }

    }

    // Micro Warehouse
    public function address_array($value)
    {
        $dropship_data = [];

        $dropship_data['locationId'] = (isset($value->id)) ? $value->id : "";
        $dropship_data['zip'] = (isset($value->zip)) ? $value->zip : "";
        $dropship_data['city'] = (isset($value->city)) ? $value->city : "";
        $dropship_data['state'] = (isset($value->state)) ? $value->state : "";
        // Origin terminal address
        $dropship_data['address'] = (isset($value->address)) ? $value->address : "";
        // Terminal phone number
        $dropship_data['phone_instore'] = (isset($value->phone_instore)) ? $value->phone_instore : "";
        $dropship_data['location'] = (isset($value->location)) ? $value->location : "";
        $dropship_data['country'] = (isset($value->country)) ? $value->country : "";
        $dropship_data['enable_store_pickup'] = (isset($value->enable_store_pickup)) ? $value->enable_store_pickup : "";
        $dropship_data['fee_local_delivery'] = (isset($value->fee_local_delivery)) ? $value->fee_local_delivery : "";
        $dropship_data['suppress_local_delivery'] = (isset($value->suppress_local_delivery)) ? $value->suppress_local_delivery : "";
        $dropship_data['miles_store_pickup'] = (isset($value->miles_store_pickup)) ? $value->miles_store_pickup : "";
        $dropship_data['match_postal_store_pickup'] = (isset($value->match_postal_store_pickup)) ? $value->match_postal_store_pickup : "";
        $dropship_data['checkout_desc_store_pickup'] = (isset($value->checkout_desc_store_pickup)) ? $value->checkout_desc_store_pickup : "";
        $dropship_data['enable_local_delivery'] = (isset($value->enable_local_delivery)) ? $value->enable_local_delivery : "";
        $dropship_data['miles_local_delivery'] = (isset($value->miles_local_delivery)) ? $value->miles_local_delivery : "";
        $dropship_data['match_postal_local_delivery'] = (isset($value->match_postal_local_delivery)) ? $value->match_postal_local_delivery : "";
        $dropship_data['checkout_desc_local_delivery'] = (isset($value->checkout_desc_local_delivery)) ? $value->checkout_desc_local_delivery : "";

        $dropship_data['sender_origin'] = $dropship_data['location'] . ": " . $dropship_data['city'] . ", " . $dropship_data['state'] . " " . $dropship_data['zip'];

        return $dropship_data;
    }

    /**
     * Get Freight Class and Hazardous Material Checkbox
     * @param $_product
     * @param $variation_id
     * @param $product_id
     * @return Shipping Class
     */
    function rnl_get_freight_class_hazardous($_product, $variation_id, $product_id)
    {
        if ($_product->get_type() == 'variation') {
            $hazardous_material = get_post_meta($variation_id, '_hazardousmaterials', true);
            $variation_class = get_post_meta($variation_id, '_ltl_freight_variation', true);

            if ($variation_class == 0) {
                $variation_class = get_post_meta($product_id, '_ltl_freight', true);
                $freightClass_ltl_gross = $variation_class;
            } else {
                if ($variation_class > 0) {
                    $freightClass_ltl_gross = get_post_meta($variation_id, '_ltl_freight_variation', true);
                } else {
                    $freightClass_ltl_gross = get_post_meta($_product->get_id(), '_ltl_freight', true);
                }
            }
        } else {
            $hazardous_material = get_post_meta($_product->get_id(), '_hazardousmaterials', true);
            $freightClass_ltl_gross = get_post_meta($_product->get_id(), '_ltl_freight', true);
        }
        $aDataArr = array(
            'freightClass_ltl_gross' => $freightClass_ltl_gross,
            'hazardous_material' => $hazardous_material
        );
        return $aDataArr;
    }

    /**
     * Get R+L Enable or not
     * @param $_product
     * @return string/array
     */
    function get_rnl_enable($_product)
    {
        if ($_product->get_type() == 'variation') {
            $ship_class_id = $_product->get_shipping_class_id();
            if ($ship_class_id == 0) {
                $parent_data = $_product->get_parent_data();
                $get_parent_term = get_term_by('id', $parent_data['shipping_class_id'], 'product_shipping_class');
                $get_shipping_result = (isset($get_parent_term->slug)) ? $get_parent_term->slug : '';

            } else {
                $get_shipping_result = $_product->get_shipping_class();
            }

            $rnl_enable = ($get_shipping_result && $get_shipping_result == 'ltl_freight') ? true : false;
        } else {
            $get_shipping_result = $_product->get_shipping_class();
            $rnl_enable = ($get_shipping_result == 'ltl_freight') ? true : false;
        }
        return $rnl_enable;
    }

    /**
     * Grouping For Shipment Quotes
     * @param $quotes
     * @param $handlng_fee
     * @return Total Cost
     */
    function rnl_grouped_quotes($quotes, $handlng_fee)
    {
        $totalPrice = 0;
        $grandTotal = 0;
        $freight = [];
        $liftgate_amnt = 0;
        $label_sfx_arr = "";
        $grandTotalWdoutLiftGate = 0;

        if (count($quotes) > 0 && !empty($quotes)) {
            foreach ($quotes as $multiValues) {
                if (isset($multiValues) && !empty($multiValues)) {

                    $totalPriceLiftGate = (isset($multiValues['surcharges']['LIFT'])) ? $multiValues['surcharges']['LIFT'] : 0;

                    if ($handlng_fee != '') {
                        $grandTotal += $this->rnl_parse_handeling_fee($handlng_fee, $multiValues['cost']);
                        $grandTotalWdoutLiftGate += $this->rnl_parse_handeling_fee($handlng_fee, $multiValues['cost'] - $totalPriceLiftGate);

                    } else {
                        $grandTotal += $multiValues['cost'];
                        $grandTotalWdoutLiftGate += $multiValues['cost'] - $totalPriceLiftGate;
                    }
                } else {
                    $this->errors = 'no quotes return';
                    continue;
                }

                (isset($multiValues['surcharges']['LIFT']) && !empty($multiValues['surcharges']['LIFT'])) ? $liftgate_amnt = $liftgate_amnt + $multiValues['surcharges']['LIFT'] : '';
                (isset($multiValues['label_sfx_arr'])) ? $label_sfx_arr = $multiValues['label_sfx_arr'] : '';

            }
        }
        $freight = array('total' => $grandTotal,
            'label_sfx_arr' => $label_sfx_arr,
            'liftgate_amnt' => $liftgate_amnt,
            'grandTotalWdoutLiftGate' => $grandTotalWdoutLiftGate
        );
        return $freight;
    }

    /**
     * Grouping For Small Quotes
     * @param $smallQuotes
     * @return int/string Total Cost
     */
    function rnl_get_small_package_cost($smallQuotes)
    {
        $result = [];
        $minCostArr = [];
        if (isset($smallQuotes) && count($smallQuotes) > 0) {
            foreach ($smallQuotes as $smQuotes) {
                $CostArr = [];
                if (!isset($smQuotes['error'])) {
                    foreach ($smQuotes as $smQuote) {
                        $CostArr[] = $smQuote['cost'];
                        $result['error'] = false;
                    }
                    $minCostArr[] = (count($CostArr) > 0) ? min($CostArr) : "";
                } else {
                    $result['error'] = !isset($result['error']) ? true : $result['error'];
                }
            }
            $result['price'] = (isset($minCostArr) && count($minCostArr) > 0) ? min($minCostArr) : "";
        } else {
            $result['error'] = false;
            $result['price'] = 0;
        }
        return $result;
    }

    /**
     * Calculate Handling Fee
     * @param $handlng_fee
     * @param $cost
     * @return handling cost
     */
    function rnl_parse_handeling_fee($handlng_fee, $cost)
    {
        $pos = strpos($handlng_fee, '%');
        if ($pos > 0) {
            $exp = explode(substr($handlng_fee, $pos), $handlng_fee);
            $get = $exp[0];
            $percnt = $get / 100 * $cost;
            $grandTotal = $cost + $percnt;
        } else {
            $grandTotal = $cost + $handlng_fee;
        }
        return $grandTotal;
    }

    /**
     * Get the product nmfc number
     */
    public function en_group_package($item, $product_object, $product_detail)
    {
        $en_nmfc_number = $this->en_nmfc_number($product_object, $product_detail);
        $item['nmfc_number'] = $en_nmfc_number;
        return $item;
    }

    /**
     * Get product shippable unit enabled
     */
    public function en_nmfc_number($product_object, $product_detail)
    {
        $post_id = (isset($product_object['variation_id']) && $product_object['variation_id'] > 0) ? $product_object['variation_id'] : $product_detail->get_id();
        return get_post_meta($post_id, '_nmfc_number', true);
    }

    /**
     * Returns flat rate price and quantity
     */
    function en_get_flat_rate_price($values, $_product)
    {
        if ($_product->get_type() == 'variation') {
            $flat_rate_price = get_post_meta($values['variation_id'], 'en_flat_rate_price', true);
            if (strlen($flat_rate_price) < 1) {
                $flat_rate_price = get_post_meta($values['product_id'], 'en_flat_rate_price', true);
            }
        } else {
            $flat_rate_price = get_post_meta($_product->get_id(), 'en_flat_rate_price', true);
        }

        return $flat_rate_price;
    }

    /**
    * Returns product level markup
    */
    function rl_ltl_get_product_level_markup($_product, $variation_id, $product_id, $quantity)
    {
        $product_level_markup = 0;
        if ($_product->get_type() == 'variation') {
            $product_level_markup = get_post_meta($variation_id, '_en_product_markup_variation', true);
            if(empty($product_level_markup) || $product_level_markup == 'get_parent'){
                $product_level_markup = get_post_meta($_product->get_id(), '_en_product_markup', true);
            }
        } else {
            $product_level_markup = get_post_meta($_product->get_id(), '_en_product_markup', true);
        }
        if(empty($product_level_markup)) {
            $product_level_markup = get_post_meta($product_id, '_en_product_markup', true);
        }
        if(!empty($product_level_markup) && strpos($product_level_markup, '%') === false 
        && is_numeric($product_level_markup) && is_numeric($quantity))
        {
            $product_level_markup *= $quantity;
        } else if(!empty($product_level_markup) && strpos($product_level_markup, '%') > 0 && is_numeric($quantity)){
            $position = strpos($product_level_markup, '%');
            $first_str = substr($product_level_markup, $position);
            $arr = explode($first_str, $product_level_markup);
            $percentage_value = $arr[0];
            $product_price = $_product->get_price();
 
            if (!empty($product_price)) {
                $product_level_markup = $percentage_value / 100 * ($product_price * $quantity);
            } else {
                $product_level_markup = 0;
            }
         }
 
        return $product_level_markup;
    }
}