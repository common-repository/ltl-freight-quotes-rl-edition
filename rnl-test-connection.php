<?php
/**
 * R+L WooComerce Test connection AJAX Request
 * @package     Woocommerce R+L Edition
 * @author      <https://eniture.com/>
 * @copyright   Copyright (c) 2017, Eniture
 */
if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_nopriv_rnl_action', 'rnl_test_submit');
add_action('wp_ajax_rnl_action', 'rnl_test_submit');
/**
 * R+L Test connection AJAX Request
 */
function rnl_test_submit()
{
    $domain = rnl_quotes_get_domain();
    $data = array(
        'licence_key' => (isset($_POST['rnl_plugin_license'])) ? sanitize_text_field($_POST['rnl_plugin_license']) : "",
        'sever_name' => $domain,
        'carrierName' => 'rnl',
        'plateform' => 'WordPress',
        'carrier_mode' => 'test',
        'ApiVersion' => '2.0',
        'UserName' => (isset($_POST['rnl_username'])) ? sanitize_text_field($_POST['rnl_username']) : "",
        'Password' => (isset($_POST['rnl_password'])) ? sanitize_text_field($_POST['rnl_password']) : "",
        'APIKey' => (isset($_POST['rnl_api_key'])) ? sanitize_text_field($_POST['rnl_api_key']) : ""
    );
    $url = RNL_FREIGHT_DOMAIN_HITTING_URL . '/index.php';
    $field_string = http_build_query($data);

    $response = wp_remote_post($url,
        array(
            'method' => 'POST',
            'timeout' => 60,
            'redirection' => 5,
            'blocking' => true,
            'body' => $field_string,
        )
    );

    $response = wp_remote_retrieve_body($response);
    $sResponseData = json_decode($response);
    $severity = isset($sResponseData->severity) ? $sResponseData->severity :  '';

    if ($severity == 'SUCCESS') {
        $sResult = array('message' => "success");
    } elseif ($severity == 'ERROR' || $sResponseData->error) {
        $test_error = isset($sResponseData->error) ? $sResponseData->error : $sResponseData->Message;
        $sResult = array('message' => $test_error);
    } else {
        $sResult = array('message' => "Failure try again later.");
    }
    echo json_encode($sResult);
    exit();
}
