<?php
/**
 * Plugin Name:    LTL Freight Quotes - R+L Edition
 * Plugin URI:     https://eniture.com/products/
 * Description:    Dynamically retrieves your negotiated shipping rates from R+L Freight and displays the results in the WooCommerce shopping cart.
 * Version:        3.3.4
 * Author:         Eniture Technology
 * Author URI:     http://eniture.com/
 * Text Domain:    eniture-technology
 * License:        GPL version 2 or later - http://www.eniture.com/
 * WC requires at least: 6.4
 * WC tested up to: 9.3.1
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

define('RNL_FREIGHT_DOMAIN_HITTING_URL', 'https://ws034.eniture.com');
define('RNL_FDO_HITTING_URL', 'https://freightdesk.online/api/updatedWoocomData');
define('RNL_MAIN_FILE', __FILE__);

add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

// Define reference
function en_rl_freight_plugin($plugins)
{
    $plugins['lfq'] = (isset($plugins['lfq'])) ? array_merge($plugins['lfq'], ['rnl' => 'RNL_Freight_Shipping_Class']) : ['rnl' => 'RNL_Freight_Shipping_Class'];
    return $plugins;
}

add_filter('en_plugins', 'en_rl_freight_plugin');
/**
 * Product Detail Notification Messages.
 */
if (!function_exists('en_woo_plans_notification_PD')) {

    function en_woo_plans_notification_PD($product_detail_options)
    {
        $eniture_plugins_id = 'eniture_plugin_';

        for ($e = 1; $e <= 25; $e++) {
            $settings = get_option($eniture_plugins_id . $e);
            if (isset($settings) && (!empty($settings)) && (is_array($settings))) {
                $plugin_detail = current($settings);
                $plugin_name = (isset($plugin_detail['plugin_name'])) ? $plugin_detail['plugin_name'] : "";

                foreach ($plugin_detail as $key => $value) {
                    if ($key != 'plugin_name') {
                        $action = $value === 1 ? 'enable_plugins' : 'disable_plugins';
                        $product_detail_options[$key][$action] = (isset($product_detail_options[$key][$action]) && strlen($product_detail_options[$key][$action]) > 0) ? ", $plugin_name" : "$plugin_name";
                    }
                }
            }
        }

        return $product_detail_options;
    }

    add_filter('en_woo_plans_notification_action', 'en_woo_plans_notification_PD', 10, 1);
}

/**
 * Product Detail Old & New Plugin Plans Messages.
 */
if (!function_exists('en_woo_plans_notification_message')) {

    function en_woo_plans_notification_message($enable_plugins, $disable_plugins)
    {
        $enable_plugins = (strlen($enable_plugins) > 0) ? "$enable_plugins: <b> Enabled</b>. " : "";
        $disable_plugins = (strlen($disable_plugins) > 0) ? " $disable_plugins: Upgrade to <b>Standard Plan to enable</b>." : "";
        return $enable_plugins . "<br>" . $disable_plugins;
    }

    add_filter('en_woo_plans_notification_message_action', 'en_woo_plans_notification_message', 10, 2);
}

// Product detail set plans notification message for nested checkbox
if (!function_exists('en_woo_plans_nested_notification_message')) {

    function en_woo_plans_nested_notification_message($enable_plugins, $disable_plugins, $feature)
    {
        $enable_plugins = (strlen($enable_plugins) > 0) ? "$enable_plugins: <b> Enabled</b>. " : "";
        $disable_plugins = (strlen($disable_plugins) > 0 && $feature == 'nested_material') ? " $disable_plugins: Upgrade to <b>Advance Plan to enable</b>." : "";
        return $enable_plugins . "<br>" . $disable_plugins;
    }

    add_filter('en_woo_plans_nested_notification_message_action', 'en_woo_plans_nested_notification_message', 10, 3);
}

