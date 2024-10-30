<?php
/**
 * R+L Create warehouse database table
 * @package     Woocommerce R+L Edition
 * @author      <https://eniture.com/>
 * @copyright   Copyright (c) 2017, Eniture
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create warehouse database table
 * @global $wpdb
 */
function create_rnl_wh_db($network_wide = null)
{
    if ( is_multisite() && $network_wide ) {
        global $wpdb;
        foreach (get_sites(['fields'=>'ids']) as $blog_id) {
            switch_to_blog($blog_id);
            $warehouse_table = $wpdb->prefix . "warehouse";
            if ($wpdb->query("SHOW TABLES LIKE '" . $warehouse_table . "'") === 0) {
                $origin = 'CREATE TABLE ' . $warehouse_table . '(
                        id mediumint(9) NOT NULL AUTO_INCREMENT,
                        city varchar(200) NOT NULL,
                        state varchar(200) NOT NULL,
                        address varchar(255) NOT NULL,
                        phone_instore varchar(255) NOT NULL,
                        zip varchar(200) NOT NULL,
                        country varchar(200) NOT NULL,
                        location varchar(200) NOT NULL,
                        nickname varchar(200) NOT NULL,    
                        enable_store_pickup VARCHAR(255) NOT NULL,
                        miles_store_pickup VARCHAR(255) NOT NULL ,
                        match_postal_store_pickup VARCHAR(255) NOT NULL ,
                        checkout_desc_store_pickup VARCHAR(255) NOT NULL ,
                        enable_local_delivery VARCHAR(255) NOT NULL ,
                        miles_local_delivery VARCHAR(255) NOT NULL ,
                        match_postal_local_delivery VARCHAR(255) NOT NULL ,
                        checkout_desc_local_delivery VARCHAR(255) NOT NULL ,
                        fee_local_delivery VARCHAR(255) NOT NULL ,
                        suppress_local_delivery VARCHAR(255) NOT NULL,
                        origin_markup VARCHAR(255),                    
                        PRIMARY KEY  (id) )';
                dbDelta($origin);
            }

            $myCustomer = $wpdb->get_row("SHOW COLUMNS FROM " . $warehouse_table . " LIKE 'enable_store_pickup'");
            if (!(isset($myCustomer->Field) && $myCustomer->Field == 'enable_store_pickup')) {

                $wpdb->query(sprintf("ALTER TABLE %s ADD COLUMN enable_store_pickup VARCHAR(255) NOT NULL , "
                    . "ADD COLUMN miles_store_pickup VARCHAR(255) NOT NULL , "
                    . "ADD COLUMN match_postal_store_pickup VARCHAR(255) NOT NULL , "
                    . "ADD COLUMN checkout_desc_store_pickup VARCHAR(255) NOT NULL , "
                    . "ADD COLUMN enable_local_delivery VARCHAR(255) NOT NULL , "
                    . "ADD COLUMN miles_local_delivery VARCHAR(255) NOT NULL , "
                    . "ADD COLUMN match_postal_local_delivery VARCHAR(255) NOT NULL , "
                    . "ADD COLUMN checkout_desc_local_delivery VARCHAR(255) NOT NULL , "
                    . "ADD COLUMN fee_local_delivery VARCHAR(255) NOT NULL , "
                    . "ADD COLUMN suppress_local_delivery VARCHAR(255) NOT NULL", $warehouse_table));

            }

            $rl_origin_markup = $wpdb->get_row("SHOW COLUMNS FROM " . $warehouse_table . " LIKE 'origin_markup'");
            if (!(isset($rl_origin_markup->Field) && $rl_origin_markup->Field == 'origin_markup')) {
                $wpdb->query(sprintf("ALTER TABLE %s ADD COLUMN origin_markup VARCHAR(255) NOT NULL", $warehouse_table));
            }    

            // Origin terminal address
            rl_freight_update_warehouse();
            add_option('rnl_db_version', '1.0');
            restore_current_blog();
        }

    } else {
        global $wpdb;
        $warehouse_table = $wpdb->prefix . "warehouse";
        if ($wpdb->query("SHOW TABLES LIKE '" . $warehouse_table . "'") === 0) {
            $origin = 'CREATE TABLE ' . $warehouse_table . '(
                        id mediumint(9) NOT NULL AUTO_INCREMENT,
                        city varchar(200) NOT NULL,
                        state varchar(200) NOT NULL,
                        address varchar(255) NOT NULL,
                        phone_instore varchar(255) NOT NULL,
                        zip varchar(200) NOT NULL,
                        country varchar(200) NOT NULL,
                        location varchar(200) NOT NULL,
                        nickname varchar(200) NOT NULL,    
                        enable_store_pickup VARCHAR(255) NOT NULL,
                        miles_store_pickup VARCHAR(255) NOT NULL ,
                        match_postal_store_pickup VARCHAR(255) NOT NULL ,
                        checkout_desc_store_pickup VARCHAR(255) NOT NULL ,
                        enable_local_delivery VARCHAR(255) NOT NULL ,
                        miles_local_delivery VARCHAR(255) NOT NULL ,
                        match_postal_local_delivery VARCHAR(255) NOT NULL ,
                        checkout_desc_local_delivery VARCHAR(255) NOT NULL ,
                        fee_local_delivery VARCHAR(255) NOT NULL ,
                        suppress_local_delivery VARCHAR(255) NOT NULL,
                        origin_markup VARCHAR(255),                    
                        PRIMARY KEY  (id) )';
            dbDelta($origin);
        }

        $myCustomer = $wpdb->get_row("SHOW COLUMNS FROM " . $warehouse_table . " LIKE 'enable_store_pickup'");
        if (!(isset($myCustomer->Field) && $myCustomer->Field == 'enable_store_pickup')) {

            $wpdb->query(sprintf("ALTER TABLE %s ADD COLUMN enable_store_pickup VARCHAR(255) NOT NULL , "
                . "ADD COLUMN miles_store_pickup VARCHAR(255) NOT NULL , "
                . "ADD COLUMN match_postal_store_pickup VARCHAR(255) NOT NULL , "
                . "ADD COLUMN checkout_desc_store_pickup VARCHAR(255) NOT NULL , "
                . "ADD COLUMN enable_local_delivery VARCHAR(255) NOT NULL , "
                . "ADD COLUMN miles_local_delivery VARCHAR(255) NOT NULL , "
                . "ADD COLUMN match_postal_local_delivery VARCHAR(255) NOT NULL , "
                . "ADD COLUMN checkout_desc_local_delivery VARCHAR(255) NOT NULL , "
                . "ADD COLUMN fee_local_delivery VARCHAR(255) NOT NULL , "
                . "ADD COLUMN suppress_local_delivery VARCHAR(255) NOT NULL", $warehouse_table));

        }

        $rl_origin_markup = $wpdb->get_row("SHOW COLUMNS FROM " . $warehouse_table . " LIKE 'origin_markup'");
        if (!(isset($rl_origin_markup->Field) && $rl_origin_markup->Field == 'origin_markup')) {
            $wpdb->query(sprintf("ALTER TABLE %s ADD COLUMN origin_markup VARCHAR(255) NOT NULL", $warehouse_table));
        }   
            
        // Origin terminal address
        rl_freight_update_warehouse();
        add_option('rnl_db_version', '1.0');
    }

}
/**
 * Update warehouse
 */
