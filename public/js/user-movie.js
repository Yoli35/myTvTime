let json, ids, preview, infos, exportFile;
let _user_id;
let _locale;
let _personal_movies_export, _json_ids, _personal_movie_add, _json_cleanup, _json_sample, _movie_page, _movies_more;
let _url;

// more videos variables
let userMovieList, total_videos, displayed_videos, more_video_controller, loading_more_videos, nav_to_top, nav_to_top_visible;

function initButtons(id, locale, paths, url) {

    _user_id = id;
    _locale = locale;
    _personal_movies_export = paths[0];
    _json_ids = paths[1];
    _personal_movie_add = paths[2];
    _json_cleanup = paths[3];
    _json_sample = paths[4];
    _movie_page = paths[5].substring(0, paths[5].length - 1);
    _movies_more = paths[6];
    _url = url;

    const userMovieLink = document.querySelector('#query');
    document.addEventListener("visibilitychange", () => {
        if (document.visibilityState === 'visible') {
            userMovieLink.focus();
            userMovieLink.select();
        }
    });
    setTimeout(() => {
        userMovieLink.focus();
        userMovieLink.select();
    }, 1000);

    const exportButton = document.querySelector('#export-button');
    const appendButton = document.querySelector("#append-button");
    const exportModal = document.querySelector('#exportModal');
    const appendModal = document.querySelector("#appendModal");

    exportButton.addEventListener("click", () => {

        let result = exportModal.querySelector('.export-result');
        let count, movies, url, file, sample, result_items;

        const xhr = new XMLHttpRequest();
        xhr.onload = function () {
            const data = JSON.parse(this.response);
            count = data['count'];
            movies = data['movies'];
            json = data['json'];
            url = data['url'];
            file = data['file'];
            sample = data['sample'];

            // $('#json-link').attr({'href': url + file, 'download': file});
            exportModal.querySelector("#json-link").setAttribute("href", url + file);
            exportModal.querySelector("#json-link").setAttribute("download", file);

            // $(result).empty();
            while (result.lastChild) {
                result.removeChild(result.lastChild);
            }
            // $(result).append('<div class="sample">' + sample + '</div>');
            result.innerHTML += '<div class="sample">' + sample + '</div>';
            // $(result).append(
            result.innerHTML +=
                '<div class="selection">' +
                '   <div class="input-group w-75">' +
                '       <span class="input-group-text">' + txt.ui.filter[_locale] + '</span>' +
                '       <input type="search" id="export-filter" class="form-control" placeholder="' + txt.ui.movie_name[_locale] + '" aria-label="' + txt.ui.filter[_locale] + '" style="width: 15em">' +
                '       <button type="button" id="export-select" class="btn btn-secondary" aria-label="' + txt.ui.aria_select_all[_locale] + '">' + txt.ui.select_all[_locale] + '</button>' +
                '       <button type="button" id="export-deselect" class="btn btn-secondary" aria-label="' + txt.ui.aria_deselect_all[_locale] + '">' + txt.ui.deselect_all[_locale] + '</button>' +
                '   </div>' +
                '</div>';
            // );

            for (let i = 0; i < count; i++) {
                // $(result).append(
                //     '<div class="result-item" data-movie-id="' + movies[i]['movie_db_id'] + '" data-title="' + movies[i]['title'] + '" data-original="' + movies[i]['original_title'] + '">' + movies[i]['title'] + '</div>';
                // )
                // 100 x faster than innerHtml = "<div class...>"
                let div = document.createElement("div");
                div.setAttribute("class", "result-item");
                div.setAttribute("data-movie-id", movies[i]['movie_db_id']);
                div.setAttribute("data-title", movies[i]['title']);
                div.setAttribute("data-original", movies[i]['original_title']);
                div.appendChild(document.createTextNode(movies[i]['title']));
                result.appendChild(div);
            }

            // console.log(Modal);
            // new Modal('#exportModal').show();
            // $(exportModal).css('background-color', 'rgba(189,115,189,0.25)');
            // exportModal.setAttribute("style", "background-color: rgba(189,11,189,0.25)");
            exportModal.setAttribute("style", "background-color: rgba(189, 141, 189, 0.25)");
            // $(exportModal).css('display', 'block');
            // $(exportModal).css('opacity', '1');

            exportFile = file;

            // result_items = $('.result-item');
            result_items = exportModal.querySelectorAll(".result-item");

            // $(result_items).click(function () {
            //     $(this).toggleClass("active");
            //     updateSample($(result_items));
            // })
            result_items.forEach((item) => {
                item.addEventListener("click", () => {
                    item.classList.toggle("active");
                    updateSample(result_items);
                })
            })

            // $('#export-select').click(function () {
            //     $(result_items).addClass("active");
            //     updateSample($(result_items));
            // })
            exportModal.querySelector("#export-select").addEventListener("click", () => {
                result_items.forEach((item) => {
                    item.classList.add("active");
                });
                updateSample(result_items);
            });

            // $('#export-deselect').click(function () {
            //     $(result_items).removeClass("active");
            //     updateSample($(result_items));
            // })
            exportModal.querySelector("#export-deselect").addEventListener("click", () => {
                result_items.forEach((item) => {
                    item.classList.remove("active");
                });
                updateSample(result_items);
            });

            // $('#export-filter').on('input', function () {
            //     let needle = $(this).val();
            //     filter(result_items, needle);
            //     updateSample(result_items);
            // })
            exportModal.querySelector("#export-filter").addEventListener("input", function () {
                let needle = this.value;
                filter(result_items, needle);
                updateSample(result_items);
            });
        }
        xhr.open("GET", _personal_movies_export + '?id=' + _user_id);
        xhr.send();
    });

    // $('#export-copy').click(function () {
    exportModal.querySelector("#export-copy").addEventListener("click", function () {
        let string = JSON.stringify(json, null, '\t');
        navigator.clipboard.writeText(json).then(function () {
            /* presse-papiers modifié avec succès */
            alert('Copied! (' + string.length + ' caractères)')
        }, function () {
            /* échec de l’écriture dans le presse-papiers */
            alert('Rejected!')
        }).catch(err => alert(err));
    });

    // $('#append-button').click(function () {
    appendButton.addEventListener("click", function () {
        //     preview = $('.append-result');
        //     infos = $('.append-infos');
        preview = appendModal.querySelector(".append-result");
        infos = appendModal.querySelector(".append-infos");
        // $(preview).empty();
        while (preview.lastChild) {
            preview.removeChild(preview.lastChild);
        }
        // $(infos).empty();
        while (infos.lastChild) {
            infos.removeChild(infos.lastChild);
        }
        // $(infos).css('padding', '0');
        infos.setAttribute("style", "padding: 0");

        // $.ajax({
        //     url: _json_ids,
        //     method: 'GET',
        //     success: function (data) {
        //         ids = data['movie_ids'];
        //     }
        // })
        const xhr = new XMLHttpRequest();
        xhr.onload = function () {
            const data = JSON.parse(this.response);
            ids = data['movie_ids'];
        }
        xhr.open("GET", _json_ids);
        xhr.send();

        // let appendModal = $('#appendModal');
        // $(appendModal).css('transition', 'background-color .45s');
        // $(appendModal).css('background-color', 'rgba(189,115,189,0.25)');
        appendModal.setAttribute("style", "transition: background-color .45s");
        appendModal.setAttribute("style", "background-color: rgba(189, 141, 189, 0.25)");
    });

    // $('input[name="json"]').change(function () {
    appendModal.querySelector("input[name='json']").addEventListener("change", function () {
        // let add = $('#append-add');
        const add = appendModal.querySelector("#append-add");

        let json, content, count = 0;
        let fr = new FileReader();

        fr.onload = function () {
            json = fr.result;
        }
        fr.onloadend = function () {
            content = JSON.parse(json);
            // $(preview).empty();
            while (preview.lastChild) {
                preview.removeChild(preview.lastChild);
            }
            // $(infos).empty();
            while (infos.lastChild) {
                infos.removeChild(infos.lastChild);
            }

            // $(add).attr('disabled', 'disabled');
            add.setAttribute("disabled", "");

            if (content['total_results']) {
                for (let i = 0; i < content['total_results']; i++) {
                    let item = content['results'][i];
                    if (!ids.includes(item['movie_db_id'])) {
                        // $(preview).append('<div class="result-item"><img src="' + _url + item['poster_path'] + '" alt="' + item['title'] + '"><div class="name">' + item['title'] + '</div><div class="check-add" data-id="' + item['id'] + '" data-tmdb="' + item['movie_db_id'] + '"></div></div>');
                        preview.innerHTML += '<div class="result-item"><img src="' + _url + item['poster_path'] + '" alt="' + item['title'] + '"><div class="name">' + item['title'] + '</div><div class="check-add" data-id="' + item['id'] + '" data-tmdb="' + item['movie_db_id'] + '"></div></div>';
                        count++;
                    }
                }
                // $(infos).css('padding', '.5em');
                preview.setAttribute("style", "padding: .5em");
                let delta = content['total_results'] - count;
                // $(infos).append('<div class="info">' + txt.count[_locale] + txt.space + content['total_results'] + txt.space + txt.movies[_locale] + '.</div>');
                infos.innerHTML += '<div class="info">' + txt.ui.count[_locale] + txt.ui.space + content['total_results'] + txt.ui.space + txt.ui.movies[_locale] + '.</div>';
                if (delta) {
                    // $(infos).append('<div class="info">' + delta + txt.space + txt[(delta > 1) ? 'movies' : 'movie'][_locale] + txt.space + (delta > 1 ? txt.presents[_locale] : txt.present[_locale]) + '</div>');
                    infos.innerHTML += '<div class="info">' + delta + txt.ui.space + txt.ui[(delta > 1) ? 'movies' : 'movie'][_locale] + txt.ui.space + (delta > 1 ? txt.ui.presents[_locale] : txt.ui.present[_locale]) + '</div>';
                }
                if (count) {
                    // $(infos).append(
                    infos.innerHTML +=
                        '<div class="select">' +
                        '   <div class="btn-group btn-group-sm" role="group" aria-label="' + txt.ui.aria_select_group[_locale] + '">' +
                        '       <button name="select-all" type="button" class="btn btn-primary">' + txt.ui.select_all[_locale] + '</button>' +
                        '       <button name="unselect-all" type="button" class="btn btn-secondary">' + txt.ui.deselect_all[_locale] + '</button>' +
                        '   </div>' +
                        '</div>';
                    // );

                    // $('.check-add').click(function () {
                    //     $(this).toggleClass('active');
                    // });
                    const checkAdds = appendModal.querySelectorAll(".check-add");
                    checkAdds.forEach((item) => {
                        item.addEventListener("click", () => {
                            item.classList.toggle("active");
                        });
                    });

                    // $('button[name="select-all"]').click(function () {
                    //     $('.check-add').addClass('active');
                    // });
                    appendModal.querySelector('button[name="select-all"]').addEventListener("click", () => {
                        checkAdds.forEach((item) => {
                            item.classList.add("active");
                        });
                    });

                    // $('button[name="unselect-all"]').click(function () {
                    //     $('.check-add').removeClass('active');
                    // });
                    appendModal.querySelector('button[name="unselect-all"]').addEventListener("click", () => {
                        checkAdds.forEach((item) => {
                            item.classList.remove("active");
                        });
                    });

                    // $(add).removeAttr('disabled');
                    add.removeAttribute("disabled");
                } else {
                    // $(infos).append(
                    infos.innerHTML +=
                        '<div class="none">' + txt.ui.none[_locale] + '</div>';
                    // );
                }
            }
        }
        fr.readAsText(this.files[0]);
    });

    // $('#append-add').click(function () {
    appendModal.querySelector("#append-add").addEventListener("click", () => {
        // let selected = $('.check-add.active');
        const selected = appendModal.querySelectorAll('.check-add.active');
        // let progress = $('.append-progress');
        const progress = appendModal.querySelector('.append-progress');
        // let value = $('.value');
        const value = progress.querySelector('.value');
        let i, n = selected.length, pv = 0;

        // $(progress).css('display', 'flex');
        progress.setAttribute("style", "display: flex");
        // $(value).css('width', '0%');
        value.setAttribute("style", "width: 0%");

        for (i = 0; i < n; i++) {
            let movie = selected[i];
            // let tmdb_id = $(movie).data('tmdb');
            let tmdb_id = movie.getAttribute("data-tmdb");
            let val = (100 * (i / n)).toFixed();

            // $.ajax({
            //     url: _personal_movie_add,
            //     method: 'GET',
            //     data: {movie_db_id: tmdb_id, progress_value: val},
            //     success: function () {
            //         pv++;
            //         $(value).css('width', (100 * (pv / n)).toFixed() + '%');
            //
            //         if (pv === n) {
            //             setTimeout(() => {
            //                 // Ajout terminé.
            //                 $(progress).css('display', 'none');
            //                 window.location.reload();
            //             }, 1000);
            //         }
            //     }
            // })
            const xhr = new XMLHttpRequest();
            xhr.onload = function () {
                pv++;
                value.setAttribute("style", "width: " + (100 * (pv / n)).toFixed() + "%");

                if (pv === n) {
                    setTimeout(() => {
                        // Ajout terminé.
                        progress.removeAttribute("style");
                        window.location.reload();
                    }, 1000);
                }
            }
            xhr.open("GET", _personal_movie_add + '?movie_db_id=' + tmdb_id + '&progress_value=' + val);
            xhr.send();
        }
    });

    exportModal.addEventListener('hidden.bs.modal', () => {
        const xhr = new XMLHttpRequest();
        xhr.onload = function () {
        }
        xhr.open("GET", _json_cleanup + '?filename=' + exportFile);
        xhr.send();
    });

    // more videos event listener
    userMovieList = document.querySelector("#content");
    nav_to_top = document.querySelector(".nav-to-top");
    total_videos = parseInt(document.querySelector("h1").getAttribute("data-total-results"));
    displayed_videos = document.querySelectorAll(".home-discover").length;
    // debug_more_videos = document.querySelector(".debug-more-video");
    loading_more_videos = false;
    nav_to_top_visible = false;

    if (displayed_videos < total_videos) {
        moreVideos();
        more_video_controller = new AbortController();
        window.addEventListener("scroll", moreVideos, {signal: more_video_controller.signal});
    }

    // nav to top
    document.querySelector(".nav-to-top").addEventListener("click", function (e) {
        e.preventDefault();
        document.querySelector("html").scrollTop = 0;
        // document.querySelector("html").animate( {
        //     scrollTop: 0
        // }, 800);
    })
}