if (!function_exists('is_plugin_active')) {
    require_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

/**
 * Load scripts for Rnl Freight json tree view
 */
if (!function_exists('en_rnl_jtv_script')) {
    function en_rnl_jtv_script()
    {
        wp_register_style('en_rnl_json_tree_view_style', plugin_dir_url(__FILE__) . 'logs/en-json-tree-view/en-jtv-style.css');
        wp_register_script('en_rnl_json_tree_view_script', plugin_dir_url(__FILE__) . 'logs/en-json-tree-view/en-jtv-script.js', ['jquery'], '1.0.0');

        wp_enqueue_style('en_rnl_json_tree_view_style');
        wp_enqueue_script('en_rnl_json_tree_view_script', [
            'en_tree_view_url' => plugins_url(),
        ]);

        // Shipping rules script and styles
        wp_enqueue_script('en_rnl_sr_script', plugin_dir_url(__FILE__) . '/shipping-rules/assets/js/shipping_rules.js', array(), '1.0.1');
        wp_localize_script('en_rnl_sr_script', 'script', array(
            'pluginsUrl' => plugins_url(),
        ));
        wp_register_style('en_rnl_shipping_rules_section', plugin_dir_url(__FILE__) . '/shipping-rules/assets/css/shipping_rules.css', false, '1.0.0');
        wp_enqueue_style('en_rnl_shipping_rules_section');        
    }

    add_action('admin_init', 'en_rnl_jtv_script');
}

/**
 * Check woocommerce installation
 */
if (!is_plugin_active('woocommerce/woocommerce.php')) {
    add_action('admin_notices', 'rnl_wc_avaibility_error');
}

/**
 * Show error when woocommerce plugin is not activated
 */
function rnl_wc_avaibility_error()
{
    $class = "error";
    $message = "LTL Freight Quotes - R+L Edition is enabled, but not effective. It requires WooCommerce in order to work, Please <a target='_blank' href='https://wordpress.org/plugins/woocommerce/installation/'>Install</a> WooCommerce Plugin. Reactivate LTL Freight Quotes - R+L Edition plugin to create LTL shipping class.";
    echo "<div class=\"$class\"> <p>$message</p></div>";
}

add_action('admin_init', 'rnl_check_wc_version');

/**
 * Check WooCommerce version compatibility
 */
function rnl_check_wc_version()
{
    $woo_version = rnl_wc_version_number();
    $version = '2.6';
    if (!version_compare($woo_version, $version, ">=")) {
        add_action('admin_notices', 'wc_version_incompatibility_rnl');
    }
}

/**
 * Check WooCommerce version incompatibility
 */
function wc_version_incompatibility_rnl()
{
    ?>
    <div class="notice notice-error">
        <p>
            <?php
            _e('LTL Freight Quotes - R+L Edition plugin requires WooCommerce version 2.6 or higher to work. Functionality may not work properly.', 'wwe-woo-version-failure');
            ?>
        </p>
    </div>
    <?php
}

/**
 * Return WooCommerce version
 * @return string/int
 */
function rnl_wc_version_number()
{
    $plugin_folder = get_plugins('/' . 'woocommerce');
    $plugin_file = 'woocommerce.php';

    if (isset($plugin_folder[$plugin_file]['Version'])) {
        return $plugin_folder[$plugin_file]['Version'];
    } else {
        return NULL;
    }
}

/**
 * If Woocommerce activated on store
 */
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))) || is_plugin_active_for_network('woocommerce/woocommerce.php')) {
    /**
     * Admin Scripts
     */
    function rnl_admin_script()
    {
        // Cuttoff Time

        wp_register_style('rnl_wickedpicker_style', plugin_dir_url(__FILE__) . 'css/wickedpicker.min.css', false, '1.0.0');
        wp_register_script('rnl_wickedpicker_script', plugin_dir_url(__FILE__) . 'js/wickedpicker.js', false, '1.0.0');
        wp_enqueue_style('rnl_wickedpicker_style');

        wp_enqueue_script('rnl_wickedpicker_script');
        wp_register_style('rnl-style', plugin_dir_url(__FILE__) . '/css/rnl-style.css', false, '1.1.5');
        wp_enqueue_style('rnl-style');

        if(is_admin() && (!empty( $_GET['page']) && 'wc-orders' == $_GET['page'] ) && (!empty( $_GET['action']) && 'new' == $_GET['action'] ))
        {
            if (!wp_script_is('eniture_calculate_shipping_admin', 'enqueued')) {
                wp_enqueue_script('eniture_calculate_shipping_admin', plugin_dir_url(__FILE__) . 'js/eniture-calculate-shipping-admin.js', array(), '1.0.0' );
            }
        }
    }

    add_action('admin_enqueue_scripts', 'rnl_admin_script');

    add_action('admin_enqueue_scripts', 'en_rnl_script');

    /**
     * Load Front-end scripts for rnl
     */
    function en_rnl_script()
    {
        wp_enqueue_script('jquery');
        wp_enqueue_script('en_rnl_script', plugin_dir_url(__FILE__) . 'js/en-rnl.js', array(), '1.1.2');
        wp_localize_script('en_rnl_script', 'en_rnl_admin_script', array(
            'plugins_url' => plugins_url(),
            'allow_proceed_checkout_eniture' => trim(get_option("allow_proceed_checkout_eniture")),
            'prevent_proceed_checkout_eniture' => trim(get_option("prevent_proceed_checkout_eniture")),
            // Cuttoff Time
            'rnl_freight_order_cutoff_time' => get_option("rnl_freight_order_cut_off_time"),
            'rnl_backup_rates_fixed_rate' => get_option("rnl_backup_rates_fixed_rate"),
            'rnl_backup_rates_cart_price_percentage' => get_option("rnl_backup_rates_cart_price_percentage"),
            'rnl_backup_rates_weight_function' => get_option("rnl_backup_rates_weight_function"),
        ));
    }

    /*
     * Inlude Plugin Files
     */

    require_once 'fdo/en-fdo.php';
    require_once('warehouse-dropship/wild-delivery.php');
    require_once('warehouse-dropship/get-distance-request.php');
    require_once('standard-package-addon/standard-package-addon.php');
    require_once('update-plan.php');

    require_once('rnl-liftgate-as-option.php');
    require_once('rnl-test-connection.php');
    require_once('rnl-shipping-class.php');
    require_once('db/rnl-db.php');
    require_once('rnl-admin-filter.php');
    require_once 'template/products-nested-options.php';
    require_once 'order/en-order-export.php';
    require_once 'order/en-order-widget.php';
    require_once 'order/rates/order-rates.php';

    // Origin terminal address
    add_action('admin_init', 'rl_freight_update_warehouse');
    add_action('admin_init', 'create_rnl_shipping_rules_db');
    
    require_once('product/en-product-detail.php');
    require_once('shipping-rules/shipping-rules-save.php');

    require_once('rnl-group-package.php');
    require_once('rnl-carrier-service.php');
    require_once('template/connection-settings.php');
    require_once('template/quote-settings.php');
    require_once 'template/csv-export.php';
    require_once('rnl-wc-update-change.php');
    require_once('rnl-curl-class.php');
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    /*
     * R+L Freight Activation Hook
     */
    register_activation_hook(__FILE__, 'rnl_ltl_freight_class');
    register_activation_hook(__FILE__, 'create_rnl_wh_db');
    register_activation_hook(__FILE__, 'create_rnl_option');
    register_activation_hook(__FILE__, 'create_rnl_shipping_rules_db');

    register_activation_hook(__FILE__, 'rnl_quotes_activate_hit_to_update_plan');
    register_activation_hook(__FILE__, 'old_store_rnl_ltl_hazmat_status');
    register_activation_hook(__FILE__, 'old_store_rnl_ltl_dropship_status');
    register_deactivation_hook(__FILE__, 'rnl_quotes_deactivate_hit_to_update_plan');
    register_deactivation_hook(__FILE__, 'en_rnl_ltl_deactivate_plugin');


    /**
     * r+l plugin update now
     */
    function en_rl_update_now()
    {
        $index = 'ltl-freight-quotes-rl-edition/ltl-freight-quotes-rnl-edition.php';
        $plugin_info = get_plugins();
        $plugin_version = (isset($plugin_info[$index]['Version'])) ? $plugin_info[$index]['Version'] : '';
        $update_now = get_option('en_rl_update_now');

        if ($update_now != $plugin_version) {
            if (!function_exists('rnl_quotes_activate_hit_to_update_plan')) {
                require_once(__DIR__ . '/update-plan.php');
            }

            rnl_ltl_freight_class();
            create_rnl_wh_db();
            create_rnl_option();
            rnl_quotes_activate_hit_to_update_plan();
            old_store_rnl_ltl_hazmat_status();
            old_store_rnl_ltl_dropship_status();

            update_option('en_rl_update_now', $plugin_version);
        }
    }

    add_action('init', 'en_rl_update_now');


    /*
     * R+L Action And Filters
     */
    add_action('woocommerce_shipping_init', 'rnl_logistics_init');
    add_filter('woocommerce_shipping_methods', 'add_rnl_logistics');
    add_filter('woocommerce_get_settings_pages', 'rnl_shipping_sections');
    add_filter('woocommerce_package_rates', 'rnl_hide_shipping', 99);
    add_filter('plugin_action_links', 'rnl_logistics_add_action_plugin', 10, 5);

    /**
     * R+L action links
     * @staticvar $plugin
     * @param $actions
     * @param $plugin_file
     * @return string/array
     */
    function rnl_logistics_add_action_plugin($actions, $plugin_file)
    {
        static $plugin;
        if (!isset($plugin))
            $plugin = plugin_basename(__FILE__);
        if ($plugin == $plugin_file) {
            $settings = array('settings' => '<a href="admin.php?page=wc-settings&tab=rnl_quotes">' . __('Settings', 'General') . '</a>');
            $site_link = array('support' => '<a href="https://support.eniture.com" target="_blank">Support</a>');
            $actions = array_merge($settings, $actions);
            $actions = array_merge($site_link, $actions);
        }
        return $actions;
    }

    add_filter('woocommerce_cart_no_shipping_available_html', 'rnl_cart_html_message');

    /**
     * No Quotes Cart Message
     */
    function rnl_cart_html_message()
    {
        echo "<div><p>There are no shipping methods available. Please double check your address, or contact us if you need any help.</p></div>";
    }

}