function rl_freight_update_warehouse()
{
    // Origin terminal address
    global $wpdb;
    $warehouse_table = $wpdb->prefix . "warehouse";
    $warehouse_address = $wpdb->get_row("SHOW COLUMNS FROM " . $warehouse_table . " LIKE 'phone_instore'");
    if (!(isset($warehouse_address->Field) && $warehouse_address->Field == 'phone_instore')) {
        $wpdb->query(sprintf("ALTER TABLE %s ADD COLUMN address VARCHAR(255) NOT NULL", $warehouse_table));
        // Terminal phone number
        $wpdb->query(sprintf("ALTER TABLE %s ADD COLUMN phone_instore VARCHAR(255) NOT NULL", $warehouse_table));
    }
}
/**
 * Create LTL Freight Class
 */
function rnl_ltl_freight_class($network_wide = null)
{
    if ( is_multisite() && $network_wide ) {

        foreach (get_sites(['fields'=>'ids']) as $blog_id) {
            switch_to_blog($blog_id);
            if (!function_exists('create_ltl_class')) {
                wp_insert_term(
                    'LTL Freight', 'product_shipping_class', array(
                        'description' => 'The plugin is triggered to provide an LTL freight quote when the shopping cart contains an item that has a designated shipping class. Shipping class? is a standard WooCommerce parameter not to be confused with freight class? or the NMFC classification system.',
                        'slug' => 'ltl_freight'
                    )
                );
            }
            restore_current_blog();
        }

    } else {
        if (!function_exists('create_ltl_class')) {
            wp_insert_term(
                'LTL Freight', 'product_shipping_class', array(
                    'description' => 'The plugin is triggered to provide an LTL freight quote when the shopping cart contains an item that has a designated shipping class. Shipping class? is a standard WooCommerce parameter not to be confused with freight class? or the NMFC classification system.',
                    'slug' => 'ltl_freight'
                )
            );
        }
    }
}

/**
 * Create plugin option
 */
