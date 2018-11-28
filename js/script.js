jQuery(document).ready(function ($) {
    $('.more').click(function (e) {
        $(this).parents('.less-text').hide().next().show();
        e.preventDefault();
        return false;
    });

    $('.less').click(function (e) {
        $(this).parents('.more-text').hide().prev().show();
        e.preventDefault();
        return false;
    });
});