define("en_woo_plugin_rnl_quotes", "rnl_quotes");

add_action('wp_enqueue_scripts', 'en_ltl_rnl_frontend_checkout_script');

/**
 * Load Frontend scripts for rnl
 */
function en_ltl_rnl_frontend_checkout_script()
{
    wp_enqueue_script('jquery');
    wp_enqueue_script('en_ltl_rnl_frontend_checkout_script', plugin_dir_url(__FILE__) . 'front/js/en-rnl-checkout.js', array(), '1.0.1');
    wp_localize_script('en_ltl_rnl_frontend_checkout_script', 'frontend_script', array(
        'pluginsUrl' => plugins_url(),
    ));
}

/**
 * Get Host
 * @param type $url
 * @return type
 */
if (!function_exists('getHost')) {

    function getHost($url)
    {
        $parseUrl = parse_url(trim($url));
        if (isset($parseUrl['host'])) {
            $host = $parseUrl['host'];
        } else {
            $path = explode('/', $parseUrl['path']);
            $host = $path[0];
        }
        return trim($host);
    }

}

/**
 * Get Domain Name
 */
if (!function_exists('rnl_quotes_get_domain')) {

    function rnl_quotes_get_domain()
    {
        global $wp;
        $url = home_url($wp->request);
        return getHost($url);
    }
}


