jQuery(document).ready(function () {

    jQuery( "label[for='rnl_freight_handling_weight']" ).addClass( 'rnl_freight_handling_weight' );
    jQuery( "label[for='rnl_freight_maximum_handling_weight']" ).addClass( 'rnl_freight_handling_weight' );
    jQuery("#rnl_freight_handling_weight").attr('maxlength','8');
    jQuery("#rnl_freight_maximum_handling_weight").attr('maxlength','8');
    jQuery("#wc_settings_rnl_handling_fee,#rnl_freight_handling_weight,#rnl_freight_maximum_handling_weight").keydown(function (e) {
        // Allow: backspace, delete, tab, escape, enter and .
        if (jQuery.inArray(e.keyCode, [46, 8, 9, 27, 13, 110, 190]) !== -1 ||
            // Allow: Ctrl+A, Command+A
            (e.keyCode === 65 && (e.ctrlKey === true || e.metaKey === true)) ||
            // Allow: home, end, left, right, down, up
            (e.keyCode >= 35 && e.keyCode <= 40)|| e.keyCode == 109) {
            // let it happen, don't do anything
            return;
        }
        if(e.target.id == 'wc_settings_rnl_handling_fee')
        {
            if (jQuery.inArray(e.keyCode, [53,189]) !== -1  ){
                return;
            }
        }
        // Ensure that it is a number and stop the keypress
        if ((e.keyCode === 190 || e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
            e.preventDefault();
        }

        if ((jQuery(this).val().indexOf('.') != -1) && (jQuery(this).val().substring(jQuery(this).val().indexOf('.'), jQuery(this).val().indexOf('.').length).length > 2)) {
            if (event.keyCode !== 8 && event.keyCode !== 46) { //exception
                event.preventDefault();
            }
        }

    });
    jQuery("#wc_settings_rnl_handling_fee,#rnl_freight_handling_weight,#rnl_freight_maximum_handling_weight").keyup(function (e) {

        var val = jQuery(this).val();

        if (val.split('.').length - 1 > 1) {
            var newval = val.substring(0, val.length - 1);
            var countDots = newval.substring(newval.indexOf('.') + 1).length;
            newval = newval.substring(0, val.length - countDots - 1);
            jQuery(this).val(newval);
        }

        if (val.split('%').length - 1 > 1) {
            var newval = val.substring(0, val.length - 1);
            var countPercentages = newval.substring(newval.indexOf('%') + 1).length;
            newval = newval.substring(0, val.length - countPercentages - 1);
            jQuery(this).val(newval);
        }
        if (val.split('>').length - 1 > 0) {
            var newval = val.substring(0, val.length - 1);
            var countGreaterThan = newval.substring(newval.indexOf('>') + 1).length;
            newval = newval.substring(newval, newval.length - countGreaterThan - 1);
            jQuery(this).val(newval);
        }
    });

    // Weight threshold for LTL freight
    en_weight_threshold_limit();

    // JS for edit product nested fields
    jQuery("._nestedMaterials").closest('p').addClass("_nestedMaterials_tr");
    jQuery("._nestedPercentage").closest('p').addClass("_nestedPercentage_tr");
    jQuery("._maxNestedItems").closest('p').addClass("_maxNestedItems_tr");
    jQuery("._nestedDimension").closest('p').addClass("_nestedDimension_tr");
    jQuery("._nestedStakingProperty").closest('p').addClass("_nestedStakingProperty_tr");
    // Cuttoff Time
    jQuery("#rnl_freight_shipment_offset_days").closest('tr').addClass("rnl_freight_shipment_offset_days_tr");
    jQuery("#all_shipment_days_rnl").closest('tr').addClass("all_shipment_days_rnl_tr");
    jQuery(".rnl_shipment_day").closest('tr').addClass("rnl_shipment_day_tr");
    jQuery("#rnl_freight_order_cut_off_time").closest('tr').addClass("rnl_freight_cutt_off_time_ship_date_offset");

    jQuery("#en_rnl_pallets_dropdown").closest('tr').addClass("en_rnl_pallets_dropdown_tr");
    jQuery("#en_rnl_max_weight_per_pallet").closest('tr').addClass("en_rnl_max_weight_per_pallet_tr");
    var rnl_current_time = en_rnl_admin_script.rnl_freight_order_cutoff_time;
    if (rnl_current_time == '') {

        jQuery('#rnl_freight_order_cut_off_time').wickedpicker({
            now: '',
            title: 'Cut Off Time',
        });
    } else {
        jQuery('#rnl_freight_order_cut_off_time').wickedpicker({

            now: rnl_current_time,
            title: 'Cut Off Time'
        });
    }

    var delivery_estimate_val = jQuery('input[name=rnl_delivery_estimates]:checked').val();
    if (delivery_estimate_val == 'dont_show_estimates') {
        jQuery("#rnl_freight_order_cut_off_time").prop('disabled', true);
        jQuery("#rnl_freight_shipment_offset_days").prop('disabled', true);
        jQuery("#rnl_freight_shipment_offset_days").css("cursor", "not-allowed");
        jQuery("#rnl_freight_order_cut_off_time").css("cursor", "not-allowed");
    } else {
        jQuery("#rnl_freight_order_cut_off_time").prop('disabled', false);
        jQuery("#rnl_freight_shipment_offset_days").prop('disabled', false);
        // jQuery("#rnl_freight_order_cut_off_time").css("cursor", "auto");
        jQuery("#rnl_freight_order_cut_off_time").css("cursor", "");
    }

    jQuery("input[name=rnl_delivery_estimates]").change(function () {
        var delivery_estimate_val = jQuery('input[name=rnl_delivery_estimates]:checked').val();
        if (delivery_estimate_val == 'dont_show_estimates') {
            jQuery("#rnl_freight_order_cut_off_time").prop('disabled', true);
            jQuery("#rnl_freight_shipment_offset_days").prop('disabled', true);
            jQuery("#rnl_freight_order_cut_off_time").css("cursor", "not-allowed");
            jQuery("#rnl_freight_shipment_offset_days").css("cursor", "not-allowed");
        } else {
            jQuery("#rnl_freight_order_cut_off_time").prop('disabled', false);
            jQuery("#rnl_freight_shipment_offset_days").prop('disabled', false);
            jQuery("#rnl_freight_order_cut_off_time").css("cursor", "auto");
            jQuery("#rnl_freight_shipment_offset_days").css("cursor", "auto");
        }
    });

    /*
     * Uncheck Week days Select All Checkbox
     */
    jQuery(".rnl_shipment_day").on('change load', function () {

        var checkboxes = jQuery('.rnl_shipment_day:checked').length;
        var un_checkboxes = jQuery('.rnl_shipment_day').length;
        if (checkboxes === un_checkboxes) {
            jQuery('.all_shipment_days_rnl').prop('checked', true);
        } else {
            jQuery('.all_shipment_days_rnl').prop('checked', false);
        }
    });

    /*
     * Select All Shipment Week days
     */

    var all_int_checkboxes = jQuery('.all_shipment_days_rnl');
    if (all_int_checkboxes.length === all_int_checkboxes.filter(":checked").length) {
        jQuery('.all_shipment_days_rnl').prop('checked', true);
    }

    jQuery(".all_shipment_days_rnl").change(function () {
        if (this.checked) {
            jQuery(".rnl_shipment_day").each(function () {
                this.checked = true;
            });
        } else {
            jQuery(".rnl_shipment_day").each(function () {
                this.checked = false;
            });
        }
    });


    //** End: Order Cut Off Time
    if (!jQuery('._nestedMaterials').is(":checked")) {
        jQuery('._nestedPercentage_tr').hide();
        jQuery('._nestedDimension_tr').hide();
        jQuery('._maxNestedItems_tr').hide();
        jQuery('._nestedDimension_tr').hide();
        jQuery('._nestedStakingProperty_tr').hide();
    } else {
        jQuery('._nestedPercentage_tr').show();
        jQuery('._nestedDimension_tr').show();
        jQuery('._maxNestedItems_tr').show();
        jQuery('._nestedDimension_tr').show();
        jQuery('._nestedStakingProperty_tr').show();
    }

    jQuery("._nestedPercentage").attr('min', '0');
    jQuery("._maxNestedItems").attr('min', '0');
    jQuery("._nestedPercentage").attr('max', '100');
    jQuery("._maxNestedItems").attr('max', '100');
    jQuery("._nestedPercentage").attr('maxlength', '3');
    jQuery("._maxNestedItems").attr('maxlength', '3');

    if (jQuery("._nestedPercentage").val() == '') {
        jQuery("._nestedPercentage").val(0);
    }

    jQuery("._nestedPercentage").keydown(function (eve) {
        Rnl_LFQ_stopSpecialCharacters(eve);
        var nestedPercentage = jQuery('._nestedPercentage').val();
        if (nestedPercentage.length == 2) {
            var newValue = nestedPercentage + '' + eve.key;
            if (newValue > 100) {
                return false;
            }
        }
    });

    jQuery("._maxNestedItems").keydown(function (eve) {
        Rnl_LFQ_stopSpecialCharacters(eve);
    });

    jQuery("._nestedMaterials").change(function () {
        if (!jQuery('._nestedMaterials').is(":checked")) {
            jQuery('._nestedPercentage_tr').hide();
            jQuery('._nestedDimension_tr').hide();
            jQuery('._maxNestedItems_tr').hide();
            jQuery('._nestedDimension_tr').hide();
            jQuery('._nestedStakingProperty_tr').hide();
        } else {
            jQuery('._nestedPercentage_tr').show();
            jQuery('._nestedDimension_tr').show();
            jQuery('._maxNestedItems_tr').show();
            jQuery('._nestedDimension_tr').show();
            jQuery('._nestedStakingProperty_tr').show();
        }
    });

    // Backup Rates
    addBackupRatesSettings();

    jQuery("#wc_settings_rnl_residential").closest('tr').addClass("wc_settings_rnl_residential");
    jQuery("#avaibility_auto_residential").closest('tr').addClass("avaibility_auto_residential");
    jQuery("#avaibility_lift_gate").closest('tr').addClass("avaibility_lift_gate");
    jQuery("#wc_settings_rnl_liftgate").closest('tr').addClass("wc_settings_rnl_liftgate");
    jQuery("#rnl_quotes_liftgate_delivery_as_option").closest('tr').addClass("rnl_quotes_liftgate_delivery_as_option");

    jQuery("#order_shipping_line_items .shipping .view .display_meta").css('display', 'none');

    jQuery('#rnl_hold_at_terminal_fee, #en_wd_origin_markup, #en_wd_dropship_markup, ._en_product_markup').bind("cut copy paste", function (e) {
        e.preventDefault();
    });

    jQuery("#en_wd_origin_markup, #en_wd_dropship_markup,._en_product_markup").keypress(function (e) {
        if (!String.fromCharCode(e.keyCode).match(/^[-0-9\d\.%\s]+$/i)) return false;
    });

    jQuery("#rnl_hold_at_terminal_fee, #en_rnl_max_weight_per_pallet").keydown(function (e) {
        console.log(e.keyCode);
        // Allow: backspace, delete, tab, escape, enter and .
        if (jQuery.inArray(e.keyCode, [46, 8, 9, 27, 13, 110, 190, 53, 189]) !== -1 ||
            // Allow: Ctrl+A, Command+A
            (e.keyCode === 65 && (e.ctrlKey === true || e.metaKey === true)) ||
            // Allow: home, end, left, right, down, up
            (e.keyCode >= 35 && e.keyCode <= 40)) {
            // let it happen, don't do anything
            return;
        }

        // Ensure that it is a number and stop the keypress
        if ((e.keyCode === 190 || e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
            e.preventDefault();
        }

        if ((jQuery(this).val().indexOf('.') != -1) && (jQuery(this).val().substring(jQuery(this).val().indexOf('.'), jQuery(this).val().indexOf('.').length).length > 2)) {
            if (event.keyCode !== 8 && event.keyCode !== 46) { //exception
                event.preventDefault();
            }
        }

    });

    jQuery("#rnl_hold_at_terminal_fee, #en_wd_origin_markup, #en_wd_dropship_markup, ._en_product_markup").keyup(function (e) {

        var val = jQuery(this).val();

        if (val.split('.').length - 1 > 1) {

            var newval = val.substring(0, val.length - 1);
            var countDots = newval.substring(newval.indexOf('.') + 1).length;
            newval = newval.substring(0, val.length - countDots - 1);
            jQuery(this).val(newval);
        }

        if (val.split('%').length - 1 > 1) {
            var newval = val.substring(0, val.length - 1);
            var countPercentages = newval.substring(newval.indexOf('%') + 1).length;
            newval = newval.substring(0, val.length - countPercentages - 1);
            jQuery(this).val(newval);
        }

        if (val.split('-').length - 1 > 1) {
            var newval = val.substring(0, val.length - 1);
            var countPercentages = newval.substring(newval.indexOf('-') + 1).length;
            newval = newval.substring(0, val.length - countPercentages - 1);
            jQuery(this).val(newval);
        }
    });


    /**
     * Offer lift gate delivery as an option and Always include residential delivery fee
     * @returns {undefined}
     */

    jQuery(".checkbox_fr_add").on("click", function () {
        var id = jQuery(this).attr("id");
        if (id == "wc_settings_rnl_liftgate") {
            jQuery("#rnl_quotes_liftgate_delivery_as_option").prop({checked: false});
            jQuery("#en_woo_addons_liftgate_with_auto_residential").prop({checked: false});

        } else if (id == "rnl_quotes_liftgate_delivery_as_option" ||
            id == "en_woo_addons_liftgate_with_auto_residential") {
            jQuery("#wc_settings_rnl_liftgate").prop({checked: false});
        }
    });

    var url = getUrlVarsRnLFreight()["tab"];
    if (url === 'rnl_quotes') {
        jQuery('#footer-left').attr('id', 'wc-footer-left');
    }
    /*
     * Add err class on connection settings page
     */
    jQuery('.connection_section_class_rnl input[type="text"]').each(function () {
        if (jQuery(this).parent().find('.err').length < 1) {
            jQuery(this).after('<span class="err"></span>');
        }
    });

    /*
     * Show Note Message on Connection Settings Page
     */

    jQuery('.connection_section_class_rnl .form-table').before("<div class='warning-msg'><p>Note! You must have an R+L Carriers account to use this application. If you do not have one, contact R+L Carriers at 800-543-5589, or request that someone contact you by filling out this <a href='http://www2.rlcarriers.com/contact/contactform' target='_blank'>form</a>.</p></div>");

    /*
     * Add maxlength Attribute on Handling Fee Quote Setting Page
     */

    jQuery("#wc_settings_rnl_handling_fee").attr('maxlength', '8');


    /*
     * Add Title To Connection Setting Fields
     */

    jQuery('#wc_settings_rnl_username').attr('data-optional', 1);
    jQuery('#wc_settings_rnl_password').attr('data-optional', 1);
    jQuery('#wc_settings_rnl_username').attr('title', 'Username');
    jQuery('#wc_settings_rnl_password').attr('title', 'Password');
    jQuery('#wc_settings_rnl_api_key').attr('title', 'API Key');
    jQuery('#wc_settings_rnl_plugin_licence_key').attr('title', 'Eniture API Key');

    /*
     * Add Title To Quotes Setting Fields
     */

    jQuery('#wc_settings_rnl_handling_fee').attr('title', 'Handling Fee / Mark Up');

    /*
     * Add CSS Class To Quote Services
     */

    jQuery('.rnl_all_services').closest('tr').addClass('rnl_all_services_tr');
    jQuery('.rnl_quotes_services_checkbox').closest('tr').addClass('rnl_quotes_services_checkbox_tr');
    jQuery('.rnl_quotes_services_checkbox').closest('td').addClass('rnl_quotes_services_checkbox_td');

    /*
     * Select All Services
     */

    var sm_all_checkboxes = jQuery('.rnl_quotes_services_checkbox');

    if (sm_all_checkboxes.length === sm_all_checkboxes.filter(":checked").length) {
        jQuery('.rnl_all_services').prop('checked', true);
    }

    jQuery(".rnl_all_services").change(function () {
        if (this.checked) {
            jQuery(".rnl_quotes_services_checkbox").each(function () {
                this.checked = true;
            });
        } else {
            jQuery(".rnl_quotes_services_checkbox").each(function () {
                this.checked = false;
            });
        }
    });

    /*
     * Uncheck Select All Checkbox
     */

    jQuery(".rnl_quotes_services_checkbox").on('change load', function () {
        var checkboxes = jQuery('.rnl_quotes_services_checkbox:checked').length;
        var un_checkboxes = jQuery('.rnl_quotes_services_checkbox').length;

        if (checkboxes === un_checkboxes) {
            jQuery('.rnl_all_services').prop('checked', true);
        } else {
            jQuery('.rnl_all_services').prop('checked', false);
        }
    });

    /*
         * Connection Settings Input Validation On Save
         */

    jQuery(".connection_section_class_rnl .woocommerce-save-button").click(function () {
        var input = validateInput('.connection_section_class_rnl');
        if (input === false) {
            return false;
        }
    });

    /*
     * Test connection
     */

    jQuery(".connection_section_class_rnl .woocommerce-save-button").text('Save Changes');

    jQuery(".connection_section_class_rnl .woocommerce-save-button").before('<a href="javascript:void(0)" class="button-primary rnl_test_connection">Test Connection</a>');
    jQuery('.rnl_test_connection').click(function (e) {
        var input = validateInput('.connection_section_class_rnl');
        if (input === false) {
            return false;
        }
        var postForm = {
            'action': 'rnl_action',
            'rnl_username': jQuery('#wc_settings_rnl_username').val(),
            'rnl_password': jQuery('#wc_settings_rnl_password').val(),
            'rnl_api_key': jQuery('#wc_settings_rnl_api_key').val(),
            'rnl_plugin_license': jQuery('#wc_settings_rnl_plugin_licence_key').val()
        };

        jQuery.ajax({
            type: 'POST',
            url: ajaxurl,
            data: postForm,
            dataType: 'json',

            beforeSend: function () {
                jQuery(".connection_save_button").remove();
                jQuery('#wc_settings_rnl_username').css('background', 'rgba(255, 255, 255, 1) url("' + en_rnl_admin_script.plugins_url + '/ltl-freight-quotes-rl-edition/asset/processing.gif") no-repeat scroll 50% 50%');
                jQuery('#wc_settings_rnl_password').css('background', 'rgba(255, 255, 255, 1) url("' + en_rnl_admin_script.plugins_url + '/ltl-freight-quotes-rl-edition/asset/processing.gif") no-repeat scroll 50% 50%');
                jQuery('#wc_settings_rnl_api_key').css('background', 'rgba(255, 255, 255, 1) url("' + en_rnl_admin_script.plugins_url + '/ltl-freight-quotes-rl-edition/asset/processing.gif") no-repeat scroll 50% 50%');
                jQuery('#wc_settings_rnl_plugin_licence_key').css('background', 'rgba(255, 255, 255, 1) url("' + en_rnl_admin_script.plugins_url + '/ltl-freight-quotes-rl-edition/asset/processing.gif") no-repeat scroll 50% 50%');
            },
            success: function (data) {
                jQuery('#wc_settings_rnl_username').css('background', '#fff');
                jQuery('#wc_settings_rnl_password').css('background', '#fff');
                jQuery('#wc_settings_rnl_api_key').css('background', '#fff');
                jQuery('#wc_settings_rnl_plugin_licence_key').css('background', '#fff');

                jQuery(".rnl_error_message").remove();
                jQuery(".rnl_success_message").remove();
                jQuery("#message").remove();

                if (data.message === "success") {
                    jQuery('.warning-msg').before('<div class="notice notice-success rnl_success_message"><p><strong>Success! The test resulted in a successful connection.</strong></p></div>');
                } else if (data.message !== "failure" && data.message !== "success") {
                    jQuery('.warning-msg').before('<div class="notice notice-error rnl_error_message"><p>Error!  ' + data.message + ' </p></div>');
                } else {
                    jQuery('.warning-msg').before('<div class="notice notice-error rnl_error_message"><p>Error! Please verify credentials and try again.</p></div>');
                }
            }
        });
        e.preventDefault();
    });
    // fdo va
    jQuery('#fd_online_id').click(function (e) {
        var postForm = {
            'action': 'rnl_fd',
            'company_id': jQuery('#freightdesk_online_id').val(),
            'disconnect': jQuery('#fd_online_id').attr("data")
        }
        var id_lenght = jQuery('#freightdesk_online_id').val();
        var disc_data = jQuery('#fd_online_id').attr("data");
        if(typeof (id_lenght) != "undefined" && id_lenght.length < 1) {
            jQuery(".rnl_error_message").remove();
            jQuery('.user_guide_fdo').before('<div class="notice notice-error rnl_error_message"><p><strong>Error!</strong> FreightDesk Online ID is Required.</p></div>');
            return;
        }
        jQuery.ajax({
            type: "POST",
            url: ajaxurl,
            data: postForm,
            beforeSend: function () {
                jQuery('#freightdesk_online_id').css('background', 'rgba(255, 255, 255, 1) url("' + en_rnl_admin_script.plugins_url + '/ltl-freight-quotes-rl-edition/asset/processing.gif") no-repeat scroll 50% 50%');
            },
            success: function (data_response) {
                if(typeof (data_response) == "undefined"){
                    return;
                }
                var fd_data = JSON.parse(data_response);
                jQuery('#freightdesk_online_id').css('background', '#fff');
                jQuery(".rnl_error_message").remove();
                if((typeof (fd_data.is_valid) != 'undefined' && fd_data.is_valid == false) || (typeof (fd_data.status) != 'undefined' && fd_data.is_valid == 'ERROR')) {
                    jQuery('.user_guide_fdo').before('<div class="notice notice-error rnl_error_message"><p><strong>Error! ' + fd_data.message + '</strong></p></div>');
                }else if(typeof (fd_data.status) != 'undefined' && fd_data.status == 'SUCCESS') {
                    jQuery('.user_guide_fdo').before('<div class="notice notice-success rnl_success_message"><p><strong>Success! ' + fd_data.message + '</strong></p></div>');
                    window.location.reload(true);
                }else if(typeof (fd_data.status) != 'undefined' && fd_data.status == 'ERROR') {
                    jQuery('.user_guide_fdo').before('<div class="notice notice-error rnl_error_message"><p><strong>Error! ' + fd_data.message + '</strong></p></div>');
                }else if (fd_data.is_valid == 'true') {
                    jQuery('.user_guide_fdo').before('<div class="notice notice-error rnl_error_message"><p><strong>Error!</strong> FreightDesk Online ID is not valid.</p></div>');
                } else if (fd_data.is_valid == 'true' && fd_data.is_connected) {
                    jQuery('.user_guide_fdo').before('<div class="notice notice-error rnl_error_message"><p><strong>Error!</strong> Your store is already connected with FreightDesk Online.</p></div>');

                } else if (fd_data.is_valid == true && fd_data.is_connected == false && fd_data.redirect_url != null) {
                    window.location = fd_data.redirect_url;
                } else if (fd_data.is_connected == true) {
                    jQuery('#con_dis').empty();
                    jQuery('#con_dis').append('<a href="#" id="fd_online_id" data="disconnect" class="button-primary">Disconnect</a>')
                }
            }
        });
        e.preventDefault();
    });

    /*
     * Save Changes Action
     */

    jQuery('.quote_section_class_rnl .woocommerce-save-button').on('click', function () {
        jQuery(".updated").hide();
        jQuery('.error').remove();

        // Quote service options
        const checkboxes = jQuery('.rnl_quotes_services_checkbox:checked');
        if (!checkboxes?.length) {
            jQuery("#mainform .quote_section_class_rnl").prepend('<div id="message" class="error inline rnl_handlng_fee_error"><p><strong>Please select at least one quote service option.</strong></p></div>');
            jQuery('html, body').animate({
                'scrollTop': jQuery('.rnl_handlng_fee_error').position().top
            });

            return false;
        } 

        /*
         * Handeling Fee Input Validation
         */

        if (!rnl_pallet_ship_class()) {
            return false;
        }

        // backup rates validation
        if (!backupRatesValidations()) return false;

        var handling_fee = jQuery('#wc_settings_rnl_handling_fee').val();

        if (handling_fee.slice(handling_fee.length - 1) == '%') {
            handling_fee = handling_fee.slice(0, handling_fee.length - 1);
        }

        if (handling_fee === "") {
            return true;
        } else {
            if (isValidNumber(handling_fee) === false) {
                jQuery("#mainform .quote_section_class_rnl").prepend('<div id="message" class="error inline rnl_handlng_fee_error"><p><strong>Handling fee format should be 100.2000 or 10%.</strong></p></div>');
                jQuery('html, body').animate({
                    'scrollTop': jQuery('.rnl_handlng_fee_error').position().top
                });
                return false;
            } else if (isValidNumber(handling_fee) === 'decimal_point_err') {
                jQuery("#mainform .quote_section_class_rnl").prepend('<div id="message" class="error inline rnl_handlng_fee_error"><p><strong>Handling fee format should be 100.2000 or 10% and only 4 digits are allowed after decimal point.</strong></p></div>');
                jQuery('html, body').animate({
                    'scrollTop': jQuery('.rnl_handlng_fee_error').position().top
                });
                return false;
            } else {
                return true;
            }
        }
    });

    // limited access delivery
    jQuery("#rnl_limited_access_delivery").closest('tr').addClass("rnl_limited_access_delivery");
    jQuery("#rnl_limited_access_delivery_as_option").closest('tr').addClass("rnl_limited_access_delivery_as_option");
    jQuery("#rnl_limited_access_delivery_fee").closest('tr').addClass("rnl_limited_access_delivery_fee");

    // limited access
    jQuery(".limited_access_add").on("change", function ()
    {
        const id = jQuery(this).attr('id');
        id == 'rnl_limited_access_delivery'
			? jQuery('#rnl_limited_access_delivery_as_option').prop({ checked: false })
			: jQuery('#rnl_limited_access_delivery').prop({ checked: false });

        if (this.checked) jQuery('.rnl_limited_access_delivery_fee').css('display', '');

        if (jQuery('#rnl_limited_access_delivery_as_option').prop('checked') == false &&
            jQuery('#rnl_limited_access_delivery').prop('checked') == false) {
			jQuery('.rnl_limited_access_delivery_fee').css('display', 'none');
		}
    });

    if (jQuery("#rnl_limited_access_delivery_as_option").prop("checked") == false &&
        jQuery("#rnl_limited_access_delivery").prop("checked") == false) {
        jQuery('.rnl_limited_access_delivery_fee').css('display', 'none');
    }

    // limited access delivery fee
    jQuery("#rnl_limited_access_delivery_fee, #rnl_backup_rates_fixed_rate, #rnl_backup_rates_weight_function").keypress(function (e) {
        if (!String.fromCharCode(e.keyCode).match(/^[0-9\d\.\s]+$/i)) return false;
    });

    jQuery('#rnl_limited_access_delivery_fee').keyup(function () {
		var val = jQuery(this).val();
		if (val.length > 7) {
			val = val.substring(0, 7);
			jQuery(this).val(val);
		}
	});

    jQuery('#rnl_limited_access_delivery_fee, #rnl_backup_rates_fixed_rate, #rnl_backup_rates_cart_price_percentage, #rnl_backup_rates_weight_function').keyup(function () {
		var val = jQuery(this).val();
		var regex = /\./g;
		var count = (val.match(regex) || []).length;
		
        if (count > 1) {
			val = val.replace(/\.+$/, '');
			jQuery(this).val(val);
		}
    });
    
    jQuery('#wc_settings_rnl_residential').on('change', function (e)
    {
        const checked = e.target.checked;
        if (checked) {
            jQuery('#rnl_limited_access_delivery').prop('disabled', true);
            jQuery('#rnl_limited_access_delivery').prop('checked', false);
        } else {
            jQuery('#rnl_limited_access_delivery').prop('disabled', false);
        }
    });

    if (jQuery('#wc_settings_rnl_residential').is(":checked")) {
        jQuery('#rnl_limited_access_delivery').prop('disabled', true);
        jQuery('#rnl_limited_access_delivery').prop('checked', false);
    }

    if (jQuery('#rnl_limited_access_delivery').is(":checked")) {
        jQuery('#wc_settings_rnl_residential').prop('disabled', true);
        jQuery('#wc_settings_rnl_residential').prop('checked', false);
    }
    
    // Product variants settings
    jQuery(document).on("click", '._nestedMaterials', function(e) {
        const checkbox_class = jQuery(e.target).attr("class");
        const name = jQuery(e.target).attr("name");
        const checked = jQuery(e.target).prop('checked');

        if (checkbox_class?.includes('_nestedMaterials')) {
            const id = name?.split('_nestedMaterials')[1];
            setNestMatDisplay(id, checked);
        }
    });

    // Callback function to execute when mutations are observed
    const handleMutations = (mutationList) => {
        let childs = [];
        for (const mutation of mutationList) {
            childs = mutation?.target?.children;
            if (childs?.length) setNestedMaterialsUI();
          }
    };
    const observer = new MutationObserver(handleMutations),
        targetNode = document.querySelector('.woocommerce_variations.wc-metaboxes'),
        config = { childList: true, subtree: true };
    if (targetNode) observer.observe(targetNode, config);

});

// Weight threshold for LTL freight
if (typeof en_weight_threshold_limit != 'function') {
    function en_weight_threshold_limit() {
        // Weight threshold for LTL freight
        jQuery("#en_weight_threshold_lfq").keypress(function (e) {
            if (String.fromCharCode(e.keyCode).match(/[^0-9]/g) || !jQuery("#en_weight_threshold_lfq").val().match(/^\d{0,3}$/)) return false;
        });

        jQuery('#en_plugins_return_LTL_quotes').on('change', function () {
            if (jQuery('#en_plugins_return_LTL_quotes').prop("checked")) {
                jQuery('tr.en_weight_threshold_lfq').css('display', 'contents');
                jQuery('tr.en_suppress_parcel_rates').css('display', '');
            } else {
                jQuery('tr.en_weight_threshold_lfq').css('display', 'none');
                jQuery('tr.en_suppress_parcel_rates').css('display', 'none');
            }
        });

        jQuery("#en_plugins_return_LTL_quotes").closest('tr').addClass("en_plugins_return_LTL_quotes_tr");
        // Weight threshold for LTL freight
        var weight_threshold_class = jQuery("#en_weight_threshold_lfq").attr("class");
        jQuery("#en_weight_threshold_lfq").closest('tr').addClass("en_weight_threshold_lfq " + weight_threshold_class);

        // Weight threshold for LTL freight is empty
        if (jQuery('#en_weight_threshold_lfq').length && !jQuery('#en_weight_threshold_lfq').val().length > 0) {
            jQuery('#en_weight_threshold_lfq').val(150);
        }

        // Suppress parcel rates when thresold is met
        jQuery(".en_suppress_parcel_rates").closest('tr').addClass("en_suppress_parcel_rates");
        !jQuery("#en_plugins_return_LTL_quotes").is(":checked") ? jQuery('tr.en_suppress_parcel_rates').css('display', 'none') : jQuery('tr.en_suppress_parcel_rates').css('display', '');
    }
}

// Update plan
if (typeof en_update_plan != 'function') {
    function en_update_plan(input) {
        let action = jQuery(input).attr('data-action');
        jQuery.ajax({
            type: "POST",
            url: ajaxurl,
            data: {action: action},
            success: function (data_response) {
                window.location.reload(true);
            }
        });
    }
}

function rnl_pallet_ship_class() {
    var en_ship_class = jQuery('#en_ignore_items_through_freight_classification').val();
    var en_ship_class_arr = en_ship_class.split(',');
    var en_ship_class_trim_arr = en_ship_class_arr.map(Function.prototype.call, String.prototype.trim);
    if (en_ship_class_trim_arr.indexOf('ltl_freight') != -1) {
        jQuery("#mainform .quote_section_class_rnl").prepend('<div id="message" class="error inline rnl_pallet_weight_error"><p><strong>Error! </strong>Shipping Slug of <b>ltl_freight</b> can not be ignored.</p></div>');
        jQuery('html, body').animate({
            'scrollTop': jQuery('.rnl_pallet_weight_error').position().top
        });
        jQuery("#en_ignore_items_through_freight_classification").css({'border-color': '#e81123'});
        return false;
    } else {
        return true;
    }
}

/*
 * Validate Input If Empty or Invalid
 */

function validateInput(form_id) {
    var has_err = true;
    jQuery(form_id + " input[type='text']").each(function () {

        var input = jQuery(this).val();
        var response = validateString(input);
        var errorText = jQuery(this).attr('title');
        var optional = jQuery(this).data('optional');

        var errorElement = jQuery(this).parent().find('.err');
        jQuery(errorElement).html('');

        optional = (optional === undefined) ? 0 : 1;
        errorText = (errorText != undefined) ? errorText : '';

        if ((optional == 0) && (response == false || response == 'empty')) {
            errorText = (response == 'empty') ? errorText + ' is required.' : 'Invalid input.';
            jQuery(errorElement).html(errorText);
        }
        has_err = (response != true && optional == 0) ? false : has_err;
    });
    return has_err;
}

/*
 * Check Input Value Is Not String
 */

function isValidNumber(value, noNegative) {
    if (typeof (noNegative) === 'undefined') noNegative = false;
    var isValidNumber = false;
    var validNumber = (noNegative == true) ? parseFloat(value) >= 0 : true;

    if ((value == parseInt(value) || value == parseFloat(value)) && (validNumber)) {
        if (value.indexOf(".") >= 0) {
            var n = value.split(".");
            if (n[n.length - 1].length <= 4) {
                isValidNumber = true;
            } else {
                isValidNumber = 'decimal_point_err';
            }
        } else {
            isValidNumber = true;
        }
    }
    return isValidNumber;
}

function validateString(string) {
    if (string == '') {
        return 'empty';
    } else {
        return true;
    }
}

/**
 * Read a page's GET URL variables and return them as an associative array.
 */
function getUrlVarsRnLFreight() {
    var vars = [], hash;
    var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
    for (var i = 0; i < hashes.length; i++) {
        hash = hashes[i].split('=');
        vars.push(hash[0]);
        vars[hash[0]] = hash[1];
    }
    return vars;
}

// Nesting
function Rnl_LFQ_stopSpecialCharacters(e) {
    // Allow: backspace, delete, tab, escape, enter and .
    if (jQuery.inArray(e.keyCode, [46, 9, 27, 13, 110, 190, 189]) !== -1 ||
        // Allow: Ctrl+A, Command+A
        (e.keyCode === 65 && (e.ctrlKey === true || e.metaKey === true)) ||
        // Allow: home, end, left, right, down, up
        (e.keyCode >= 35 && e.keyCode <= 40)) {
        // let it happen, don't do anything
        e.preventDefault();
        return;
    }
    // Ensure that it is a number and stop the keypress
    if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 90)) && (e.keyCode < 96 || e.keyCode > 105) && e.keyCode != 186 && e.keyCode != 8) {
        e.preventDefault();
    }
    if (e.keyCode == 186 || e.keyCode == 190 || e.keyCode == 189 || (e.keyCode > 64 && e.keyCode < 91)) {
        e.preventDefault();
        return;
    }
}