function create_rnl_option($network_wide = null)
{
    if ( is_multisite() && $network_wide ) {

        foreach (get_sites(['fields'=>'ids']) as $blog_id) {
            switch_to_blog($blog_id);
            $eniture_plugins = get_option('EN_Plugins');

            if (!$eniture_plugins) {
                add_option('EN_Plugins', json_encode(array('rnl')));
            } else {
                $plugins_array = json_decode($eniture_plugins, true);
                if (!in_array('rnl', $plugins_array)) {
                    array_push($plugins_array, 'rnl');
                    update_option('EN_Plugins', json_encode($plugins_array));
                }
            }
            restore_current_blog();
        }

    } else {
        $eniture_plugins = get_option('EN_Plugins');

        if (!$eniture_plugins) {
            add_option('EN_Plugins', json_encode(array('rnl')));
        } else {
            $plugins_array = json_decode($eniture_plugins, true);
            if (!in_array('rnl', $plugins_array)) {
                array_push($plugins_array, 'rnl');
                update_option('EN_Plugins', json_encode($plugins_array));
            }
        }
    }
}

/**
 * Create shipping rules database table
 */
function create_rnl_shipping_rules_db($network_wide = null)
{
    if ( is_multisite() && $network_wide ) {

        foreach (get_sites(['fields'=>'ids']) as $blog_id) {
            switch_to_blog($blog_id);
            global $wpdb;
            $shipping_rules_table = $wpdb->prefix . "eniture_rnl_shipping_rules";

            if ($wpdb->query("SHOW TABLES LIKE '" . $shipping_rules_table . "'") === 0) {
                $query = 'CREATE TABLE ' . $shipping_rules_table . '(
                    id INT(10) NOT NULL AUTO_INCREMENT,
                    name VARCHAR(50) NOT NULL,
                    type VARCHAR(30) NOT NULL,
                    settings TEXT NULL,
                    is_active TINYINT(1) NOT NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id)
                )';

                dbDelta($query);
            } else {
                $query = 'SHOW COLUMNS FROM ' . $shipping_rules_table . ' LIKE "type"';
                $result = $wpdb->get_results($query);
                $query = count($result) == 0 ? 'ALTER TABLE ' . $shipping_rules_table . ' ADD COLUMN type VARCHAR(30) NOT NULL' : 'ALTER TABLE ' . $shipping_rules_table . ' MODIFY type VARCHAR(30) NOT NULL';
                $wpdb->query($query);
            }

            restore_current_blog();
        }
    } else {
        global $wpdb;
        $shipping_rules_table = $wpdb->prefix . "eniture_rnl_shipping_rules";

        if ($wpdb->query("SHOW TABLES LIKE '" . $shipping_rules_table . "'") === 0) {
            $query = 'CREATE TABLE ' . $shipping_rules_table . '(
                id INT(10) NOT NULL AUTO_INCREMENT,
                name VARCHAR(50) NOT NULL,
                type VARCHAR(30) NOT NULL,
                settings TEXT NULL,
                is_active TINYINT(1) NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id) 
            )';

            dbDelta($query);
        } else {
            $query = 'SHOW COLUMNS FROM ' . $shipping_rules_table . ' LIKE "type"';
            $result = $wpdb->get_results($query);
            $query = count($result) == 0 ? 'ALTER TABLE ' . $shipping_rules_table . ' ADD COLUMN type VARCHAR(30) NOT NULL' : 'ALTER TABLE ' . $shipping_rules_table . ' MODIFY type VARCHAR(30) NOT NULL';
            $wpdb->query($query);
        }
    }
}

/**
 * Remove plugin option
 */
function en_rnl_ltl_deactivate_plugin($network_wide = null)
{
    if ( is_multisite() && $network_wide ) {
        foreach (get_sites(['fields'=>'ids']) as $blog_id) {
            switch_to_blog($blog_id);
            $eniture_plugins = get_option('EN_Plugins');
            $plugins_array = json_decode($eniture_plugins, true);
            $plugins_array = !empty($plugins_array) && is_array($plugins_array) ? $plugins_array : array();
            $key = array_search('rnl', $plugins_array);
            if ($key !== false) {
                unset($plugins_array[$key]);
            }
            update_option('EN_Plugins', json_encode($plugins_array));
            restore_current_blog();
        }
    } else {
        $eniture_plugins = get_option('EN_Plugins');
        $plugins_array = json_decode($eniture_plugins, true);
        $plugins_array = !empty($plugins_array) && is_array($plugins_array) ? $plugins_array : array();
        $key = array_search('rnl', $plugins_array);
        if ($key !== false) {
            unset($plugins_array[$key]);
        }
        update_option('EN_Plugins', json_encode($plugins_array));
    }
}