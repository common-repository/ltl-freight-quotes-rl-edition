<?php
/**
 * R+L WooCommerce Class for new and old functions
 * @package     Woocommerce R+L Edition
 * @author      <https://eniture.com/>
 * @copyright   Copyright (c) 2017, Eniture
 */ 
if ( ! defined( 'ABSPATH' ) ) {
    exit; 
}

/**
 * R+L WooCommerce Class for new and old functions
 */
class RNL_Woo_Update_Changes 
{
    /**
     * WooCommerce Version Number
     * @var int 
     */
    public $WooVersion;

    /**
     * R+L WooCommerce Class for new and old functions
     */
    function __construct() 
    {
        if (!function_exists('get_plugins'))
           require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

        $plugin_folder     = get_plugins('/' . 'woocommerce');
        $plugin_file       = 'woocommerce.php';
        $this->WooVersion  = $plugin_folder[$plugin_file]['Version'];
    }

    /**
     * R+L WooCommerce functions for postcode
     * @return string
     */
    function rnl_postcode()
    { 
        $sPostCode = "";
        switch ($this->WooVersion) 
        {  
            case ($this->WooVersion <= '2.7'):
                $sPostCode = WC()->customer->get_postcode();
                break;
            case ($this->WooVersion >= '3.0'):
                $sPostCode = WC()->customer->get_billing_postcode();
                break;

            default:
                break;
        }
        return $sPostCode;
    }

    /**
     * R+L WooCommerce functions for state
     * @return string
     */
    function rnl_getState()
    { 
        $sState = "";
        switch ($this->WooVersion) 
        {  
            case ($this->WooVersion <= '2.7'):
                $sState = WC()->customer->get_state();
                break;
            case ($this->WooVersion >= '3.0'):
                $sState = WC()->customer->get_billing_state();
                break;

            default:
                break;
        }
        return $sState;
    }

    /**
     * R+L WooCommerce functions for city
     * @return string
     */
    function rnl_getCity()
    { 
        $sCity = "";
        switch ($this->WooVersion) 
        {  
            case ($this->WooVersion <= '2.7'):
                $sCity = WC()->customer->get_city();
                break;
            case ($this->WooVersion >= '3.0'):
                $sCity = WC()->customer->get_billing_city();
                break;

            default:
                break;
        }
        return $sCity;
    }

    /**
     * R+L WooCommerce functions for country
     * @return string
     */
    function rnl_getCountry()
    { 
        $sCountry = "";
        switch ($this->WooVersion) 
        {  
            case ($this->WooVersion <= '2.7'):
                $sCountry = WC()->customer->get_country();
                break;
            case ($this->WooVersion >= '3.0'):
                $sCountry = WC()->customer->get_billing_country();
                break;

            default:
                break;
        }
        return $sCountry;
    }
}