if (typeof setNestedMaterialsUI != 'function') {
    function setNestedMaterialsUI() {
        const nestedMaterials = jQuery('._nestedMaterials');
        const productMarkups = jQuery('._en_product_markup');
        
        if (productMarkups?.length) {
            for (const markup of productMarkups) {
                jQuery(markup).attr('maxlength', '7');

                jQuery(markup).keypress(function (e) {
                    if (!String.fromCharCode(e.keyCode).match(/^[0-9.%-]+$/))
                        return false;
                });
            }
        }

        if (nestedMaterials?.length) {
            for (let elem of nestedMaterials) {
                const className = elem.className;

                if (className?.includes('_nestedMaterials')) {
                    const checked = jQuery(elem).prop('checked'),
                        name = jQuery(elem).attr('name'),
                        id = name?.split('_nestedMaterials')[1];
                    setNestMatDisplay(id, checked);
                }
            }
        }
    }
}

if (typeof setNestMatDisplay != 'function') {
    function setNestMatDisplay (id, checked) {
        
        jQuery(`input[name="_nestedPercentage${id}"]`).attr('min', '0');
        jQuery(`input[name="_nestedPercentage${id}"]`).attr('max', '100');
        jQuery(`input[name="_nestedPercentage${id}"]`).attr('maxlength', '3');
        jQuery(`input[name="_maxNestedItems${id}"]`).attr('min', '0');
        jQuery(`input[name="_maxNestedItems${id}"]`).attr('max', '100');
        jQuery(`input[name="_maxNestedItems${id}"]`).attr('maxlength', '3');

        jQuery(`input[name="_nestedPercentage${id}"], input[name="_maxNestedItems${id}"]`).keypress(function (e) {
            if (!String.fromCharCode(e.keyCode).match(/^[0-9]+$/))
                return false;
        });

        jQuery(`input[name="_nestedPercentage${id}"]`).closest('p').css('display', checked ? '' : 'none');
        jQuery(`select[name="_nestedDimension${id}"]`).closest('p').css('display', checked ? '' : 'none');
        jQuery(`input[name="_maxNestedItems${id}"]`).closest('p').css('display', checked ? '' : 'none');
        jQuery(`select[name="_nestedStakingProperty${id}"]`).closest('p').css('display', checked ? '' : 'none');
    }
}

