let _profile_infos, _imdb_infos,
    _add_movie, _remove_movie,
    _get_movie_rating, _set_movie_rating,
    _app_movie_collection_toggle,
    _profile_url;
let _loc;
const txt = {
    'rating': {
        'vote': {
            'fr': 'Merci pour votre vote !',
            'en': 'Thank you for your vote!',
            'es': 'Gracias por su voto.',
            'de': 'Vielen Dank für Ihre Stimme!',
        },
        'create': {
            'fr': 'Nouveau vote pour ce film !',
            'en': 'New vote for this movie!',
            'es': '¡Nuevo voto para esta película!',
            'de': 'Neue Stimme für diesen Film!',
        },
        'update': {
            'fr': 'Le vote pour ce film a bien été mis à jour.',
            'en': 'The vote for this film has been updated.',
            'es': 'El voto para esta película ha sido actualizado.',
            'de': 'Die Abstimmung für diesen Film wurde tatsächlich aktualisiert.',
        }
    },
    'movie': {
        'release_date': {
            'fr': 'Date de sortie ',
            'en': 'Release date',
            'de': 'Erscheinungsdatum',
            'es': 'Fecha de lanzamiento'
        },
        'original_title': {
            'fr': 'Titre original ',
            'en': 'Original title',
            'de': 'Originaltitel',
            'es': 'Título original'
        },
        'runtime': {
            'fr': 'Durée ',
            'en': 'Runtime',
            'de': 'Dauer',
            'es': 'Duración'
        },
        'minutes': {
            'fr': 'minutes ',
            'en': 'minutes',
            'de': 'Minuten',
            'es': 'minutos'
        },
        'add': {
            'fr': 'Ce film a été ajouté à votre liste de films vus avec succès !',
            'en': 'This movie has been added to your list of movies successfully viewed!',
            'es': '¡Esta película ha sido añadida a tu lista de películas vistas con éxito!',
            'de': 'Dieser Film wurde erfolgreich zu Ihrer Liste der gesehenen Filme hinzugefügt!',
        },
        'rating': {
            'fr': 'Vous pouvez désormais évaluer ce film ★★★☆☆ !',
            'en': 'You can now rate this movie ★★★☆☆!',
            'es': '¡Ya puedes calificar esta película ★★★☆☆!',
            'de': 'Sie können diesen Film jetzt bewerten ★★★☆☆!',
        },
        'remove': {
            'fr': 'Le film a été retiré de votre liste de films vus ainsi que son éventuelle évaluation (★★★☆☆).',
            'en': 'The movie has been removed from your list of movies seen as well as its possible rating (★★★☆☆).',
            'es': 'La película ha sido eliminada de su lista de películas vistas y de su posible calificación (★★★☆☆).',
            'de': 'Der Film wurde aus Ihrer Liste der gesehenen Filme entfernt, ebenso wie seine eventuelle Bewertung (★★★☆☆).',
        }
    },
    'ui': {
        'aria_select_group': {
            'fr': 'Boutons de sélection',
            'en': 'Selection buttons',
            'de': 'Auswahlknöpfe',
            'es': 'Botones de selección'
        },
        'aria_select_all': {
            'fr': 'Tout sélectionner',
            'en': 'Select all',
            'de': 'Alle auswählen',
            'es': 'Seleccionar todo'
        },
        'aria_deselect_all': {
            'fr': 'Tout désélectionner',
            'en': 'Deselect all',
            'de': 'Alles abwählen',
            'es': 'De seleccionar todo'
        },
        'select_all': {
            'fr': 'Tout sélectionner',
            'en': 'Select all',
            'de': 'Alle auswählen',
            'es': 'Seleccionar todo'
        },
        'deselect_all': {
            'fr': 'Tout désélectionner',
            'en': 'Deselect all',
            'de': 'Alles abwählen',
            'es': 'De seleccionar todo'
        },
        'filter': {
            'fr': 'Filtre par nom',
            'en': 'Filter by name',
            'de': 'Nach Namen filtern',
            'es': 'Filtrar por nombre'
        },
        'movie_name': {
            'fr': 'Titre de film',
            'en': 'Movie title',
            'de': 'Filmtitel',
            'es': 'Título de la película'
        },
        'count': {
            'fr': 'Cette liste contient',
            'en': 'This list counts',
            'de': 'Diese Liste enthält',
            'es': 'Esta lista contiene'
        },
        'movies': {
            'fr': 'films',
            'en': 'movies',
            'de': 'Filme',
            'es': 'películas'
        },
        'movie': {
            'fr': 'film',
            'en': 'movie',
            'de': 'Film',
            'es': 'película'
        },
        'present': {
            'fr': 'déjà présent dans ta vidéothèque a été retiré de la liste.',
            'en': 'already in your video library has been removed from the list.',
            'de': 'wurde aus der Liste entfernt, weil er in deiner Videothek vorhanden ist.',
            'es': 'ha sido eliminada de la lista porque está presente en su videoteca'
        },
        'presents': {
            'fr': 'déjà présents dans ta vidéothèque ont été retirés de la liste.',
            'en': 'already in your video library have been removed from the list.',
            'de': 'wurden aus der Liste entfernt, weil sie in deiner Videothek vorhanden sind.',
            'es': 'han sido eliminadas de la lista porque están presentes en su videoteca'
        },
        'none': {
            'fr': 'Aucun film à ajouter',
            'en': 'No film to add',
            'de': 'Keine Filme hinzufügen',
            'es': 'No hay que añadir ninguna película'
        },
        'space': ' ',
    },
}
const personalModal = document.querySelector("#personModal");
let personalModalClose;
if (personalModal) {
    personalModalClose = personalModal.querySelectorAll("button");
}

