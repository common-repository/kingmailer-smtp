
// Show or hide SMTP/API options
var kingmailerApiOrNot = function () {

    if (jQuery('#kingmailer-api').val() != 0) {
        jQuery('.kingmailer-smtp').hide()
        jQuery('.kingmailer-api').show()
    } else {
        jQuery('.kingmailer-api').hide()
        jQuery('.kingmailer-smtp').show()
    }   
};

// Keep track of the form status
var formModified = false;

jQuery().ready(function () {
    
    kingmailerApiOrNot()
    jQuery('#kingmailer-api').change(function () { kingmailerApiOrNot() });
    
    // Send a test mail
    jQuery('#kingmailer-test').click(function (e) {
        e.preventDefault();

        // Give the user a validation message
        if (formModified) {
            var doTest = confirm(kingmailerco_smtp_admin_js_i18n.test_confirmation);
            if (!doTest) {
                return false
            }
        }

        // Print testing message to screen
        jQuery(this).val(kingmailerco_smtp_admin_js_i18n.test_testing)        
        jQuery('#kingmailer-test-result').text('')

        // Send Ajax request to the server
        jQuery.get(
            kingmailerco_smtp_admin_js_ajax.ajax_url,
            {
                action: 'kingmailer-test',
                _wpnonce: kingmailerco_smtp_admin_js_ajax.ajax_nonce
            }
        ).complete(function () {
            jQuery('#kingmailer-test').val(kingmailerco_smtp_admin_js_i18n.test_send_mail)
        }).success(function (data) {
            alert(kingmailerco_smtp_admin_js_i18n.test_sent_using + ' ' +  data.method + ' - ' + data.message)
        }).error(function () {
            alert(kingmailerco_smtp_admin_js_i18n.test_mail_failed)
        });

    });

    // Keep track of form changes
    jQuery('#kingmailer-form').change(function () {
        formModified = true
    })
});