/**
 * Plans Common Hooks
 */
add_filter('rnl_quotes_quotes_plans_suscription_and_features', 'rnl_quotes_quotes_plans_suscription_and_features', 1);

/**
 * Features with their plans
 * @param type $feature
 * @return Array/Boolean
 */
function rnl_quotes_quotes_plans_suscription_and_features($feature)
{
    $package = get_option('rnl_quotes_packages_quotes_package');

    $features = array
    (
        'instore_pickup_local_devlivery' => array('3'),
        'rnl_hold_at_terminal' => array('3'),
        'nested_material' => array('3'),
        // Cuttoff Time
        'rnl_cutt_off_time' => array('2', '3'),
    );

    if (get_option('rnl_quotes_store_type') == "1") {
        $features['multi_warehouse'] = array('2', '3');
        $features['multi_dropship'] = array('', '0', '1', '2', '3');
        $features['hazardous_material'] = array('2', '3');
    }

    if (get_option('en_old_user_dropship_status') == "0" && get_option('rnl_quotes_store_type') == "0") {
        $features['multi_dropship'] = array('', '0', '1', '2', '3');
    }

    if (get_option('en_old_user_warehouse_status') == "0" && get_option('rnl_quotes_store_type') == "0") {
        $features['multi_warehouse'] = array('2', '3');
    }

    if (get_option('en_old_user_hazmat_status') == "1" && get_option('rnl_quotes_store_type') == "0") {
        $features['hazardous_material'] = array('2', '3');
    }

    return (isset($features[$feature]) && (in_array($package, $features[$feature]))) ? TRUE : ((isset($features[$feature])) ? $features[$feature] : '');
}

