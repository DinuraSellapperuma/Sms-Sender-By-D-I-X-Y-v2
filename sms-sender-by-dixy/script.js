jQuery(document).ready(function($) {
    $('#csv_file').change(function() {
        var fileName = $(this).val().split('\\').pop();
        $('#file-name').text(fileName);
    });

    $('#check-balance').click(function() {
        var button = $(this);
        button.prop('disabled', true).text('Checking...');
        $('#balance-result').html('');

        $.ajax({
            url: smsSenderAjax.ajax_url,
            method: 'POST',
            data: {
                action: 'check_balance',
                api_key: smsSenderAjax.api_key
            },
            success: function(response) {
                if (response.success) {
                    $('#balance-result').html('<p><strong>Balance:</strong> ' + response.data + '</p>');
                } else {
                    $('#balance-result').html('<p class="error">Error: ' + response.data + '</p>');
                }
                button.prop('disabled', false).text('Check Balance');
            },
            error: function() {
                $('#balance-result').html('<p class="error">An error occurred while checking the balance.</p>');
                button.prop('disabled', false).text('Check Balance');
            }
        });
    });
});