if (typeof addBackupRatesSettings != 'function') {
    function addBackupRatesSettings() {
        jQuery('input[name*="rnl_ltl_backup_rates_category"]').closest('tr').addClass("rnl_ltl_backup_rates_category");
        // backup rates as a fixed rate
        jQuery(".rnl_ltl_backup_rates_category input[value*='fixed_rate']").after('Backup rate as a fixed rate. <br /><input type="text" style="margin-top: 10px;" name="rnl_backup_rates_fixed_rate" id="rnl_backup_rates_fixed_rate" title="Backup Rates" maxlength="50" value="' + en_rnl_admin_script.rnl_backup_rates_fixed_rate + '"> <br> <span class="description"> Enter a value for the fixed rate. (e.g. 10.00)</span><br />');
        // backup rates as a percentage of cart price
        jQuery(".rnl_ltl_backup_rates_category input[value*='percentage_of_cart_price']").after('Backup rate as a percentage of Cart price. <br /><input type="text" style="margin-top: 10px;" name="rnl_backup_rates_cart_price_percentage" id="rnl_backup_rates_cart_price_percentage" title="Backup Rates" maxlength="50" value="' + en_rnl_admin_script.rnl_backup_rates_cart_price_percentage + '"> <br> <span class="description"> Enter a percentage for the backup rate. (e.g. 10.0%)</span><br />');
        // backup rates as a function of cart weight
        jQuery(".rnl_ltl_backup_rates_category input[value*='function_of_weight']").after('Backup rate as a function of the Cart weight. <br /><input type="text" style="margin-top: 10px;" name="rnl_backup_rates_weight_function" id="rnl_backup_rates_weight_function" title="Backup Rates" maxlength="50" value="' + en_rnl_admin_script.rnl_backup_rates_weight_function + '"> <br> <span class="description"> Enter a rate per pound to use for the backup rate. (e.g. 2.00)</span><br />');

        jQuery('#rnl_ltl_backup_rates_label').attr('maxlength', '50');
        jQuery('#rnl_ltl_backup_rates_label').attr('maxlength', '50');
        jQuery('#rnl_backup_rates_fixed_rate, #rnl_backup_rates_cart_price_percentage, #rnl_backup_rates_weight_function').attr('maxlength', '10');
        jQuery('#rnl_ltl_backup_rates_carrier_fails_to_return_response, #rnl_ltl_backup_rates_carrier_returns_error').closest('td').css('padding', '0px 10px');

        jQuery("#rnl_backup_rates_cart_price_percentage").keypress(function (e) {
            if (!String.fromCharCode(e.keyCode).match(/^[0-9\d\.%\s]+$/i)) return false;
        });   
    }
}

