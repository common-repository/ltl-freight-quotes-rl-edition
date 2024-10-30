jQuery(document).ready(function($) {
    $(document).on('change', '.shipping_method', function(e) {
        var target = $(e.target);
        var priceElement = target.parents('.shipping').find('.wc_input_price.line_total');
        var shipping = target.val();
        target.prop('disabled', true);
        priceElement.length > 0 && priceElement.prop('disabled', true);
        jQuery.ajax({
            type: 'POST',
            url: ajaxurl,
            data: {
                action: 'eniture_calculate_shipping_rates_admin',
                products: $('#order_line_items .item').toArray().map(function(item) {
                    return $(item).find('input.order_item_id').val();
                }),
                country: $('#_shipping_country').val() || $('#_billing_country').val(),
                state: $('#_shipping_state').val() || $('#_billing_state').val(),
                postcode: $('#_shipping_postcode').val() || $('#_billing_postcode').val(),
                city: $('#_shipping_city').val() || $('#_billing_city').val(),
                address_line_1: $('#_shipping_address_1').val() || $('#_billing_address_1').val(),
                address_line_2: $('#_shipping_address_2').val() || $('#_billing_address_2').val(),
                city: $('#_shipping_city').val() || $('#_billing_city').val(),
                shipping: shipping
            },
            success: function (response) {
                target.prop('disabled', false);
                priceElement.length > 0 && priceElement.prop('disabled', false);
                var foundRate = response.data.shipping.find(function(rate) {
                    return rate.method.toLowerCase() === shipping.toLowerCase();
                });
                if (!foundRate)
                {
                    foundRate = response.data.shipping.find(function(rate) {
                        return rate.method.toLowerCase().match(shipping.toLowerCase());
                    });
                }
                priceElement.length > 0 && priceElement.val(foundRate ? foundRate.total : 0);
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                target.prop('disabled', false);
                priceElement.length > 0 && priceElement.prop('disabled', false);
                console.log(errorThrown);
            }
        });
    });
});