function filter(items, needle) {
    let title, original, count, idx;
    count = items.length;

    if (needle.length) {
        for (idx = 0; idx < count; idx++) {
            title = items[idx].getAttribute("data-title");
            original = items[idx].getAttribute("data-original");
            title = (typeof title === 'number') ? title.toString() : title.toLowerCase();
            original = (typeof original === 'number') ? original.toString() : original.toLowerCase();

            if (title.includes(needle) === false && original.includes(needle) === false) {
                items[idx].setAttribute("style", "display: none");
                items[idx].classList.remove('active');
            } else {
                items[idx].setAttribute("style", "display: block");
                items[idx].classList.add('active');
            }
        }
    } else {
        items.forEach((item) => {
            item.setAttribute("style", "display: block");
            item.classList.remove('active');
        });
    }
}

function updateSample(items) {

    let ids = [];

    items.forEach((item) => {
        if (item.classList.contains("active")) {
            ids.push(item.getAttribute("data-movie-id"));
        }
    })
    if (ids.length === items.length) {
        ids = [];
    }

    const xhr = new XMLHttpRequest();
    xhr.onload = function () {
        const data = JSON.parse(this.response);
        document.querySelector('.sample').innerHTML = data['sample'];
        json = data['json'];
    }
    xhr.open("GET", _json_sample + "?user_id=" + _user_id + "&ids=" + JSON.stringify(ids) + "&filename=" + exportFile);
    xhr.send();
}