function initMovieStuff(paths, profileUrl, locale) {
    // querySelectorAll renvoie une nodeList, vide si aucune correspondance n'est trouvée
    // const profiles = document.querySelectorAll(".profile");
    const has_been_seen = document.querySelectorAll(".has-been-seen");
    const movie_headers = document.querySelectorAll(".movie-header");

    _loc = locale
    _profile_infos = paths[0];
    _imdb_infos = paths[1];
    _add_movie = paths[2];
    _remove_movie = paths[3];
    _get_movie_rating = paths[4];
    _set_movie_rating = paths[5];
    _app_movie_collection_toggle = paths[6];
    _profile_url = profileUrl;

    initNotifications();

    movie_headers.forEach(movie_header => {
        movie_header.classList.add("start");
        setTimeout(() => {
            movie_header.classList.add("visible");
        }, 10);
    });

    has_been_seen.forEach(badge => {
        badge.addEventListener("click", toggleSeenStatus);
        if (badge.classList.contains("yes")) {
            getMovieRating(badge);
        }
    })

/*
    profiles.forEach(profile => {
        profile.addEventListener("click", getProfile);
    });

    if (personalModal) {
        personalModal.addEventListener("click", closeProfile);
        personalModalClose.forEach(button => {
            button.addEventListener("click", closeProfile);
        });
    }
*/
}

