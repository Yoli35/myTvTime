$(function () {
    let has_been_seen = $('.has-been-seen');

    $(has_been_seen).click(function () {
        let badge = $(this);
        let movie_id = $(badge).attr("id");

        if ($(this).hasClass('yes')) {
            $.ajax({
                url: "/movie/remove",
                method: 'GET',
                data: {movie_db_id: movie_id},
                success: function (data) {
                    $(badge).removeClass('yes');
                    console.log(data);
                }
            });
        }
        else {
            $.ajax({
                url: "/movie/add",
                method: 'GET',
                data: {movie_db_id: movie_id},
                success: function (data) {
                    $(badge).addClass('yes');
                }
            });
        }
    })
})
