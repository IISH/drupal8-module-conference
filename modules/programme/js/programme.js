jQuery(document).ready(function ($) {
    $('.programme .favorite').click(function () {
        var elem = $(this);
        if (!elem.data('locked')) {
            var session = elem.data('session');
            if (session) {
                elem.data('locked', true);
                var url = (elem.hasClass('on')) ? 'programme/remove-session/' : 'programme/add-session/';
                $.getJSON(url + session, [], function (result) {
                    if (result.success) {
                        if ((elem.hasClass('on')))
                            elem.removeClass('on');
                        else
                            elem.addClass('on');
                    }
                    elem.data('locked', false);
                });
            }
        }
    });
});