add_filter('rnl_quotes_plans_notification_link', 'rnl_quotes_plans_notification_link', 1);

/**
 * Plan Notification URL To Redirect eniture.com
 * @param array $plans
 * @return string
 */
function rnl_quotes_plans_notification_link($plans)
{
    $plan = current($plans);
    $plan_to_upgrade = "";
    switch ($plan) {
        case 2:
            $plan_to_upgrade = "<a href='https://eniture.com/plan/woocommerce-rnl-ltl-freight/' class='plan_color' target='_blank'>Standard Plan required</a>";
            break;
        case 3:
            $plan_to_upgrade = "<a href='https://eniture.com/plan/woocommerce-rnl-ltl-freight/' target='_blank'>Advanced Plan required</a>";
            break;
    }

    return $plan_to_upgrade;
}

/**
 * old customer check dropship / warehouse status on plugin update
 */
function old_store_rnl_ltl_dropship_status()
{
    global $wpdb;

//  Check total no. of dropships on plugin updation
    $table_name = $wpdb->prefix . 'warehouse';
    $count_query = "select count(*) from $table_name where location = 'dropship' ";
    $num = $wpdb->get_var($count_query);

    if (get_option('en_old_user_dropship_status') == "0" && get_option('rnl_quotes_store_type') == "0") {
        $dropship_status = ($num > 1) ? 1 : 0;
        update_option('en_old_user_dropship_status', "$dropship_status");
    } elseif (get_option('en_old_user_dropship_status') == "" && get_option('rnl_quotes_store_type') == "0") {
        $dropship_status = ($num == 1) ? 0 : 1;
        update_option('en_old_user_dropship_status', "$dropship_status");
    }

//  Check total no. of warehouses on plugin updation
    $table_name = $wpdb->prefix . 'warehouse';
    $warehouse_count_query = "select count(*) from $table_name where location = 'warehouse' ";
    $warehouse_num = $wpdb->get_var($warehouse_count_query);

    if (get_option('en_old_user_warehouse_status') == "0" && get_option('rnl_quotes_store_type') == "0") {
        $warehouse_status = ($warehouse_num > 1) ? 1 : 0;
        update_option('en_old_user_warehouse_status', "$warehouse_status");
    } elseif (get_option('en_old_user_warehouse_status') == "" && get_option('rnl_quotes_store_type') == "0") {
        $warehouse_status = ($warehouse_num == 1) ? 0 : 1;
        update_option('en_old_user_warehouse_status', "$warehouse_status");
    }
}

