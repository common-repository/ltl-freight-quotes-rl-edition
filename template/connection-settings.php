<?php
/**
 * R+L Connection Settings Tab Class
 * @package     Woocommerce R+L Edition
 * @author      <https://eniture.com/>
 * @copyright   Copyright (c) 2017, Eniture
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * R+L Connection Settings Tab Class
 */
class RNL_Connection_Settings
{
    /**
     * Connection Settings Fields
     * @return array
     */
    public function rnl_con_setting()
    {
        echo '<div class="connection_section_class_rnl">';
        $settings = array(
            'section_title_rnl' => array(
                'name' => __('', 'woocommerce-settings-rnl_quotes'),
                'type' => 'title',
                'desc' => '<br> ',
                'id' => 'wc_settings_rnl_title_section_connection',
            ),

            'username_rnl' => array(
                'name' => __('Username ', 'woocommerce-settings-rnl_quotes'),
                'type' => 'text',
                'desc' => __('', 'woocommerce-settings-rnl_quotes'),
                'id' => 'wc_settings_rnl_username'
            ),

            'password_rnl' => array(
                'name' => __('Password ', 'woocommerce-settings-rnl_quotes'),
                'type' => 'text',
                'desc' => __('', 'woocommerce-settings-rnl_quotes'),
                'id' => 'wc_settings_rnl_password'
            ),

            'api_key_rnl' => array(
                'name' => __('API Key ', 'woocommerce-settings-rnl_quotes'),
                'type' => 'text',
                'desc' => __('', 'woocommerce-settings-rnl_quotes'),
                'id' => 'wc_settings_rnl_api_key'
            ),
            'plugin_licence_key_rnl' => array(
                'name' => __('Eniture API Key ', 'woocommerce-settings-rnl_quotes'),
                'type' => 'text',
                'desc' => __('Obtain a Eniture API Key from <a href="https://eniture.com/woocommerce-rnl-ltl-freight/" target="_blank" >eniture.com </a>', 'woocommerce-settings-rnl_quotes'),
                'id' => 'wc_settings_rnl_plugin_licence_key'
            ),
            'section_end_rnl' => array(
                'type' => 'sectionend',
                'id' => 'wc_settings_rnl_plugin_licence_key'
            ),
        );
        return $settings;
    }
}