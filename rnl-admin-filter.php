<?php
/**
 * R+L WooComerce Admin Settings | Plugin Shipping Settings
 * @package     Woocommerce R+L Edition
 * @author      <https://eniture.com/>
 * @copyright   Copyright (c) 2017, Eniture
 */
if (!defined('ABSPATH')) {
    exit;
}

add_action('restrict_manage_posts', 'Rnl_admin_posts_filter_restrict_manage_posts');
add_filter('parse_query', 'Rnl_posts_filter');
/**
 * R+L Admin Posts Filter
 */
function Rnl_admin_posts_filter_restrict_manage_posts()
{
    $type = 'product';
    if (isset($_GET['post_type'])) {
        $type = $_GET['post_type'];
    }
    if ('product' == $type) {
        $values = array(
            'Missing Freight Class' => 'missing_freight_class',
            'Missing Dimensions' => 'missing_dimensions',
            'Missing Weight' => 'missing_weight',
            'Shipping Class' => 'shipping_class',
            'Product List' => 'product_list',
        );
        ?>
        <select name="wwe_filter">
            <option value=""><?php _e('Select All Products', 'wwe786'); ?></option>
            <?php
            $current_v = isset($_GET['wwe_filter']) ? $_GET['wwe_filter'] : '';

            foreach ($values as $label => $value) {
                printf(
                    '<option value="%s"%s>%s</option>', $value, $value == $current_v ? ' selected="selected"' : '', $label
                );
            }
            ?>
        </select>
        <?php
    }
}

/**
 * R+L POst Filter
 * @param $query
 * @global $wpdb
 * @global $pagenow
 */
function Rnl_posts_filter($query)
{
    global $pagenow;
    global $wpdb;
    $type = 'product';

    if (isset($_GET['post_type'])) {
        $type = $_GET['post_type'];
    }

    if ('product' == $type && is_admin() && $pagenow == 'edit.php' && isset($_GET['wwe_filter']) && $_GET['wwe_filter'] != '') {
        if ($_GET['wwe_filter'] == 'missing_freight_class') {
            $get_slug_id = get_term_by('slug', 'ltl_freight', 'product_shipping_class');
            $query->query_vars['tax_query'][] = array(
                'taxonomy' => 'product_shipping_class',
                'terms' => $get_slug_id,
                'operator' => 'NOT IN'
            );
        }

        if ($_GET['wwe_filter'] == 'missing_dimensions') {
            $query->query_vars['meta_key'] = '_length';
            $query->query_vars['meta_key'] = '_width';
            $query->query_vars['meta_key'] = '_height';
            $query->query_vars['meta_value'] = '1';
            $query->query_vars['meta_compare'] = '<';
        }

        if ($_GET['wwe_filter'] == 'missing_weight') {
            $query->query_vars['meta_key'] = '_weight';
            $query->query_vars['meta_value'] = '1';
            $query->query_vars['meta_compare'] = '<';
        }

        if ($_GET['wwe_filter'] == 'shipping_class') {

            $query->query_vars['meta_key'] = '_ltl_freight';
            $query->query_vars['meta_value'] = 'NULL';
            $query->query_vars['meta_compare'] = '!=';
        }

        if ($_GET['wwe_filter'] == 'product_list') {
            $get_slug_id = get_term_by('slug', 'ltl_freight', 'product_shipping_class');
            $args = array(
                'relation' => 'AND',
                'post_type' => 'product',
                'tax_query' => array(
                    array(
                        'taxonomy' => 'product_shipping_class',
                        'field' => 'slug',
                        'terms' => 'ltl_freight',
                        'operator' => 'IN'
                    )
                ),
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => '_ltl_freight',
                        'value' => 'NULL',
                        'compare' => '!='
                    ),
                    array(
                        'key' => '_length',
                        'value' => '0',
                        'compare' => '>'
                    ),
                    array(
                        'key' => '_width',
                        'value' => '0',
                        'compare' => '>'
                    ),
                    array(
                        'key' => '_height',
                        'value' => '0',
                        'compare' => '>'
                    ),
                    array(
                        'key' => '_weight',
                        'value' => '0',
                        'compare' => '>'
                    ),
                    array(
                        'key' => '_weight',
                        'value' => '0',
                        'compare' => '>'
                    )
                ),
            );
            $query->query_vars = $args;
        }
    }
}

/**
 * Add R+L Method In WooCommerce Method List
 * @param array $methods
 * @return string
 */
function add_rnl_logistics($methods)
{
    $methods['rnl'] = 'RNL_Freight_Shipping_Class';
    return $methods;
}

/**
 * Add WooCommerce Setting Tab For R+L
 * @param $settings
 * @return string
 */
function rnl_shipping_sections($settings)
{
    include('rnl-tab-class.php');
    return $settings;
}

/**
 * Hide Other Shipping Options
 * @param $available_methods
 * @return arrray/string
 */