/*
function closeProfile() {
    personalModal.classList.remove("show");
    setTimeout(() => {
        personalModal.classList.remove("d-block");
    }, 200);
}

function getProfile(e) {
    let id = e.target.getAttribute("data-id").toString();

    const xhr = new XMLHttpRequest();
    xhr.onload = function () {
        const data = JSON.parse(this.response);
        let locale = data['locale'];
        let person = data['person'];
        let name = person['name'],
            biography = person['biography'],
            birthday = person['birthday'],
            death_day = person['death-day'],
            homepage = person['homepage'],
            imdbpage = person['imdb_id'],
            known_for_department = person['known_for_department'],
            place_of_birth = person['place_of_birth'],
            profile_path = person['profile_path'],
            gender = person['gender'];
        let department = data['department'];

        document.querySelector(".person-profile").innerHTML = "";
        personalModal.classList.add("d-block");
        personalModal.classList.add("show");

        document.querySelector(".modal-title").innerHTML = name;
        if (biography && biography.length) {
            document.querySelector(".biography div").innerHTML = biography;
        } else {
            document.querySelector(".biography div").innerHTML = '<div class="d-flex">Searching on IMDB ...&nbsp;<div class="spinner-border text-light ms-3" role="status"><span class="visually-hidden">Loading...</span></div></div>';

            const xhr2 = new XMLHttpRequest();
            xhr2.onload = function (result) {
                const imdb = JSON.parse(result.target.response);
                if (imdb['success']) {
                    let imdb_infos = imdb['person'];
                    document.querySelector(".biography div").innerHTML =
                        '<div>' +
                        imdb_infos['summary'] +
                        '</div>' +
                        '<div style="color: #eee;font-style: italic;">' +
                        imdb_infos['translated'] +
                        '</div>';
                } else
                    document.querySelector(".biography div").setAttribute("style", "display: none");
            }
            xhr2.open("GET", _imdb_infos + "?name=" + name + "&locale=" + locale);
            xhr2.send();
        }
        const options = {year: 'numeric', month: 'long', day: 'numeric'};
        if (birthday && birthday.length) document.querySelector(".birthday span").innerHTML = new Date(birthday).toLocaleString(_loc, options); else document.querySelector(".birthday").setAttribute("style", "display: none");
        if (death_day && death_day.length) document.querySelector(".death-day span").innerHTML = new Date(death_day).toLocaleString(_loc, options); else document.querySelector(".death-day").setAttribute("style", "display: none");
        if (homepage && homepage.length) document.querySelector(".homepage span").innerHTML = '<a href="' + homepage + '" target="_blank" rel="noopener">' + homepage + '</a>'; else document.querySelector(".homepage").setAttribute("style", "display: none");
        if (imdbpage && imdbpage.length) document.querySelector(".imdb-page span").innerHTML = '<a href="https://www.imdb.com/name/' + imdbpage + '" target="_blank" rel="noopener"></a>'; else document.querySelector(".imdb-page").setAttribute("style", "display: none");
        if (place_of_birth && place_of_birth.length) document.querySelector(".place-of-birth span").innerHTML = place_of_birth; else document.querySelector(".place-of-birth").setAttribute("style", "display: none");

        if (known_for_department && known_for_department.length) {
            if (locale === 'en' || department[locale][known_for_department] === undefined) {
                document.querySelector(".known-for-department span").innerHTML = '<span style="color: ' + (locale === 'en' ? 'green' : 'red') + '">' + known_for_department + '</span>';
            } else {
                document.querySelector(".known-for-department span").innerHTML = department[locale][known_for_department][gender];
            }
        } else {
            document.querySelector(".known-for-department").setAttribute("style", "display: none");
        }
        document.querySelector(".person-profile").innerHTML = '<img src="' + _profile_url + profile_path + '" alt="' + name + '">';
    }
    xhr.open("GET", _profile_infos + "?id=" + id + "&locale=" + _loc);
    xhr.send();
}
*/

function toggleSeenStatus(e) {

    let badge = e.target.parentElement;

    if (badge.classList.contains("yes")) {
        removeMovie(badge);
    } else {
        addMovie(badge);
    }
}

function addMovie(badge) {

    const id = badge.getAttribute("id");
    const xhr = new XMLHttpRequest();
    xhr.onload = function () {

        badge.classList.add("yes");
        let r = getMovieRating(badge);
        addNotification(txt.movie.add[_loc], "success");
        if (r) addNotification(txt.movie.rating[_loc], "info");
    }
    xhr.open("GET", _add_movie + "?movie_db_id=" + id);
    xhr.send();
}

function removeMovie(badge) {

    const id = badge.getAttribute("id");
    const xhr = new XMLHttpRequest();
    xhr.onload = function () {

        badge.classList.remove("yes");
        terminateRating(badge);
        addNotification(txt.movie.remove[_loc], "info");
    }
    xhr.open("GET", _remove_movie + "?movie_db_id=" + id);
    xhr.send();
}

function getMovieRating(badge) {
    const user = badge.parentElement;
    const rating = user.querySelector(".rating");
    if (rating) {
        const id = rating.getAttribute("id");
        const xhr = new XMLHttpRequest();
        xhr.onload = function () {
            const data = JSON.parse(this.response);
            let eval = data['rating'];
            user.setAttribute("data-rating", eval);
            initRating(badge);
        }
        xhr.open("GET", _get_movie_rating + "?movie_db_id=" + id);
        xhr.send();
        return true;
    }
    return false;
}

