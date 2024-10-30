<?php
/**
 * R+L Class For Quote Settings Tab
 * @package     Woocommerce R+L Edition
 * @author      <https://eniture.com/>
 * @copyright   Copyright (c) 2017, Eniture
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class For Quote Settings Tab
 */
class RNL_Quote_Settings
{

    /**
     * Quote Setting Fields
     * @return array Quote Setting Fields Array
     */
    function rnl_quote_settings_tab()
    {
        // Cuttoff Time
        $rnl_disable_cutt_off_time_ship_date_offset = "";
        $rnl_cutt_off_time_package_required = "";

        //  Check the cutt of time & offset days plans for disable input fields
        $rnl_action_cutOffTime_shipDateOffset = apply_filters('rnl_quotes_quotes_plans_suscription_and_features', 'rnl_cutt_off_time');
        if (is_array($rnl_action_cutOffTime_shipDateOffset)) {
            $rnl_disable_cutt_off_time_ship_date_offset = "disabled_me";
            $rnl_cutt_off_time_package_required = apply_filters('rnl_quotes_plans_notification_link', $rnl_action_cutOffTime_shipDateOffset);
        }

        $disable_hold_at_terminal = "";
        $hold_at_terminal_package_required = "";

        $action_hold_at_terminal = apply_filters('rnl_quotes_quotes_plans_suscription_and_features', 'rnl_hold_at_terminal');
        if (is_array($action_hold_at_terminal)) {
            $disable_hold_at_terminal = "disabled_me";
            $hold_at_terminal_package_required = apply_filters('rnl_quotes_plans_notification_link', $action_hold_at_terminal);
        }
        $ltl_enable = get_option('en_plugins_return_LTL_quotes');
        $weight_threshold_class = $ltl_enable == 'yes' ? 'show_en_weight_threshold_lfq' : 'hide_en_weight_threshold_lfq';
        $weight_threshold = get_option('en_weight_threshold_lfq');
        $weight_threshold = isset($weight_threshold) && $weight_threshold > 0 ? $weight_threshold : 150;

        $pallets_data = $this->getPalletsData();
        $label_as = get_option('wc_settings_rnl_label_as');
        $standard_service_check = get_option('standard_enable_service');
        if (!empty($label_as) && empty($standard_service_check)) {
            update_option('standard_enable_service', 'yes');
        }

        echo '<div class="quote_section_class_rnl">';
        $settings = array(
            'section_title_quote' => array(
                'title' => __('Quote Service Options', 'woocommerce-settings-rnl_quotes'),
                'type' => 'title',
                'desc' => '',
                'id' => 'wc_settings_rnl_section_title_quote'
            ),

            'enable_all_service' => array(
                'name' => __('Select All ', 'woocommerce-settings-rnl_quotes'),
                'type' => 'checkbox',
                'id' => 'enable_all_service',
                'class' => 'rnl_all_services'
            ),

            'standard_enable_service' => array(
                'name' => __('Standard Service ', 'woocommerce-settings-rnl_quotes'),
                'type' => 'checkbox',
                'id' => 'standard_enable_service',
                'class' => 'rnl_quotes_services_checkbox'
            ),
            'label_as_rnl' => array(
                'name' => __('', 'woocommerce-settings-rnl_quotes'),
                'type' => 'text',
                'desc' => '<span class="desc_text_style" >What the user sees during checkout, e.g. "Freight". Leave blank to display the carrier\'s name for the service.</span>',
                'id' => 'wc_settings_rnl_label_as'
            ),

            'guaranteed_pm_enable_service' => array(
                'name' => __('Guaranteed PM ', 'woocommerce-settings-rnl_quotes'),
                'type' => 'checkbox',
                'id' => 'guaranteed_pm_enable_service',
                'class' => 'rnl_quotes_services_checkbox'
            ),
            'guaranteed_pm_label_as' => array(
                'name' => __('', 'woocommerce-settings-rnl_quotes'),
                'type' => 'text',
                'desc' => '<span class="desc_text_style" >What the user sees during checkout, e.g. "Freight". Leave blank to display the carrier\'s name for the service.</span>',
                'id' => 'guaranteed_pm_label_as'
            ),

            'guaranteed_am_enable_service' => array(
                'name' => __('Guaranteed AM ', 'woocommerce-settings-rnl_quotes'),
                'type' => 'checkbox',
                'id' => 'guaranteed_am_enable_service',
                'class' => 'rnl_quotes_services_checkbox'
            ),
            'guaranteed_am_label_as' => array(
                'name' => __('', 'woocommerce-settings-rnl_quotes'),
                'type' => 'text',
                'desc' => '<span class="desc_text_style" >What the user sees during checkout, e.g. "Freight". Leave blank to display the carrier\'s name for the service.</span>',
                'id' => 'guaranteed_am_label_as'
            ),

            'guaranteed_hourly_enable_service' => array(
                'name' => __('Guaranteed Hourly Window ', 'woocommerce-settings-rnl_quotes'),
                'type' => 'checkbox',
                'id' => 'guaranteed_hourly_enable_service',
                'class' => 'rnl_quotes_services_checkbox'
            ),
            'guaranteed_hourly_label_as' => array(
                'name' => __('', 'woocommerce-settings-rnl_quotes'),
                'type' => 'text',
                'desc' => '<span class="desc_text_style" >What the user sees during checkout, e.g. "Freight". Leave blank to display the carrier\'s name for the service.</span>',
                'id' => 'guaranteed_hourly_label_as'
            ),

            'price_sort_rnl' => array(
                'name' => __("Don't sort shipping methods by price  ", 'woocommerce-settings-abf_quotes'),
                'type' => 'checkbox',
                'desc' => 'By default, the plugin will sort all shipping methods by price in ascending order.',
                'id' => 'shipping_methods_do_not_sort_by_price'
            ),

            //** Start Delivery Estimate Options - Cuttoff Time
            'service_rnl_estimates_title' => array(
                'name' => __('Delivery Estimate Options ', 'woocommerce-settings-en_woo_addons_packages_quotes'),
                'type' => 'text',
                'desc' => '',
                'id' => 'service_rnl_estimates_title'
            ),
            'rnl_show_delivery_estimates_options_radio' => array(
                'name' => __("", 'woocommerce-settings-rnl'),
                'type' => 'radio',
                'default' => 'dont_show_estimates',
                'options' => array(
                    'dont_show_estimates' => __("Don't display delivery estimates.", 'woocommerce'),
                    'delivery_days' => __("Display estimated number of days until delivery.", 'woocommerce'),
                    'delivery_date' => __("Display estimated delivery date.", 'woocommerce'),
                ),
                'id' => 'rnl_delivery_estimates',
                'class' => 'rnl_dont_show_estimate_option',
            ),

            // Pallet rates
            'en_rnl_pallet_rates' => array(
                'name' => __('Pallet Rates ', 'woocommerce-settings-en_woo_addons_packages_quotes'),
                'type' => 'text',
                'class' => 'hidden',
                'id' => 'en_rnl_pallet_rates'
            ),

            'en_rnl_pallets_dropdown' => array(
                'name' => __('I have pallet rates for the following pallet size: ', 'woocommerce-settings-rnl_quotes'),
                'type' => 'select',
                'default' => '0',
                'desc' => __('<span class="desc_text_style" >' . $pallets_data['desc'] . '</span>', 'woocommerce-settings-rnl_quotes'),
                'id' => 'en_rnl_pallets_dropdown',
                'options' => $pallets_data['pallets']
            ),

            'en_rnl_max_weight_per_pallet' => array(
                'name' => __('Maximum Weight per Pallet  ', 'woocommerce-settings-rnl_quotes'),
                'type' => 'text',
                'desc' => 'Enter in pounds the maximum weight that can be placed on the Pallet.',
                'id' => 'en_rnl_max_weight_per_pallet'
            ),

            //** End Delivery Estimate Options
            //**Start: Cut Off Time & Ship Date Offset
            'cutOffTime_shipDateOffset_rnl_freight' => array(
                'name' => __('Cut Off Time & Ship Date Offset ', 'woocommerce-settings-en_woo_addons_packages_quotes'),
                'type' => 'text',
                'class' => 'hidden',
                'desc' => $rnl_cutt_off_time_package_required,
                'id' => 'rnl_freight_cutt_off_time_ship_date_offset'
            ),
            'orderCutoffTime_rnl_freight' => array(
                'name' => __('Order Cut Off Time ', 'woocommerce-settings-rnl_freight_freight_orderCutoffTime'),
                'type' => 'text',
                'placeholder' => '-- : -- --',
                'desc' => 'Enter the cut off time (e.g. 2.00) for the orders. Orders placed after this time will be quoted as shipping the next business day.',
                'id' => 'rnl_freight_order_cut_off_time',
                'class' => $rnl_disable_cutt_off_time_ship_date_offset,
            ),
            'shipmentOffsetDays_rnl_freight' => array(
                'name' => __('Fullfillment Offset Days ', 'woocommerce-settings-rnl_freight_shipment_offset_days'),
                'type' => 'text',
                'desc' => 'The number of days the ship date needs to be moved to allow the processing of the order.',
                'placeholder' => 'Fullfillment Offset Days, e.g. 2',
                'id' => 'rnl_freight_shipment_offset_days',
                'class' => $rnl_disable_cutt_off_time_ship_date_offset,
            ),
            'all_shipment_days_rnl' => array(
                'name' => __("What days do you ship orders?", 'woocommerce-settings-rnl_quotes'),
                'type' => 'checkbox',
                'desc' => 'Select All',
                'class' => "all_shipment_days_rnl $rnl_disable_cutt_off_time_ship_date_offset",
                'id' => 'all_shipment_days_rnl'
            ),
            'monday_shipment_day_rnl' => array(
                'name' => __("", 'woocommerce-settings-rnl_quotes'),
                'type' => 'checkbox',
                'desc' => 'Monday',
                'class' => "rnl_shipment_day $rnl_disable_cutt_off_time_ship_date_offset",
                'id' => 'monday_shipment_day_rnl'
            ),
            'tuesday_shipment_day_rnl' => array(
                'name' => __("", 'woocommerce-settings-rnl_quotes'),
                'type' => 'checkbox',
                'desc' => 'Tuesday',
                'class' => "rnl_shipment_day $rnl_disable_cutt_off_time_ship_date_offset",
                'id' => 'tuesday_shipment_day_rnl'
            ),
            'wednesday_shipment_day_rnl' => array(
                'name' => __("", 'woocommerce-settings-rnl_quotes'),
                'type' => 'checkbox',
                'desc' => 'Wednesday',
                'class' => "rnl_shipment_day $rnl_disable_cutt_off_time_ship_date_offset",
                'id' => 'wednesday_shipment_day_rnl'
            ),
            'thursday_shipment_day_rnl' => array(
                'name' => __("", 'woocommerce-settings-rnl_quotes'),
                'type' => 'checkbox',
                'desc' => 'Thursday',
                'class' => "rnl_shipment_day $rnl_disable_cutt_off_time_ship_date_offset",
                'id' => 'thursday_shipment_day_rnl'
            ),
            'friday_shipment_day_rnl' => array(
                'name' => __("", 'woocommerce-settings-rnl_quotes'),
                'type' => 'checkbox',
                'desc' => 'Friday',
                'class' => "rnl_shipment_day $rnl_disable_cutt_off_time_ship_date_offset",
                'id' => 'friday_shipment_day_rnl'
            ),
            'rnl_show_delivery_estimates' => array(
                'title' => __('', 'woocommerce'),
                'name' => __('', 'woocommerce-settings-rnl_quotes'),
                'desc' => '',
                'id' => 'rnl_show_delivery_estimates',
                'css' => '',
                'default' => '',
                'type' => 'title',
            ),
            //**End: Cut Off Time & Ship Date Offset

            'accessorial_quoted_rnl' => array(
                'title' => __('', 'woocommerce'),
                'name' => __('', 'woocommerce-settings-rnl_quotes'),
                'desc' => '',
                'id' => 'woocommerce_accessorial_quoted_rnl',
                'css' => '',
                'default' => '',
                'type' => 'title',
            ),

            'residential_delivery_options_label' => array(
                'name' => __('Residential Delivery', 'woocommerce-settings-wwe_small_packages_quotes'),
                'type' => 'text',
                'class' => 'hidden',
                'id' => 'residential_delivery_options_label'
            ),

            'accessorial_residential_delivery_rnl' => array(
                'name' => __('Always quote as residential delivery ', 'woocommerce-settings-rnl_quotes'),
                'type' => 'checkbox',
                'desc' => __('', 'woocommerce-settings-rnl_quotes'),
                'id' => 'wc_settings_rnl_residential',
                'class' => 'accessorial_service rnlCheckboxClass',
            ),

//          Auto-detect residential addresses notification
            'avaibility_auto_residential' => array(
                'name' => __('Auto-detect residential addresses', 'woocommerce-settings-wwe_small_packages_quotes'),
                'type' => 'text',
                'class' => 'hidden',
                'desc' => "Click <a target='_blank' href='https://eniture.com/woocommerce-residential-address-detection/'>here</a> to add the Residential Address Detection module. (<a target='_blank' href='https://eniture.com/woocommerce-residential-address-detection/#documentation'>Learn more</a>)",
                'id' => 'avaibility_auto_residential'
            ),

            'liftgate_delivery_options_label' => array(
                'name' => __('Lift Gate Delivery ', 'woocommerce-settings-en_woo_addons_packages_quotes'),
                'type' => 'text',
                'class' => 'hidden',
                'id' => 'liftgate_delivery_options_label'
            ),

            'accessorial_liftgate_delivery_rnl' => array(
                'name' => __('Always quote lift gate delivery ', 'woocommerce-settings-rnl_quotes'),
                'type' => 'checkbox',
                'desc' => __('', 'woocommerce-settings-rnl_quotes'),
                'id' => 'wc_settings_rnl_liftgate',
                'class' => 'accessorial_service rnlCheckboxClass checkbox_fr_add',
            ),

            'rnl_quotes_liftgate_delivery_as_option' => array(
                'name' => __('Offer lift gate delivery as an option ', 'woocommerce-settings-xpo_quotes'),
                'type' => 'checkbox',
                'desc' => __('', 'woocommerce-settings-fedex_freight'),
                'id' => 'rnl_quotes_liftgate_delivery_as_option',
                'class' => 'accessorial_service checkbox_fr_add',
            ),

//          Use my liftgate notification
            'avaibility_lift_gate' => array(
                'name' => __('Always include lift gate delivery when a residential address is detected', 'woocommerce-settings-wwe_small_packages_quotes'),
                'type' => 'text',
                'class' => 'hidden',
                'desc' => "Click <a target='_blank' href='https://eniture.com/woocommerce-residential-address-detection/'>here</a> to add the Residential Address Detection module. (<a target='_blank' href='https://eniture.com/woocommerce-residential-address-detection/#documentation'>Learn more</a>)",
                'id' => 'avaibility_lift_gate'
            ),

            // Limited access delivery
            'rnl_limited_access_delivery_label' => array(
                'name' => __("Limited Access Delivery", 'woocommerce-settings-wwe_quetes'),
                'type' => 'text',
                'class' => 'hidden',
                'desc' => '',
                'id' => 'rnl_limited_access_delivery_label'
            ),
            'rnl_limited_access_delivery' => array(
                'name' => __("Always quote limited access delivery", 'woocommerce-settings-wwe_quetes'),
                'type' => 'checkbox',
                'id' => 'rnl_limited_access_delivery',
                'class' => "limited_access_add",
            ),
            'rnl_limited_access_delivery_as_option' => array(
                'name' => __("Offer limited access delivery as an option", 'woocommerce-settings-wwe_quetes'),
                'type' => 'checkbox',
                'id' => 'rnl_limited_access_delivery_as_option',
                'class' => "limited_access_add ",
            ),
            'rnl_limited_access_delivery_fee' => array(
                'name' => __("Limited access delivery fee", 'woocommerce-settings-wwe_quetes'),
                'type' => 'text',
                'id' => 'rnl_limited_access_delivery_fee',
                'class' => "",
            ),

//          Start Hot At Terminal

            'rnl_hold_at_terminal_checkbox_status' => array(
                'name' => __('Hold At Terminal', 'woocommerce-settings-fedex_small'),
                'type' => 'checkbox',
                'desc' => 'Offer Hold At Terminal as an option ' . $hold_at_terminal_package_required,
                'class' => $disable_hold_at_terminal,
                'id' => 'rnl_hold_at_terminal_checkbox_status',
            ),

            'rnl_hold_at_terminal_fee' => array(
                'name' => __('', 'ground-transit-settings-ground_transit'),
                'type' => 'text',
                'desc' => 'Adjust the price of the Hold At Terminal option.Enter an amount, e.g. 3.75, or a percentage, e.g. 5%.  Leave blank to use the price returned by the carrier.',
                'class' => $disable_hold_at_terminal,
                'id' => 'rnl_hold_at_terminal_fee'
            ),

//          End Hot At Terminal
            // Handling Weight
            'rnl_label_handling_unit' => array(
                'name' => __('Handling Unit ', 'rnl_freight_wc_settings'),
                'type' => 'text',
                'class' => 'hidden',
                'id' => 'rnl_label_handling_unit'
            ),
            'rnl_freight_handling_weight' => array(
                'name' => __('Weight of Handling Unit  ', 'rnl_freight_wc_settings'),
                'type' => 'text',
                'desc' => 'Enter in pounds the weight of your pallet, skid, crate or other type of handling unit.',
                'id' => 'rnl_freight_handling_weight'
            ),
            // max Handling Weight
            'rnl_freight_maximum_handling_weight' => array(
                'name' => __('Maximum Weight per Handling Unit  ', 'rnl_freight_wc_settings'),
                'type' => 'text',
                'desc' => 'Enter in pounds the maximum weight that can be placed on the handling unit.',
                'id' => 'rnl_freight_maximum_handling_weight'
            ),
            'handing_fee_markup_rnl' => array(
                'name' => __('Handling Fee / Mark Up ', 'woocommerce-settings-rnl_quotes'),
                'type' => 'text',
                'desc' => '<span class="desc_text_style" >Amount excluding tax. Enter an amount, e.g 3.75, or a percentage, e.g, 5%. Leave blank to disable.</span>',
                'id' => 'wc_settings_rnl_handling_fee'
            ),
            // Enale Logs
            'enale_logs_rnl' => array(
                'name' => __("Enable Logs  ", 'woocommerce_odfl_quote'),
                'type' => 'checkbox',
                'desc' => 'When checked, the Logs page will contain up to 25 of the most recent transactions.',
                'id' => 'enale_logs_rnl'
            ),
            //Ignore items with the following Shipping Class(es) By (K)
            'en_ignore_items_through_freight_classification' => array(
                'name' => __('Ignore items with the following Shipping Class(es)', 'woocommerce-settings-wwe_quetes'),
                'type' => 'text',
                'desc' => "Enter the <a target='_blank' href = '" . get_admin_url() . "admin.php?page=wc-settings&tab=shipping&section=classes'>Shipping Slug</a> you'd like the plugin to ignore. Use commas to separate multiple Shipping Slug.",
                'id' => 'en_ignore_items_through_freight_classification'
            ),
            'accessorial_quoted_rnl' => array(
                'title' => __('', 'woocommerce'),
                'name' => __('', 'woocommerce-settings-rnl_quotes'),
                'desc' => '',
                'id' => 'woocommerce_accessorial_quoted_rnl',
                'css' => '',
                'default' => '',
                'type' => 'title',
            ),

            'allow_other_plugins_rnl' => array(
                'name' => __('Show WooCommerce Shipping Options ', 'woocommerce-settings-rnl_quotes'),
                'type' => 'select',
                'default' => '3',
                'desc' => __('<span class="desc_text_style" >Enabled options on WooCommerce Shipping page are included in quote results.</span>', 'woocommerce-settings-rnl_quotes'),
                'id' => 'wc_settings_rnl_allow_other_plugins',
                'options' => array(
                    'yes' => __('YES', 'YES'),
                    'no' => __('NO', 'NO')
                )
            ),

            'return_RNL_quotes' => array(
                'name' => __("Return LTL quotes when an order parcel shipment weight exceeds the weight threshold ", 'woocommerce-settings-rnl_quetes'),
                'type' => 'checkbox',
                'desc' => '<span class="desc_text_style" >When checked, the LTL Freight Quote will return quotes when an order’s total weight exceeds the weight threshold (the maximum permitted by WWE and UPS), even if none of the products have settings to indicate that it will ship LTL Freight. To increase the accuracy of the returned quote(s), all products should have accurate weights and dimensions. </span>',
                'id' => 'en_plugins_return_LTL_quotes',
                'class' => 'rnlCheckboxClass'
            ),
            // Weight threshold for LTL freight
            'en_weight_threshold_lfq' => [
                'name' => __('Weight threshold for LTL Freight Quotes  ', 'woocommerce-settings-rnl_quetes'),
                'type' => 'text',
                'default' => $weight_threshold,
                'class' => $weight_threshold_class,
                'id' => 'en_weight_threshold_lfq'
            ],
            'en_suppress_parcel_rates' => array(
                'name' => __("", 'woocommerce-settings-rnl_quetes'),
                'type' => 'radio',
                'default' => 'display_parcel_rates',
                'options' => array(
                    'display_parcel_rates' => __("Continue to display parcel rates when the weight threshold is met.", 'woocommerce'),
                    'suppress_parcel_rates' => __("Suppress parcel rates when the weight threshold is met.", 'woocommerce'),
                ),
                'class' => 'en_suppress_parcel_rates',
                'id' => 'en_suppress_parcel_rates',
            ),
            // Error management
            'error_management_rnl_ltl' => array(
                'name' => __('Error management ', 'woocommerce-settings-rnl_quetes'),
                'type' => 'text',
                'id' => 'error_management_rnl_ltl',
                'class' => 'hidden',
            ),
            'error_management_settings_rnl_ltl' => array(
                'name' => __('', 'woocommerce-settings-rnl_quetes'),
                'type' => 'radio',
                'default' => 'quote_shipping',
                'options' => array(
                    'quote_shipping' => __('Quote shipping using known shipping parameters, even if other items are missing shipping parameters.', 'woocommerce'),
                    'dont_quote_shipping' => __('Don\'t quote shipping if one or more items are missing the required shipping parameters.', 'woocommerce'),
                ),
                'id' => 'error_management_settings_rnl_ltl',
            ),
            // Backup Rates
            'backup_rates_rnl_ltl' => array(
                'name' => __('Checkout options if the plugin fails to return a rate ', 'woocommerce-settings-rnl_quetes'),
                'type' => 'text',
                'class' => 'hidden',
                'desc' => __('', 'woocommerce-settings-rnl_quetes'),
                'id' => 'backup_rates_rnl_ltl'
            ),
            'enable_backup_rates_rnl_ltl' => array(
                'name' => __('', 'woocommerce-settings-rnl_quetes'),
                'type' => 'checkbox',
                'desc' => __('Present the user with a backup shipping rate.', 'woocommerce-settings-rnl_quetes'),
                'id' => 'enable_backup_rates_rnl_ltl',
            ),
            'rnl_ltl_backup_rates_label' => array(
                'name' => __('', 'woocommerce-settings-rnl_quetes'),
                'type' => 'text',
                'desc' => 'Label for backup shipping rate (Maximum of 50 characters).',
                'id' => 'rnl_ltl_backup_rates_label'
            ),
            'rnl_ltl_backup_rates_category' => array(
                'name' => __('', 'woocommerce-settings-rnl_quetes'),
                'type' => 'radio',
                'default' => 'fixed_rate',
                'options' => array(
                    'fixed_rate' => __('', 'woocommerce'),
                    'percentage_of_cart_price' => __('', 'woocommerce'),
                    'function_of_weight' => __('', 'woocommerce'),
                ),
                'id' => 'rnl_ltl_backup_rates_category',
            ),
            'rnl_ltl_backup_rates_carrier_fails_to_return_response' => array(
                'name' => __('', 'woocommerce-settings-rnl_quetes'),
                'type' => 'checkbox',
                'desc' => __('Display the backup rate if the carrier fails to return a response.', 'woocommerce-settings-rnl_quetes'),
                'id' => 'rnl_ltl_backup_rates_carrier_fails_to_return_response',
            ),
            'rnl_ltl_backup_rates_carrier_returns_error' => array(
                'name' => __('', 'woocommerce-settings-rnl_quetes'),
                'type' => 'checkbox',
                'desc' => __('Display the backup rate if the carrier returns an error.', 'woocommerce-settings-rnl_quetes'),
                'id' => 'rnl_ltl_backup_rates_carrier_returns_error',
            ),
            'rnl_ltl_backup_rates_display' => array(
                'name' => __('', 'woocommerce-settings-rnl_quetes'),
                'type' => 'radio',
                'default' => 'no_other_rates',
                'options' => array(
                    'no_plugin_rates' => __('Display the backup rate if the plugin fails to return a rate.', 'woocommerce'),
                    'no_other_rates' => __('Display the backup rate only if no rates, from any shipping method, are presented.', 'woocommerce'),
                ),
                'id' => 'rnl_ltl_backup_rates_display',
            ),
            'section_end_quote' => array(
                'type' => 'sectionend',
                'id' => 'wc_settings_quote_section_end'
            )
        );
        return $settings;
    }