function rnl_hide_shipping($available_methods)
{
    // flag to check if rates available for current plugin
    $plugin_rates_available = false;
    if (!empty($available_methods) && count($available_methods) > 0) {
        foreach ($available_methods as $value) {
            if (strpos($value->id, 'backup_rates') !== false) continue;

            if ($value->method_id == 'rnl' || strpos($value->id, 'rnl') !== false) {
                $plugin_rates_available = true;
                break;
            }
        }
    }

    // Remove backup Rates
    $plugin_rates = get_option('rnl_ltl_backup_rates_display') == 'no_plugin_rates' && $plugin_rates_available;
    $other_rates = (empty(get_option('rnl_ltl_backup_rates_display')) || get_option('rnl_ltl_backup_rates_display') == 'no_other_rates') && count($available_methods) > 1;
    if (get_option('enable_backup_rates_rnl_ltl') == 'yes' && ($plugin_rates || $other_rates)) {
        $backup_rate_id = 'rnl:backup_rates';
        foreach ($available_methods as $key => $value) {
            if (isset($value->id) && $value->id == $backup_rate_id) {
                unset($available_methods[$key]);
            }
        }
    }

    if (get_option('wc_settings_rnl_allow_other_plugins') == 'no'
        && count($available_methods) > 0) {
        $plugins_array = array();
        $eniture_plugins = get_option('EN_Plugins');
        if ($eniture_plugins) {
            $plugins_array = json_decode($eniture_plugins, true);
        }

        // add methods which not exist in array
        $plugins_array[] = 'ltl_shipping_method';
        $plugins_array[] = 'daylight';
        $plugins_array[] = 'tql';
        $plugins_array[] = 'unishepper_small';
        $plugins_array[] = 'usps';

        if ($plugin_rates_available) {
            foreach ($available_methods as $index => $method) {
                if (!in_array($method->method_id, $plugins_array)) {
                    unset($available_methods[$index]);
                }
            }
        }
    }
    return $available_methods;
}

/**
 * Filter For CSV Import
 */
if (!function_exists('en_import_dropship_location_csv')) {

    /**
     * Import drop ship location CSV
     * @param $data
     * @param $this
     * @return array
     */
    function en_import_dropship_location_csv($data, $parseData)
    {
        $_product_freight_class = $_product_freight_class_variation = '';
        $_dropship_location = $locations = [];
        foreach ($data['meta_data'] as $key => $metaData) {
            $location = explode(',', trim($metaData['value']));
            switch ($metaData['key']) {
                // Update new columns
                case '_product_freight_class':
                    $_product_freight_class = trim($metaData['value']);
                    unset($data['meta_data'][$key]);
                    break;
                case '_product_freight_class_variation':
                    $_product_freight_class_variation = trim($metaData['value']);
                    unset($data['meta_data'][$key]);
                    break;
                case '_dropship_location_nickname':
                    $locations[0] = $location;
                    unset($data['meta_data'][$key]);
                    break;
                case '_dropship_location_zip_code':
                    $locations[1] = $location;
                    unset($data['meta_data'][$key]);
                    break;
                case '_dropship_location_city':
                    $locations[2] = $location;
                    unset($data['meta_data'][$key]);
                    break;
                case '_dropship_location_state':
                    $locations[3] = $location;
                    unset($data['meta_data'][$key]);
                    break;
                case '_dropship_location_country':
                    $locations[4] = $location;
                    unset($data['meta_data'][$key]);
                    break;
                case '_dropship_location':
                    $_dropship_location = $location;
            }
        }

        // Update new columns
        if (strlen($_product_freight_class) > 0) {
            $data['meta_data'][] = [
                'key' => '_ltl_freight',
                'value' => $_product_freight_class,
            ];
        }

        // Update new columns
        if (strlen($_product_freight_class_variation) > 0) {
            $data['meta_data'][] = [
                'key' => '_ltl_freight_variation',
                'value' => $_product_freight_class_variation,
            ];
        }

        if (!empty($locations) || !empty($_dropship_location)) {
            if (isset($locations[0]) && is_array($locations[0])) {
                foreach ($locations[0] as $key => $location_arr) {
                    $metaValue = [];
                    if (isset($locations[0][$key], $locations[1][$key], $locations[2][$key], $locations[3][$key])) {
                        $metaValue[0] = $locations[0][$key];
                        $metaValue[1] = $locations[1][$key];
                        $metaValue[2] = $locations[2][$key];
                        $metaValue[3] = $locations[3][$key];
                        $metaValue[4] = $locations[4][$key];
                        $dsId[] = en_serialize_dropship($metaValue);
                    }
                }
            } else {
                $dsId[] = en_serialize_dropship($_dropship_location);
            }

            $sereializedLocations = maybe_serialize($dsId);
            $data['meta_data'][] = [
                'key' => '_dropship_location',
                'value' => $sereializedLocations,
            ];
        }
        return $data;
    }

    add_filter('woocommerce_product_importer_parsed_data', 'en_import_dropship_location_csv', '99', '2');
}

/**
 * Serialize drop ship
 * @param $metaValue
 * @return string
 * @global $wpdb
 */

if (!function_exists('en_serialize_dropship')) {
    function en_serialize_dropship($metaValue)
    {
        global $wpdb;
        $dropship = (array)reset($wpdb->get_results(
            "SELECT id
                        FROM " . $wpdb->prefix . "warehouse WHERE nickname='$metaValue[0]' AND zip='$metaValue[1]' AND city='$metaValue[2]' AND state='$metaValue[3]' AND country='$metaValue[4]'"
        ));

        $dropship = array_map('intval', $dropship);

        if (empty($dropship['id'])) {
            $data = en_csv_import_dropship_data($metaValue);
            $wpdb->insert(
                $wpdb->prefix . 'warehouse', $data
            );

            $dsId = $wpdb->insert_id;
        } else {
            $dsId = $dropship['id'];
        }

        return $dsId;
    }
}

/**
 * Filtered Data Array
 * @param $metaValue
 * @return array
 */
if (!function_exists('en_csv_import_dropship_data')) {
    function en_csv_import_dropship_data($metaValue)
    {
        return array(
            'city' => $metaValue[2],
            'state' => $metaValue[3],
            'zip' => $metaValue[1],
            'country' => $metaValue[4],
            'location' => 'dropship',
            'nickname' => (isset($metaValue[0])) ? $metaValue[0] : "",
        );
    }
}