function setMovieRating(e) {
    const star = e.target;
    const rating = star.parentElement;
    const user = rating.parentElement;
    const id = rating.getAttribute("id");
    const movieRating = star.getAttribute("data-rate");
    user.setAttribute("data-rating", movieRating);
    setStars(user);

    const xhr = new XMLHttpRequest();
    xhr.onload = function () {
        const data = JSON.parse(this.response);
        let message;
        switch (data['result']) {
            case 'create':
                message = txt.rating.create[_loc];
                break;
            case 'update':
                message = txt.rating.update[_loc];
                break;
        }
        addNotification(txt.rating.vote[_loc], "success");
        addNotification(message, "success");
    }
    xhr.open("GET", _set_movie_rating + "?movie_db_id=" + id + "&rating=" + movieRating.toString());
    xhr.send();
}

function initRating(badge) {
    const user = badge.parentElement;
    const rating = user.querySelector(".rating");
    const stars = rating.querySelectorAll(".star");
    stars.forEach(star => {
        star.addEventListener("mouseover", hoverStars);
        star.addEventListener("mouseleave", leaveStars);
        star.addEventListener("click", setMovieRating);
    })
    setStars(user);
    rating.classList.add("visible");
}

function terminateRating(badge) {
    const user = badge.parentElement;
    const rating = user.querySelector(".rating");
    if (rating) {
        const stars = rating.querySelectorAll(".star");
        stars.forEach(star => {
            star.removeEventListener("mouseover", hoverStars);
            star.removeEventListener("mouseleave", leaveStars);
            star.removeEventListener("click", setMovieRating);
        })
        rating.classList.remove("visible");
    }
}

function setStars(user) {
    const rating = user.querySelector(".rating");
    const stars = rating.querySelectorAll(".star");
    const movieRating = user.getAttribute("data-rating");
    let index = 1;

    stars.forEach(star => {
        if (index++ <= movieRating) {
            star.classList.add("ok");
        } else {
            star.classList.remove("ok");
        }
    })
}

function hoverStars(e) {
    const star = e.target;
    const rating = star.parentElement;
    let rate = star.getAttribute("data-rate");

    let stars = rating.children;

    for (let i = 0; i < 5; i++) {
        stars[i].classList.remove("ok");
    }
    for (let i = 1; i <= rate; i++) {
        let s = stars[i - 1];
        s.classList.add("ok");
    }
}

function leaveStars(e) {
    const star = e.target;
    const rating = star.parentElement;
    const user = rating.parentElement;
    setStars(user);
}

function initCollections() {
    const collections = document.querySelector(".movie-collection").querySelectorAll(".item");

    collections.forEach( collection => {
        collection.addEventListener("click", toggleCollection);
    });
}

function toggleCollection(e) {
    const collection = e.target;
    const id = collection.getAttribute("data-id").toString();
    const movie = collection.parentElement.getAttribute("data-movie");

    collection.classList.toggle("selected");

        const xhr = new XMLHttpRequest();
        xhr.onload = function () {
            const data = JSON.parse(this.response);
            let message = data['message'];

            addNotification(message, "success");
        }
        xhr.open("GET", _app_movie_collection_toggle + "?c=" + id + "&m=" + movie + "&a=" + (collection.classList.contains("selected")?"a":"r"));
        xhr.send();
}

function initNotifications() {
    const notifications = document.createElement("div");
    notifications.classList.add("notifications");
    document.querySelector("body").appendChild(notifications);
}

function addNotification(message, type) {
    const notifications = document.querySelector(".notifications");
    let notification = document.createElement("div");

    notification.classList.add("notification", type);
    notification.appendChild(document.createTextNode(message));
    notifications.appendChild(notification);
    notification.classList.add("init");
    notification.classList.add("start");

    setTimeout(() => {
        notification.classList.add("visible");
    }, 100);
    setTimeout(() => {
        notification.classList.remove("visible");
        setTimeout(() => {
            notification.classList.add("end");
            setTimeout(() => {
                notifications.removeChild(notification);
            }, 10)
        }, 500);
    }, 5000);
}