    public function getPalletsData(){

        $response = [];
        $response['desc'] = '';

        $pallets_json = get_option('en_rnl_pallets_json');
        if(!empty($pallets_json)){
            $pallets_dec = json_decode($pallets_json);
            $response['pallets']['0'] = __('No Pallet Selected', 'No Pallet Selected');
            foreach($pallets_dec as $key => $value){
                $response['pallets'][$key] = __($value, $value);
            }
        }else{
            $api_key = get_option('wc_settings_rnl_api_key');
            $username = get_option('wc_settings_rnl_username');
            $password = get_option('wc_settings_rnl_password');
            if(!empty($api_key) && !empty($username) && !empty($password)){
                $url = RNL_FREIGHT_DOMAIN_HITTING_URL . '/index.php';
                $response = wp_remote_post( $url, array(
                    'method'      => 'POST',
                    'timeout'     => 30,
                    'headers'     => array(),
                    'body'        => array(
                        'licence_key' => get_option('wc_settings_rnl_plugin_licence_key'),
                        'sever_name' => rnl_quotes_get_domain(),
                        'carrierName' => 'rnl',
                        'plateform' => 'WordPress',
                        'carrier_mode' => 'getPallets',
                        'APIKey' => $api_key,
                        'UserName' => $username,
                        'Password' => $password,
                    )
                ));

                if ( is_wp_error( $response ) ) {
                    $response['pallets']['0'] = __('No Pallet Selected', 'No Pallet Selected');
                    $response['desc'] = 'Please verify your API credentials, if pallets are not listed.';
                } else {
                    $response_body = json_decode(wp_remote_retrieve_body($response));
                    if(isset($response_body->severity) && $response_body->severity == 'success' && isset($response_body->pallets)
                    && is_array($response_body->pallets) && count($response_body->pallets) > 0){
                        $pallets_dropdown_array = [];
                        $response['pallets']['0'] = __('No Pallet Selected', 'No Pallet Selected');
                        foreach($response_body->pallets as $value){
                            $pallets_dropdown_array[$value->Code] = $value->Description;
                            $response['pallets'][$value->Code] = __($value->Description, $value->Description);
                        }
                        update_option('en_rnl_pallets_json' , json_encode($pallets_dropdown_array));
                    }else{
                        $response['pallets']['0'] = __('No Pallet Selected', 'No Pallet Selected');
                        $response['desc'] = 'Please verify your API credentials, if pallets are not listed.';
                    }
                }
            }else{
                $response['pallets']['0'] = __('Please save API credentials first', 'Please save API credentials first');
                $response['desc'] = 'Please click on the test connection tab and save your API credentials first.';
            }
        } 

        return $response;

    }
}
