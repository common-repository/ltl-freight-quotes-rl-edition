<?php
/**
 * R+L WooComerce Settings Tabs
 * @package     Woocommerce R+L Edition
 * @author      <https://eniture.com/>
 * @copyright   Copyright (c) 2017, Eniture
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * R+L WooComerce Settings Tabs Class
 */
class WC_Settings_RNL_Freight extends WC_Settings_Page
{
    /**
     * R+L WooComerce Settings Tabs Class Constructor
     */
    public function __construct()
    {
        $this->id = 'rnl_quotes';
        add_filter('woocommerce_settings_tabs_array', array($this, 'add_settings_tab'), 50);
        add_action('woocommerce_sections_' . $this->id, array($this, 'output_sections'));
        add_action('woocommerce_settings_' . $this->id, array($this, 'output'));
        add_action('woocommerce_settings_save_' . $this->id, array($this, 'save'));
    }

    /**
     * Tabs Settings Array
     * @param array $settings_tabs
     * @return array
     */
    public function add_settings_tab($settings_tabs)
    {
        $settings_tabs[$this->id] = __('R+L Freight', 'woocommerce-settings-rnl_quotes');
        return $settings_tabs;
    }

    /**
     * WooCommerce Tabs Titles
     * @return array
     */
    public function get_sections()
    {
        $sections = array(
            '' => __('Connection Settings', 'woocommerce-settings-rnl_quotes'),
            'section-1' => __('Quote Settings', 'woocommerce-settings-rnl_quotes'),
            'section-2' => __('Warehouses', 'woocommerce-settings-rnl_quotes'),
            'shipping-rules' => __('Shipping Rules', 'woocommerce-settings-rnl_quotes'),
            // fdo va
            'section-4' => __('FreightDesk Online', 'woocommerce-settings-rnl_quotes'),
            'section-5' => __('Validate Addresses', 'woocommerce-settings-rnl_quotes'),
            'section-3' => __('User Guide', 'woocommerce-settings-rnl_quotes')
        );

        // Logs data
        $enable_logs = get_option('enale_logs_rnl');
        if ($enable_logs == 'yes') {
            $sections['en-logs'] = 'Logs';
        }

        $sections = apply_filters('en_woo_addons_sections', $sections, en_woo_plugin_rnl_quotes);
        // Standard Packaging
        $sections = apply_filters('en_woo_pallet_addons_sections', $sections, en_woo_plugin_rnl_quotes);
        return apply_filters('woocommerce_get_sections_' . $this->id, $sections);
    }

    /**
     * R+L warehouse settings page
     */
    public function rnl_warehouse()
    {
        require_once 'warehouse-dropship/wild/warehouse/warehouse_template.php';
        require_once 'warehouse-dropship/wild/dropship/dropship_template.php';
    }

    /**
     * R+L user guide settings page
     */
    public function rnl_user_guide()
    {
        include_once('template/guide.php');
    }


    /**
     * R+L get settings array
     * @param $section
     * @return array
     */
    public function get_settings($section = null)
    {
        ob_start();
        switch ($section) {
            case 'section-0' :
                $settings = RNL_Connection_Settings::rnl_con_setting();
                break;
            case 'section-1':
                $rnl_quote_Settings = new RNL_Quote_Settings();
                $settings = $rnl_quote_Settings->rnl_quote_settings_tab();
                break;
            case 'shipping-rules':
                $this->shipping_rules_section();
                $settings = [];
                break;
            case 'section-2' :
                $this->rnl_warehouse();
                $settings = array();
                break;
            case 'section-3' :
                $this->rnl_user_guide();
                $settings = array();
                break;
            // fdo va
            case 'section-4' :
                $this->freightdesk_online_section();
                $settings = [];
                break;

            case 'section-5' :
                $this->validate_addresses_section();
                $settings = [];
                break;

            case 'en-logs' :
                $this->shipping_logs_section();
                $settings = [];
                break;

            default:
                $rnl_con_settings = new RNL_Connection_Settings();
                $settings = $rnl_con_settings->rnl_con_setting();
                break;
        }

        $settings = apply_filters('en_woo_addons_settings', $settings, $section, en_woo_plugin_rnl_quotes);
        // Standard Packaging
        $settings = apply_filters('en_woo_pallet_addons_settings', $settings, $section, en_woo_plugin_rnl_quotes);
        $settings = $this->avaibility_addon($settings);
        return apply_filters('woocommerce-settings-rnl_quotes', $settings, $section);
    }

    /**
     * avaibility_addon
     * @param array type $settings
     * @return array type
     */
    function avaibility_addon($settings)
    {
        if (is_plugin_active('residential-address-detection/residential-address-detection.php')) {
            unset($settings['avaibility_lift_gate']);
            unset($settings['avaibility_auto_residential']);
        }

        return $settings;
    }

    /**
     * R+L settings output
     * @global $current_section
     */
    public function output()
    {
        global $current_section;
        $settings = $this->get_settings($current_section);
        WC_Admin_Settings::output_fields($settings);
    }

    /**
     * R+L settings save
     * @global $current_section
     */
    public function save()
    {
        global $current_section;
        $settings = $this->get_settings($current_section);
        // Cuttoff Time
        if (isset($_POST['rnl_freight_order_cut_off_time']) && $_POST['rnl_freight_order_cut_off_time'] != '') {
            $time_24_format = $this->rnl_get_time_in_24_hours($_POST['rnl_freight_order_cut_off_time']);
            $_POST['rnl_freight_order_cut_off_time'] = $time_24_format;
        }

        $backup_rates_fields = ['rnl_backup_rates_fixed_rate', 'rnl_backup_rates_cart_price_percentage', 'rnl_backup_rates_weight_function'];
        foreach ($backup_rates_fields as $field) {
            if (isset($_POST[$field])) update_option($field, $_POST[$field]);
        }

        WC_Admin_Settings::save_fields($settings);
    }
    /**
     * Cuttoff Time
     * @param $timeStr
     * @return false|string
     */
    public function rnl_get_time_in_24_hours($timeStr)
    {
        $cutOffTime = explode(' ', $timeStr);
        $hours = $cutOffTime[0];
        $separator = $cutOffTime[1];
        $minutes = $cutOffTime[2];
        $meridiem = $cutOffTime[3];
        $cutOffTime = "{$hours}{$separator}{$minutes} $meridiem";
        return date("H:i", strtotime($cutOffTime));
    }
    // fdo va
    /**
     * FreightDesk Online section
     */
    public function freightdesk_online_section()
    {
        include_once plugin_dir_path(__FILE__) . 'fdo/freightdesk-online-section.php';
    }

    /**
     * Validate Addresses Section
     */
    public function validate_addresses_section()
    {
        include_once plugin_dir_path(__FILE__) . 'fdo/validate-addresses-section.php';
    }

    /**
     * Shipping Logs Section
    */
    public function shipping_logs_section()
    {
        include_once plugin_dir_path(__FILE__) . 'logs/en-logs.php';
    }

    public function shipping_rules_section() 
    {
        include_once plugin_dir_path(__FILE__) . 'shipping-rules/shipping-rules-template.php';
    }
}

return new WC_Settings_RNL_Freight();