function moreVideos() {
    const options = {year: 'numeric', month: 'numeric', day: 'numeric'};

    if (!loading_more_videos) {

        if (is_visible(userMovieList.lastElementChild)) {

            loading_more_videos = true;

            const xhr = new XMLHttpRequest();
            xhr.onload = function () {
                const data = JSON.parse(this.response);
                let results = data['results'];
                let count = results.length;

                for (let i = 0; i < count; i++) {
                    let result = results[i];
                    let newVideo = document.createElement("div");
                    newVideo.setAttribute("id", result['movie_db_id']);
                    newVideo.classList.add("home-discover");
                    newVideo.setAttribute("style", "opacity: 0; transform: scale(" + Math.random() + ") rotate(" + (((Math.random() * 720).toFixed()) - 360) + "deg)");
                    // newVideo.setAttribute("data-title", result['title']);
                    let aVideo = document.createElement("a");
                    aVideo.setAttribute("href", _movie_page + result['movie_db_id'].toString());
                    let img = document.createElement("img");
                    img.setAttribute("src", _url + result['poster_path']);
                    img.setAttribute("alt", result['title']);
                    let title = document.createElement("div");
                    title.setAttribute("class", "title");
                    title.appendChild(document.createTextNode(result['title']));
                    let date = document.createElement("div");
                    date.setAttribute("class", "date");
                    let dateT = result['release_date'] + 'T00:00:00';
                    let released = new Date(dateT);
                    date.appendChild(document.createTextNode(txt.movie.release_date[_locale] + ' :\n' + released.toLocaleDateString(undefined, options)));
                    aVideo.appendChild(img);
                    aVideo.appendChild(title);
                    aVideo.appendChild(date);
                    let user = document.createElement("div");
                    user.classList.add("user");
                    user.setAttribute("style", "transform: scale(.5)");
                    let rating = document.createElement("div");
                    rating.classList.add("rating");
                    rating.setAttribute("id", result['movie_db_id']);
                    for (let j=1;j<=5;j++) {
                        let star = document.createElement("div");
                        star.classList.add("star");
                        star.setAttribute("data-rate", j.toString());
                        rating.appendChild(star);
                    }
                    let seen = document.createElement("div");
                    seen.classList.add("has-been-seen", "yes");
                    seen.setAttribute("id", result['movie_db_id']);
                    let check = document.createElement("i");
                    check.classList.add("bi", "bi-check-circle-fill");
                    seen.appendChild(check);

                    user.appendChild(rating);
                    user.appendChild(seen);

                    newVideo.appendChild(aVideo);
                    newVideo.appendChild(user);

                    userMovieList.appendChild(newVideo);

                    let lastAdded = userMovieList.lastElementChild;
                    seen = lastAdded.querySelector(".yes");
                    seen.addEventListener("click", toggleSeenStatus);
                    getMovieRating(seen);
                }
                displayed_videos += count;
                setTimeout(() => {
                    userMovieList.querySelectorAll(".home-discover[style]").forEach((item) => {
                        item.setAttribute("style", "opacity: 1.0; transform: scale(1.0) rotate(0deg");
                    })
                }, 500);
                setTimeout(() => {
                    userMovieList.querySelectorAll(".home-discover[style]").forEach(item => {
                        item.removeAttribute("style");
                    })
                    loading_more_videos = false;
                }, 600);

                if (!nav_to_top_visible) {
                    nav_to_top.setAttribute("style", "display: block; opacity: 1;");
                    nav_to_top_visible = true;
                }
                //
                // If everything is displayed, abort the event listener
                //
                if (displayed_videos === total_videos) {
                    more_video_controller.abort();
                }
            }
            xhr.open("GET", _movies_more + '?id=' + _user_id + '&offset=' + displayed_videos);
            xhr.send();
        }
    }
}

function is_visible(el) {

    let middle_of_element = el.offsetTop + (el.offsetHeight / 2);
    let bottom_of_screen = window.scrollY + window.innerHeight;

    return (bottom_of_screen > middle_of_element);
}
