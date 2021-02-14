$(function (){
    $('#clear-filters').on('click', function() {
        window.location.href = '/' + $(this).attr('data-view')
    });
    $('#filters input').on('change', function() {
        let query_string = '?';
        $('#filters input').each(function() {
            if($(this).val() != '') {
                query_string += $(this).attr('data-field') + '=' + $(this).val() + '&';
            }
        });
        window.location.href = '/' + $('#page').val() + query_string;
    });
});
