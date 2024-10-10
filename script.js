jQuery(document).ready(function($) {
    const $generateBtn = $('#pg-generate');
    const $copyBtn = $('#pg-copy');
    const $passwordField = $('#pg-password');
    const $loadingSpinner = $('#pg-loading-spinner');

    $generateBtn.on('click', function() {
        $generateBtn.prop('disabled', true);
        $copyBtn.prop('disabled', true);
        $loadingSpinner.show();

        const length = parseInt( $('#pg-length').val(), 10 );
        const uppercase = $('#pg-uppercase').is(':checked');
        const lowercase = $('#pg-lowercase').is(':checked');
        const numbers = $('#pg-numbers').is(':checked');
        const symbols = $('#pg-symbols').is(':checked');

        if ( isNaN(length) || length < 8 || length > 128 ) {
            alert( wp_ajax_object.invalid_length || 'Please select a password length between 8 and 128.' );
            $generateBtn.prop('disabled', false);
            $loadingSpinner.hide();
            return;
        }

        if ( ! uppercase && ! lowercase && ! numbers && ! symbols ) {
            alert( wp_ajax_object.no_character_types || 'Please select at least one character type.' );
            $generateBtn.prop('disabled', false);
            $loadingSpinner.hide();
            return;
        }

        const requestData = {
            action: 'pg_generate_password',
            length: length,
            uppercase: uppercase,
            lowercase: lowercase,
            numbers: numbers,
            symbols: symbols,
            nonce: pg_ajax_object.nonce
        };

        $.post( pg_ajax_object.ajax_url, requestData )
            .done( function( response ) {
                if ( response.success && response.data.password ) {
                    $passwordField.val( response.data.password );
                    $copyBtn.prop('disabled', false );
                } else if ( response.data && response.data.message ) {
                    alert( response.data.message );
                } else {
                    alert( wp_ajax_object.general_error || 'An error occurred while generating the password.' );
                }
            } )
            .fail( function() {
                alert( wp_ajax_object.server_error || 'A server error occurred. Please try again later.' );
            } )
            .always( function() {
                $generateBtn.prop('disabled', false);
                $loadingSpinner.hide();
            });
    });

    $copyBtn.on('click', function() {
        const password = $passwordField.val();

        if ( ! password ) {
            alert( wp_ajax_object.no_password || 'No password to copy!' );
            return;
        }

        navigator.clipboard.writeText( password )
            .then( function() {
                alert( wp_ajax_object.copy_success || 'Password copied to clipboard.' );
            } )
            .catch( function() {
                alert( wp_ajax_object.copy_failure || 'Failed to copy password.' );
            } );
    });

    $('#password-generator input[type="number"], #password-generator input[type="checkbox"]').on('change', function() {
        $passwordField.val('');
        $copyBtn.prop('disabled', true);
    });
});

