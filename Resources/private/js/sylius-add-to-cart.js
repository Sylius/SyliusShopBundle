/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

(function ( $ ) {
    'use strict';

    $.fn.extend({
        addToCart: function () {
            $(this).on('submit', function(event) {
                refresh(this, event);
            });
        }
    });

    function refresh(element, event) {
        event.preventDefault();

        var data = $(element).serialize();
        var href = $(element).attr('action');
        var redirectUrl = $(element).data('redirect');

        $.ajax({
            type: "POST",
            url: href,
            data: data,
            cache: false,
            success: function (data) {
                if ($(data).find(".sylius-validation-error").length) {
                    $(document).find('#sylius-product-selecting-variant').html($(data).find('#sylius-product-adding-to-cart'));

                    $(document).find('#sylius-product-adding-to-cart > button').on('click', function() {
                        return $(this).closest('form').addClass('loading');
                    });

                    $('#sylius-product-adding-to-cart').on('submit', function(event) {
                        refresh(this, event);
                    });
                } else {
                    window.location.replace(redirectUrl);
                }
            }
        })
    }

})( jQuery );
