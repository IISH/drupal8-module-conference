jQuery(document).ready(function ($) {
    $('#opt-in').click(function () {
        var elem = $(this);
        if (!elem.data('locked')) {
            elem.data('locked', true);
            $.getJSON('personalpage/opt-in', [], function (result) {
                if (result.success) {
                    elem.prop('checked', result.optin);
                }
                elem.data('locked', false);
            });
        }
        return false;
    });
});