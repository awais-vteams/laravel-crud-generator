$(function () {
    $(document).on('click', '#clear-filters', function() {
        window.location.href = '/' + $(this).attr('data-view')
    });
    $(document).on('click', '.column-sorter', function () {
        updateView($(this).attr('data-sort'));
    });
    $(document).on('change', '#filters input, #filters select', function() {
        updateView('');
    });
});
function updateView(sort)
{
    let query_string = '?';
    $('#filters input, #filters select').each(function() {
        if($(this).val() != '') {
            query_string += $(this).attr('data-field') + '=' + $(this).val() + '&';
        }
    });

    query_string += 'sort=' + sort + '&sort_order=' + sortOrder();

    window.location.href = '/' + $('#page').val() + query_string;
}

function sortOrder()
{
    let sort_icon =  $('#sort_icon').val();
    if (sort_icon !== '') {
        return sort_icon == 'up' ? 'asc' : 'desc';
    }
    return 'asc';
}
