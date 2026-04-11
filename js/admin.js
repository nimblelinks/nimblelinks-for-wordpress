/* global jQuery, nimbleLinksAdmin */
(function ($) {
    'use strict';

    $(function () {
        $('#nimble-links-connect').on('click', function () {
            var $btn     = $(this);
            var $spinner = $('#nimble-links-spinner');
            var $error   = $('#nimble-links-error');
            var token    = $('#nimble-links-token').val().trim();

            if (!token) {
                $error.text('Please enter an API token.').show();
                return;
            }

            $btn.prop('disabled', true);
            $spinner.addClass('is-active');
            $error.hide();

            $.post(nimbleLinksAdmin.ajaxUrl, {
                action: 'nimble_links_validate_token',
                nonce: nimbleLinksAdmin.nonce,
                token: token
            })
            .done(function (response) {
                if (response.success) {
                    window.location.reload();
                } else {
                    $error.text(response.data.message).show();
                }
            })
            .fail(function () {
                $error.text('Could not connect to Nimble Links.').show();
            })
            .always(function () {
                $btn.prop('disabled', false);
                $spinner.removeClass('is-active');
            });
        });

        $('#nimble-links-disconnect').on('click', function () {
            var $btn = $(this);
            $btn.prop('disabled', true);

            $.post(nimbleLinksAdmin.ajaxUrl, {
                action: 'nimble_links_disconnect',
                nonce: nimbleLinksAdmin.nonce
            })
            .done(function () {
                window.location.reload();
            })
            .fail(function () {
                $btn.prop('disabled', false);
            });
        });
    });
})(jQuery);