/**
 * old customer check hazmat status on plugin update
 */
function old_store_rnl_ltl_hazmat_status()
{
    global $wpdb;
    $results = $wpdb->get_results("SELECT meta_key FROM {$wpdb->prefix}postmeta WHERE meta_key LIKE '_hazardousmaterials%' AND meta_value = 'yes'");

    if (get_option('en_old_user_hazmat_status') == "0" && get_option('rnl_quotes_store_type') == "0") {
        $hazmat_status = (count($results) > 0) ? 0 : 1;
        update_option('en_old_user_hazmat_status', "$hazmat_status");
    } elseif (get_option('en_old_user_hazmat_status') == "" && get_option('rnl_quotes_store_type') == "0") {
        $hazmat_status = (count($results) == 0) ? 1 : 0;
        update_option('en_old_user_hazmat_status', "$hazmat_status");
    }
}
// fdo va
add_action('wp_ajax_nopriv_rnl_fd', 'rnl_fd_api');
add_action('wp_ajax_rnl_fd', 'rnl_fd_api');
/**
 * UPS AJAX Request
 */
function rnl_fd_api()
{
    $store_name = rnl_quotes_get_domain();
    $company_id = $_POST['company_id'];
    $data = [
        'plateform'  => 'wp',
        'store_name' => $store_name,
        'company_id' => $company_id,
        'fd_section' => 'tab=rnl_quotes&section=section-4',
    ];
    if (is_array($data) && count($data) > 0) {
        if($_POST['disconnect'] != 'disconnect') {
            $url =  'https://freightdesk.online/validate-company';
        }else {
            $url = 'https://freightdesk.online/disconnect-woo-connection';
        }
        $response = wp_remote_post($url, [
                'method' => 'POST',
                'timeout' => 60,
                'redirection' => 5,
                'blocking' => true,
                'body' => $data,
            ]
        );
        $response = wp_remote_retrieve_body($response);
    }
    if($_POST['disconnect'] == 'disconnect') {
        $result = json_decode($response);
        if ($result->status == 'SUCCESS') {
            update_option('en_fdo_company_id_status', 0);
        }
    }
    echo $response;
    exit();
}
add_action('rest_api_init', 'en_rest_api_init_status_rnl');
function en_rest_api_init_status_rnl()
{
    register_rest_route('fdo-company-id', '/update-status', array(
        'methods' => 'POST',
        'callback' => 'en_rnl_fdo_data_status',
        'permission_callback' => '__return_true'
    ));
}

/**
 * Update FDO coupon data
 * @param array $request
 * @return array|void
 */
function en_rnl_fdo_data_status(WP_REST_Request $request)
{
    $status_data = $request->get_body();
    $status_data_decoded = json_decode($status_data);
    if (isset($status_data_decoded->connection_status)) {
        update_option('en_fdo_company_id_status', $status_data_decoded->connection_status);
        update_option('en_fdo_company_id', $status_data_decoded->fdo_company_id);
    }
    return true;
}

add_filter('en_suppress_parcel_rates_hook', 'supress_parcel_rates');
if (!function_exists('supress_parcel_rates')) {
    function supress_parcel_rates() {
        $exceedWeight = get_option('en_plugins_return_LTL_quotes') == 'yes';
        $supress_parcel_rates = get_option('en_suppress_parcel_rates') == 'suppress_parcel_rates';
        return ($exceedWeight && $supress_parcel_rates);
    }
}