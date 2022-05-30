$(function () {
    let sort = $('select');
    let initialValue = $(sort).val(), newValue = 'popularity.desc',
        url = "{{ path(route, {page: 1, sort: sorts.sort_by}) }}";

    $(sort).change(function (e) {
        newValue = $(sort).val();
        if (newValue !== initialValue) {
            url = url.substring(0, url.length - 4) + newValue + '/1';
            console.log(url);
            // window.location.href = url;
        }
    });
})
