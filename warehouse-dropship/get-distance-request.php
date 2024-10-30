<?php
/**
 * R+L LTL Distance Get
 *
 * @package     R+L Quotes
 * @author      Eniture-Technology
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Get_rnl_quotes_distance
 */
class Get_rnl_quotes_distance
{
    /**
     * Get Distance Function
     * @param $map_address
     * @param $accessLevel
     * @return json
     */
    function rnl_quotes_get_distance($map_address, $accessLevel, $destinationZip = array())
    {

        $domain = rnl_quotes_get_domain();
        $post = array(
            'acessLevel' => $accessLevel,
            'address' => $map_address,
            'originAddresses' => (isset($map_address)) ? $map_address : "",
            'destinationAddress' => (isset($destinationZip)) ? $destinationZip : "",
            'eniureLicenceKey' => get_option('wc_settings_rnl_plugin_licence_key'),
            'ServerName' => $domain,
        );

        if (is_array($post) && count($post) > 0) {

            $ltl_curl_obj = new RNL_Curl_Request();
            $output = $ltl_curl_obj->rnl_get_curl_response(RNL_FREIGHT_DOMAIN_HITTING_URL . '/addon/google-location.php', $post);
            return $output;
        }
    }
}
