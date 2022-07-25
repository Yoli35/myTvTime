// import * as Modal from 'bootstrap/js/dist/modal';

let json, ids, preview, infos, exportFile;
let _user_id;
let _locale;
let _personal_movies_export, _json_ids, _personnel_movie_add, _json_cleanup, _json_sample, _movie_page, _movies_more;
let _url;

function initButtons(id, locale, paths, url) {

    _user_id = id;
    _locale = locale;
    _personal_movies_export = paths[0];
    _json_ids = paths[1];
    _personnel_movie_add = paths[2];
    _json_cleanup = paths[3];
    _json_sample = paths[4];
    _movie_page = paths[5].substring(0, paths[5].length - 1);
    _movies_more = paths[6];
    _url = url;

    $('#export-button').click(function () {

        let exportModal = $('#exportModal');
        let result = $('.modal-body').find('.export-result');
        let count, movies, url, file, sample, result_items;
        let txt = {
            'aria_select_group': {'fr': 'Boutons de sélection', 'en': 'Selection buttons', 'de': 'Auswahlknöpfe', 'es': 'Botones de selección'},
            'aria_select_all': {'fr': 'Tout sélectionner', 'en': 'Select all', 'de': 'Alle auswählen', 'es': 'Seleccionar todo'},
            'aria_deselect_all': {'fr': 'Tout désélectionner', 'en': 'Deselect all', 'de': 'Alles abwählen', 'es': 'De seleccionar todo'},
            'select_all': {'fr': 'Tout sélectionner', 'en': 'Select all', 'de': 'Alle auswählen', 'es': 'Seleccionar todo'},
            'deselect_all': {'fr': 'Tout désélectionner', 'en': 'Deselect all', 'de': 'Alles abwählen', 'es': 'De seleccionar todo'},
            'filter': {'fr': 'Filtre par nom', 'en': 'Filter by name', 'de': 'Nach Namen filtern', 'es': 'Filtrar por nombre'},
            'movie_name': {'fr': 'Titre de film', 'en': 'Movie title', 'de': 'Filmtitel', 'es': 'Título de la película'},
            '': {'fr': '', 'en': '', 'de': '', 'es': ''},
        }

        const xhr = new XMLHttpRequest();
        xhr.onload = function () {
            const data = JSON.parse(this.response);
            count = data['count'];
            movies = data['movies'];
            json = data['json'];
            url = data['url'];
            file = data['file'];
            sample = data['sample'];

            $('#json-link').attr({'href': url + file, 'download': file});

            $(result).empty();
            $(result).append('<div class="sample">' + sample + '</div>');
            $(result).append(
                '<div class="selection">' +
                '   <div class="input-group w-75">' +
                '       <span class="input-group-text">' + txt.filter[_locale] + '</span>' +
                '       <input type="search" id="export-filter" class="form-control" placeholder="' + txt.movie_name[_locale] + '" aria-label="' + txt.filter[_locale] + '" style="width: 15em">' +
                '       <button type="button" id="export-select" class="btn btn-secondary" aria-label="' + txt.aria_select_all[_locale] + '">' + txt.select_all[_locale] + '</button>' +
                '       <button type="button" id="export-deselect" class="btn btn-secondary" aria-label="' + txt.aria_deselect_all[_locale] + '">' + txt.deselect_all[_locale] + '</button>' +
                '   </div>' +
                '</div>');

            for (let i = 0; i < count; i++) {
                $(result).append('<div class="result-item" data-movie-id="' + movies[i]['movie_db_id'] + '" data-title="' + movies[i]['title'] + '" data-original="' + movies[i]['original_title'] + '">' + movies[i]['title'] + '</div>')
            }

            // console.log(Modal);
            // new Modal('#exportModal').show();
            $(exportModal).css('background-color', 'rgba(189,115,189,0.25)');
            // $(exportModal).css('display', 'block');
            // $(exportModal).css('opacity', '1');

            exportFile = file;

            result_items = $('.result-item');

            $(result_items).click(function () {
                $(this).toggleClass("active");
                updateSample($(result_items));
            })

            $('#export-select').click(function () {
                $(result_items).addClass("active");
                updateSample($(result_items));
            })

            $('#export-deselect').click(function () {
                $(result_items).removeClass("active");
                updateSample($(result_items));
            })

            $('#export-filter').on('input', function () {
                let needle = $(this).val();
                filter(result_items, needle);
                updateSample(result_items);
            })
        }

        xhr.open("GET", _personal_movies_export + '?id=' + _user_id);
        xhr.send();
    })

    $('#export-copy').click(function () {
        let string = JSON.stringify(json, null, '\t');
        navigator.clipboard.writeText(json).then(function () {
            /* presse-papiers modifié avec succès */
            alert('Copied! (' + string.length + ' caractères)')
        }, function () {
            /* échec de l’écriture dans le presse-papiers */
            alert('Rejected!')
        }).catch(err => alert(err));
    })

    $('#append-button').click(function () {
        preview = $('.append-result');
        infos = $('.append-infos');
        $(preview).empty();
        $(infos).empty();
        $(infos).css('padding', '0');

        $.ajax({
            url: _json_ids,
            method: 'GET',
            success: function (data) {
                ids = data['movie_ids'];
            }
        })
        let appendModal = $('#appendModal');
        $(appendModal).css('transition', 'background-color .45s');
        $(appendModal).css('background-color', 'rgba(189,115,189,0.25)');
    })

    $('input[name="json"]').change(function () {
        let json, content, count = 0;
        let add = $('#append-add');
        let fr = new FileReader();
        let txt = {
            'count': {'fr': 'Cette liste contient', 'en': 'This list counts', 'de': 'Diese Liste enthält', 'es': 'Esta lista contiene'},
            'movies': {'fr': 'films', 'en': 'movies', 'de': 'Filme', 'es': 'películas'},
            'movie': {'fr': 'film', 'en': 'movie', 'de': 'Film', 'es': 'película'},
            'present': {'fr': 'déjà présent dans ta vidéothèque a été retiré de la liste.', 'en': 'already in your video library has been removed from the list.', 'de': 'wurde aus der Liste entfernt, weil er in deiner Videothek vorhanden ist.', 'es': 'ha sido eliminada de la lista porque está presente en su videoteca'},
            'presents': {'fr': 'déjà présents dans ta vidéothèque ont été retirés de la liste.', 'en': 'already in your video library have been removed from the list.', 'de': 'wurden aus der Liste entfernt, weil sie in deiner Videothek vorhanden sind.', 'es': 'han sido eliminadas de la lista porque están presentes en su videoteca '},
            'none': {'fr': 'Aucun film à ajouter', 'en': 'No film to add', 'de': 'Keine Filme hinzufügen', 'es': 'No hay que añadir ninguna película'},
            '': {'fr': '', 'en': '', 'de': '', 'es': ''},
            'space': ' '
        }
        fr.onload = function () {
            json = fr.result;
        }
        fr.onloadend = function () {
            content = JSON.parse(json);
            $(preview).empty();
            $(infos).empty();
            $(add).attr('disabled', 'disabled');

            if (content['total_results']) {
                for (let i = 0; i < content['total_results']; i++) {
                    let item = content['results'][i];
                    if (!ids.includes(item['movie_db_id'])) {
                        $(preview).append('<div class="result-item"><img src="' + _url + item['poster_path'] + '" alt="' + item['title'] + '"><div class="name">' + item['title'] + '</div><div class="check-add" data-id="' + item['id'] + '" data-tmdb="' + item['movie_db_id'] + '"></div></div>');
                        count++;
                    }
                }
                $(infos).css('padding', '.5em');
                let delta = content['total_results'] - count;
                $(infos).append('<div class="info">' + txt.count[_locale] + txt.space + content['total_results'] + txt.space + txt.movies[_locale] + '.</div>');
                if (delta) {
                    $(infos).append('<div class="info">' + delta + txt.space + txt[(delta > 1) ? 'movies' : 'movie'][_locale] + txt.space + (delta > 1 ? txt.presents[_locale] : txt.present[_locale]) + '</div>');
                }
                if (count) {
                    $(infos).append(
                        '<div class="select">' +
                        '   <div class="btn-group btn-group-sm" role="group" aria-label="Select - Unselect">' +
                        '       <button name="select-all" type="button" class="btn btn-primary">Select all</button>' +
                        '       <button name="unselect-all" type="button" class="btn btn-secondary">Unselect all</button>' +
                        '   </div>' +
                        '</div>');
                } else {
                    $(infos).append(
                        '<div class="none">' + txt.none[_locale] + '</div>'
                    );
                }

                $('.check-add').click(function () {
                    $(this).toggleClass('active');
                });

                $('button[name="select-all"]').click(function () {
                    $('.check-add').addClass('active');
                });

                $('button[name="unselect-all"]').click(function () {
                    $('.check-add').removeClass('active');
                });
                $(add).removeAttr('disabled');
            }
        }
        fr.readAsText(this.files[0]);
    })

    $('#append-add').click(function () {
        let selected = $('.check-add.active');
        let progress = $('.append-progress');
        let value = $('.value');
        let i, n = selected.length, pv = 0;

        $(progress).css('display', 'flex');
        $(value).css('width', '0%');

        for (i = 0; i < n; i++) {
            let movie = selected[i];
            let tmdb_id = $(movie).data('tmdb');
            let val = (100 * (i / n)).toFixed();

            $.ajax({
                url: _personnel_movie_add,
                method: 'GET',
                data: {movie_db_id: tmdb_id, progress_value: val},
                success: function () {
                    pv++;
                    $(value).css('width', (100 * (pv / n)).toFixed() + '%');

                    if (pv === n) {
                        setTimeout(() => {
                            // Ajout terminé.
                            $(progress).css('display', 'none');
                            window.location.reload();
                        }, 1000);
                    }
                }
            })
        }
    })

    const myModalEl = document.getElementById('exportModal')
    myModalEl.addEventListener('hidden.bs.modal', () => {

        $.ajax({
            url: _json_cleanup,
            method: 'GET',
            data: {filename: exportFile},
            success: function () {
            }
        })
    });

    const moreButton = document.getElementById('more');
    const seeMore = document.getElementById('see-more');
    const videoList = document.getElementById('content');
    const txt = {
        'release_date': {'fr': 'Date de sortie ', 'en': 'Release date', 'de': 'Erscheinungsdatum', 'es': 'Fecha de lanzamiento'},
        '': {'fr': '', 'en': '', 'de': '', 'es': ''},
    }

    if (moreButton) {

        moreButton.addEventListener('click', () => {

            const h1 = document.getElementById('h1');
            const videos = document.getElementsByClassName('home-discover');
            const options = { year: 'numeric', month: 'numeric', day: 'numeric' };

            total_results = parseInt(h1.getAttribute('data-total-results'));
            let current_results = videos.length;

            $.ajax({
                url: _movies_more,
                method: 'GET',
                data: {id: _user_id, offset: current_results},
                success: function (data) {
                    let results = data['results'];
                    let count = results.length;

                    for (let i = 0; i < count; i++) {
                        let result = results[i];
                        let newVideo = document.createElement("div");
                        newVideo.setAttribute("class", "home-discover");
                        newVideo.setAttribute("id", result['movie_db_id']);
                        let aVideo = document.createElement("a");
                        aVideo.setAttribute("href", _movie_page + result['movie_db_id'].toString());
                        let img = document.createElement("img");
                        img.setAttribute("src", url + result['poster_path']);
                        img.setAttribute("alt", result['title']);
                        let title = document.createElement("div");
                        title.setAttribute("class", "title");
                        title.appendChild(document.createTextNode(result['title']));
                        let date = document.createElement("div");
                        date.setAttribute("class", "date");
                        let dateT = result['release_date'];
                        let released = new Date(dateT);
                        date.appendChild(document.createTextNode(txt.release_date[_locale] + ' :\n' + released.toLocaleDateString(undefined, options)));
                        aVideo.appendChild(img);
                        aVideo.appendChild(title);
                        aVideo.appendChild(date);
                        newVideo.appendChild(aVideo);

                        videoList.insertBefore(newVideo, seeMore);
                    }
                    //
                    // If everything is displayed, we make the 'See more results' button disappear
                    //
                    if (current_results + count === total_results) {
                        seeMore.setAttribute("style", "display: none;");
                    }
                }
            })
        });
    }
}