if (typeof backupRatesValidations != 'function') {
    function backupRatesValidations() {
        if (jQuery('#enable_backup_rates_rnl_ltl').is(':checked')) {
            let error_msg = '', field_id = '';
            if (jQuery('#rnl_ltl_backup_rates_label').val() == '') {
                error_msg = 'Backup rates label field is empty.';
                field_id = 'rnl_ltl_backup_rates_label';
            }

            const number_regex = /^([0-9]{1,4})$|(\.[0-9]{1,2})$/;
            const cart_price_regex = /^([0-9]{1,3}%?)$|(\.[0-9]{1,2})%?$/;
    
            if (!error_msg) {
                const backup_rates_type = jQuery('input[name="rnl_ltl_backup_rates_category"]:checked').val();
                if (backup_rates_type == 'fixed_rate' && jQuery('#rnl_backup_rates_fixed_rate').val() == '') {
                    error_msg = 'Backup rates as a fixed rate field is empty.';
                    field_id = 'rnl_backup_rates_fixed_rate';
                } else if (backup_rates_type == 'percentage_of_cart_price' && jQuery('#rnl_backup_rates_cart_price_percentage').val() == '') {
                    error_msg = 'Backup rates as a percentage of cart price field is empty.';
                    field_id = 'rnl_backup_rates_cart_price_percentage';
                } else if (backup_rates_type == 'function_of_weight' && jQuery('#rnl_backup_rates_weight_function').val() == '') {
                    error_msg = 'Backup rates as a function of weight field is empty.';
                    field_id = 'rnl_backup_rates_weight_function';
                } else if (jQuery('#rnl_backup_rates_fixed_rate').val() != '' && !number_regex.test(jQuery('#rnl_backup_rates_fixed_rate').val())) {
                    error_msg = 'Backup rates as a fixed rate format should be 100.20 or 10.';
                    field_id = 'rnl_backup_rates_fixed_rate';
                } else if (jQuery('#rnl_backup_rates_cart_price_percentage').val() != '' && !cart_price_regex.test(jQuery('#rnl_backup_rates_cart_price_percentage').val())) {
                    error_msg = 'Backup rates as a percentage of cart price format should be 100.20 or 10%.';
                    field_id = 'rnl_backup_rates_cart_price_percentage';
                } else if (jQuery('#rnl_backup_rates_weight_function').val() != '' && !number_regex.test(jQuery('#rnl_backup_rates_weight_function').val())) {
                    error_msg = 'Backup rates as a function of weight format should be 100.20 or 10.';
                    field_id = 'rnl_backup_rates_weight_function';
                }
            }
    
            if (error_msg) {
                jQuery(".updated").hide();
                jQuery("#mainform .quote_section_class_rnl").prepend('<div id="message" class="error inline no_backup_rates"><p><strong>' + error_msg + '</strong></p></div>');
                jQuery('html, body').animate({
                    'scrollTop': jQuery('#' + field_id).position().top
                });
                return false;
            }
        }

        return true;
    }
}