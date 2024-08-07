let json, ids, preview, infos, exportFile;
let _user_id;
let _locale;
let _personal_movies_export, _json_ids, _personal_movie_add, _json_cleanup, _json_sample, _movie_page, _movies_more;
let _url;
let _app_movie_list_show;

// more videos variables
let userMovieList, uml_sort, uml_order, total_videos, displayed_videos, more_video_controller, loading_more_videos, nav_to_top, nav_to_top_visible;

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
    _app_movie_list_show = paths[7].substring(0, paths[7].length - 1);
    _url = url;

    // const userMovieLink = document.querySelector('#query');
    // document.addEventListener("visibilitychange", () => {
    //     if (document.visibilityState === 'visible') {
    //         userMovieLink.focus();
    //         userMovieLink.select();
    //     }
    // });
    // setTimeout(() => {
    //     userMovieLink.focus();
    //     userMovieLink.select();
    // }, 1000);

    const exportButton = document.querySelector('#export-button');
    const appendButton = document.querySelector("#append-button");
    const exportModal = document.querySelector('#exportModal');
    const exportModalClose = exportModal.querySelectorAll("button");
    const appendModal = document.querySelector("#appendModal");
    const appendModalClose = appendModal.querySelectorAll("button");
    let currentDialog = null;

    exportModalClose.forEach(button => {
        button.addEventListener("click", closeDialog);
    });

    appendModalClose.forEach(button => {
        button.addEventListener("click", closeDialog);
    });

    exportButton.addEventListener("click", openExportDialog);
    exportModal.querySelector("#export-copy").addEventListener("click", exportListToClipboard);
    appendButton.addEventListener("click", openAppendDialog);
    appendModal.querySelector("input[name='json']").addEventListener("change", appendShowJsonContent);
    appendModal.querySelector("#append-add").addEventListener("click", appendAddJsonContent);

    function openExportDialog() {
        currentDialog = exportModal;

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

            exportModal.querySelector("#json-link").setAttribute("href", url + file);
            exportModal.querySelector("#json-link").setAttribute("download", file);

            exportModal.querySelector(".sample").innerHTML = sample;

            while (result.lastChild) {
                result.removeChild(result.lastChild);
            }

            for (let i = 0; i < count; i++) {
                // 100 x faster than innerHtml = "<div class...>"
                let div = document.createElement("div");
                div.setAttribute("class", "result-item");
                div.setAttribute("data-movie-id", movies[i]['movie_db_id']);
                div.setAttribute("data-title", movies[i]['title']);
                div.setAttribute("data-original", movies[i]['original_title']);
                div.appendChild(document.createTextNode(movies[i]['title']));
                result.appendChild(div);
            }

            openDialog();

            exportFile = file;

            result_items = exportModal.querySelectorAll(".result-item");

            result_items.forEach((item) => {
                item.addEventListener("click", () => {
                    item.classList.toggle("active");
                    updateSample(result_items);
                })
            })

            exportModal.querySelector("#export-select").addEventListener("click", () => {
                result_items.forEach((item) => {
                    item.classList.add("active");
                });
                updateSample(result_items);
            });

            exportModal.querySelector("#export-deselect").addEventListener("click", () => {
                result_items.forEach((item) => {
                    item.classList.remove("active");
                });
                updateSample(result_items);
            });

            exportModal.querySelector("#export-filter").addEventListener("input", function () {
                let needle = this.value;
                filter(result_items, needle);
                updateSample(result_items);
            });
        }
        xhr.open("GET", _personal_movies_export);
        xhr.send();
    }

    function exportListToClipboard() {
        let string = JSON.stringify(json, null, '\t');
        navigator.clipboard.writeText(json).then(function () {
            /* presse-papiers modifié avec succès */
            alert('Copied! (' + string.length + ' caractères)')
        }, function () {
            /* échec de l’écriture dans le presse-papiers */
            alert('Rejected!')
        }).catch(err => alert(err));
    }

    function openAppendDialog() {

        currentDialog = appendModal;

        preview = appendModal.querySelector(".append-result");
        infos = appendModal.querySelector(".append-infos");
        while (preview.lastChild) {
            preview.removeChild(preview.lastChild);
        }
        while (infos.lastChild) {
            infos.removeChild(infos.lastChild);
        }
        infos.setAttribute("style", "padding: 0");

        const xhr = new XMLHttpRequest();
        xhr.onload = function () {
            const data = JSON.parse(this.response);
            ids = data['movie_ids'];
        }
        xhr.open("GET", _json_ids);
        xhr.send();

        openDialog();
    }

    function appendShowJsonContent() {
        // let add = $('#append-add');
        const add = appendModal.querySelector("#append-add");

        let json, content, count = 0;
        let fr = new FileReader();

        fr.onload = function () {
            json = fr.result;
        }
        fr.onloadend = function () {
            content = JSON.parse(json);
            while (preview.lastChild) {
                preview.removeChild(preview.lastChild);
            }
            while (infos.lastChild) {
                infos.removeChild(infos.lastChild);
            }

            // $(add).attr('disabled', 'disabled');
            add.setAttribute("disabled", "");

            if (content['total_results']) {
                for (let i = 0; i < content['total_results']; i++) {
                    let item = content['results'][i];
                    if (!ids.includes(item['movie_db_id'])) {
                        preview.innerHTML += '<div class="result-item"><img src="' + _url + item['poster_path'] + '" alt="' + item['title'] + '"><div class="name">' + item['title'] + '</div><div class="check-add" data-id="' + item['id'] + '" data-tmdb="' + item['movie_db_id'] + '"></div></div>';
                        count++;
                    }
                }
                preview.setAttribute("style", "padding: .5em");
                let delta = content['total_results'] - count;
                infos.innerHTML += '<div class="info">' + txt.ui.count[_locale] + txt.ui.space + content['total_results'] + txt.ui.space + txt.ui.movies[_locale] + '.</div>';
                if (delta) {
                    infos.innerHTML += '<div class="info">' + delta + txt.ui.space + txt.ui[(delta > 1) ? 'movies' : 'movie'][_locale] + txt.ui.space + (delta > 1 ? txt.ui.presents[_locale] : txt.ui.present[_locale]) + '</div>';
                }
                if (count) {
                    infos.innerHTML +=
                        '<div class="select">' +
                        '   <div class="btn-group btn-group-sm" role="group" aria-label="' + txt.ui.aria_select_group[_locale] + '">' +
                        '       <button name="select-all" type="button" class="btn btn-primary">' + txt.ui.select_all[_locale] + '</button>' +
                        '       <button name="unselect-all" type="button" class="btn btn-secondary">' + txt.ui.deselect_all[_locale] + '</button>' +
                        '   </div>' +
                        '</div>';
                    const checkAdds = appendModal.querySelectorAll(".check-add");
                    checkAdds.forEach((item) => {
                        item.addEventListener("click", () => {
                            item.classList.toggle("active");
                        });
                    });
                    appendModal.querySelector('button[name="select-all"]').addEventListener("click", () => {
                        checkAdds.forEach((item) => {
                            item.classList.add("active");
                        });
                    });
                    appendModal.querySelector('button[name="unselect-all"]').addEventListener("click", () => {
                        checkAdds.forEach((item) => {
                            item.classList.remove("active");
                        });
                    });

                    add.removeAttribute("disabled");
                } else {
                    infos.innerHTML +=
                        '<div class="none">' + txt.ui.none[_locale] + '</div>';
                }
            }
        }
        fr.readAsText(this.files[0]);
    }

    function appendAddJsonContent() {
        const selected = appendModal.querySelectorAll('.check-add.active');
        const progress = appendModal.querySelector('.append-progress');
        const value = progress.querySelector('.value');
        let i, n = selected.length, pv = 0;

        progress.setAttribute("style", "display: flex");
        value.setAttribute("style", "width: 0%");

        for (i = 0; i < n; i++) {
            let movie = selected[i];
            // let tmdb_id = $(movie).data('tmdb');
            let tmdb_id = movie.getAttribute("data-tmdb");
            let val = (100 * (i / n)).toFixed();

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
    }

    function openDialog() {
        let dialog = currentDialog;

        if (typeof dialog.showModal === "function") {
            dialog.showModal();
            setTimeout(() => {
                dialog.classList.add("show")
            }, 0);
        } else {
            console.error("L'API <dialog> n'est pas prise en charge par ce navigateur.");
            /*dialog.setAttribute("open");
            let offset = document.querySelector("html").scrollTop;
            dialog.setAttribute("style", "translate: 0 " + offset + "px;");
            dialog.classList.remove("d-none");
            dialog.classList.add("d-block");*/
        }
    }

    function dismissDialog(dialog) {
        dialog.classList.remove("show");
        setTimeout(() => {
            dialog.close()
        }, 300);
    }

    function closeDialog(evt) {

        let dialog = currentDialog;

        if (currentDialog === exportModal) {
            const button = evt.currentTarget;
            let delay = 0;
            if (button.getAttribute("id") === 'export-file') {
                delay = 1000;
            }
            setTimeout(() => {
                const xhr = new XMLHttpRequest();
                xhr.onload = () => dismissDialog(dialog);
                xhr.onerror = () => dismissDialog(dialog);

                xhr.open("GET", _json_cleanup + '?filename=' + exportFile);
                xhr.send();
            }, delay);
        } else {
            dismissDialog(dialog);
        }
        currentDialog = null;
    }

    // more videos event listener
    userMovieList = document.querySelector("#content");
    uml_sort = userMovieList.getAttribute("data-sort") ?? 'id';
    uml_order = userMovieList.getAttribute("data-order") ?? 'DESC';
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
        if (this.response.charAt(0) !== '<') {
            const data = JSON.parse(this.response);
            document.querySelector('.sample').innerHTML = data['sample'];
            json = data['json'];
        }
    }
    xhr.open("GET", _json_sample + "?user_id=" + _user_id + "&ids=" + JSON.stringify(ids) + "&filename=" + exportFile);
    xhr.send();
}

function moreVideos() {
    if (!loading_more_videos) {

        if (is_visible(userMovieList.lastElementChild)) {

            loading_more_videos = true;

            const xhr = new XMLHttpRequest();
            xhr.onload = function () {
                const data = JSON.parse(this.response);
                let blocks = data['blocks'];
                let count = blocks.length;
                // console.log(data);

                for (let i = 0; i < count; i++) {
                //    userMovieList.innerHTML += blocks[i];
                    const div = document.createElement("div");
                    div.innerHTML = blocks[i];
                    const home_discover = div.querySelector(".home-discover");
                    userMovieList.appendChild(home_discover);
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
            xhr.open("GET", _movies_more + '?id=' + _user_id + '&offset=' + displayed_videos + '&sort=' + uml_sort + '&order=' + uml_order);
            xhr.send();
        }
    }
}

function is_visible(el) {

    let middle_of_element = el.offsetTop + (el.offsetHeight / 2);
    let bottom_of_screen = window.scrollY + window.innerHeight;

    return (bottom_of_screen > middle_of_element);
}