function filter(items, needle) {
    let title, original, count, idx;
    count = items.length;

    if (needle.length) {
        for (idx = 0; idx < count; idx++) {
            title = $(items[idx]).data('title');
            original = $(items[idx]).data('original');
            title = (typeof title === 'number') ? title.toString() : title.toLowerCase();
            original = (typeof original === 'number') ? original.toString() : original.toLowerCase();

            if (title.includes(needle) === false && original.includes(needle) === false) {
                $(items[idx]).css('display', 'none');
                $(items[idx]).removeClass('active');
            } else {
                $(items[idx]).css('display', 'block');
                $(items[idx]).addClass('active');
            }
        }
    } else {
        $(items).css('display', 'block');
        $(items).removeClass('active');
    }
}

function updateSample(items) {

    let ids = [];
    for (let idx = 0; idx < items.length; idx++) {
        if ($(items[idx]).hasClass('active')) {
            ids.push($(items[idx]).data('movie-id'));
        }
    }
    if (ids.length === items.length) {
        ids = [];
    }

    $.ajax({
        url: _json_sample,
        method: 'GET',
        data: {user_id: _user_id, ids: JSON.stringify(ids), filename: exportFile},
        success: function (data) {
            $('.sample').html(data['sample']);
            json = data['json'];
        }
    })
}

