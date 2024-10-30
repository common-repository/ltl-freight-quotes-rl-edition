<?php

if (!defined("ABSPATH")) {
    exit();
}

if (!class_exists("Rnl_Quotes_Liftgate_As_Option")) {
    class Rnl_Quotes_Liftgate_As_Option
    {

        public $rnl_quotes_as_option;
        public $label_sfx_arr;

        public function __construct()
        {
            $this->rnl_quotes_as_option = get_option("rnl_quotes_liftgate_delivery_as_option");
            $this->label_sfx_arr = array();
        }

        /**
         * update request array when lift-gate as an option
         * @param array string $post_data
         * @return array string
         */
        public function rnl_quotes_update_carrier_service($post_data)
        {
            if (isset($this->rnl_quotes_as_option) && ($this->rnl_quotes_as_option == "yes")) {
                $post_data['liftGateAsAnOption'] = '1';
            }

            return $post_data;
        }

        /**
         * get surcharges from api response
         * @param array type $surcharges
         * @return array type
         */
        public function update_parse_rnl_quotes_output($surcharges)
        {
            $surcharge_amount = array();
            foreach ($surcharges as $key => $surcharge) {
                if (isset($surcharge->Type, $surcharge->Amount) && (is_string($surcharge->Type))) {
                    $surcharge_amt = (isset($surcharge->Amount) && (is_string($surcharge->Amount))) ? $this->getFloatPrice($surcharge->Amount) : 0;
                    $surcharge_amount[$surcharge->Type] = $surcharge_amt;

                    // Micro Warehouse
                    if ($surcharge->Type == 'RC') {
                        $surcharge_amount['residentialFee'] = $surcharge_amt;
                    } elseif ($surcharge->Type == 'LIFT') {
                        $surcharge_amount['liftgateFee'] = $surcharge_amt;
                    }
                }
            }

            return $surcharge_amount;
        }

        /**
         * update quotes
         * @param array type $rate
         * @return array type
         */
        public function update_rate_whn_as_option_rnl_quotes($rate)
        {
            if (isset($rate) && (!empty($rate))) {
                $rate = apply_filters("en_woo_addons_web_quotes", $rate, en_woo_plugin_rnl_quotes);

                $label_sufex = (isset($rate['label_sufex'])) ? $rate['label_sufex'] : array();
                $label_sufex = $this->label_R_rnl($label_sufex);
                $rate['label_sufex'] = $label_sufex;

                if (isset($this->rnl_quotes_as_option, $rate['grandTotalWdoutLiftGate']) &&
                    ($this->rnl_quotes_as_option == "yes") && ($rate['grandTotalWdoutLiftGate'] > 0)) {
                    $lift_resid_flag = get_option('en_woo_addons_liftgate_with_auto_residential');

                    if (isset($lift_resid_flag) &&
                        ($lift_resid_flag == "yes") &&
                        (in_array("R", $label_sufex))) {
                        return $rate;
                    }

                    $wdout_lft_gte = $rate;
                    $rate['append_label'] = " with lift gate delivery ";
                    (!empty($label_sufex)) ? array_push($rate['label_sufex'], "L") : $rate['label_sufex'] = array("L");
                    $wdout_lft_gte['cost'] = $wdout_lft_gte['grandTotalWdoutLiftGate'];
                    $wdout_lft_gte['id'] .= "_wdout_lft_gte";
                    ((!empty($label_sufex)) && (in_array("R", $wdout_lft_gte['label_sufex']))) ? $wdout_lft_gte['label_sufex'] = array("R") : $wdout_lft_gte['label_sufex'] = array();
                    $rate = array($rate, $wdout_lft_gte);
                }
            }

            return $rate;
        }

        /**
         * filter label from api response
         * @param array type $result
         * @return array type
         */
        public function filter_label_sufex_array_rnl_quotes($result)
        {
            $this->label_sfx_arr = array();
            $this->check_residential_status($result);
            (isset($result->residentialStatus) && ($result->residentialStatus == "r")) ? array_push($this->label_sfx_arr, "R") : "";
            (isset($result->liftGateStatus) && ($result->liftGateStatus == "l")) ? array_push($this->label_sfx_arr, "L") : "";
            get_option('rnl_limited_access_delivery') == "yes" || get_option('rnl_limited_access_delivery_as_option') == "yes" ? array_push($this->label_sfx_arr, "LA") : "";

            return array_unique($this->label_sfx_arr);
        }

        /**
         * check and update residential tatus
         * @param array type $result
         */
        public function check_residential_status($result)
        {
            $residential_detecion_flag = get_option("en_woo_addons_auto_residential_detecion_flag");
            $auto_renew_plan = get_option("auto_residential_delivery_plan_auto_renew");

            if (($auto_renew_plan == "disable") &&
                ($residential_detecion_flag == "yes") &&
                (isset($result->autoResidentialSubscriptionExpired)) &&
                ($result->autoResidentialSubscriptionExpired == 1)) {
                update_option("en_woo_addons_auto_residential_detecion_flag", "no");
            }
        }

        /**
         * check "R" in array
         * @param array type $label_sufex
         * @return array type
         */
        public function label_R_rnl($label_sufex)
        {
            if (get_option('wc_settings_rnl_residential') == 'yes' && (in_array("R", $label_sufex))) {
                $label_sufex = array_flip($label_sufex);
                unset($label_sufex['R']);
                $label_sufex = array_keys($label_sufex);

            }

            return $label_sufex;
        }

        /**
         * Get valid price
         * @param string type $priceStr
         * @return float type
         */
        public function getFloatPrice($priceStr)
        {
            $search = '$';
            $replace = '';
            $count = 0;
            return str_replace($search, $replace, $priceStr, $count);
        }
    }

}

