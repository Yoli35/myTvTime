let _profile_infos, _imdb_infos,
    _add_movie, _remove_movie,
    _get_movie_rating, _set_movie_rating,
    _app_movie_collection_toggle,
    _profile_url;
let _loc;
let txt = {
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
}

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
    const collections = document.querySelector(".movie-collection");
    const xhr = new XMLHttpRequest();
    xhr.onload = function () {

        badge.classList.add("yes");
        if (collections) {
            setTimeout(() => {
                collections.classList.remove("hide");
            }, 0);
            setTimeout(() => {
                collections.classList.remove("d-none");
            }, 300);
        }
        let r = getMovieRating(badge);
        addNotification(txt.movie.add[_loc], "success");
        if (r) addNotification(txt.movie.rating[_loc], "info");
    }
    xhr.open("GET", _add_movie + "?movie_db_id=" + id);
    xhr.send();
}

function removeMovie(badge) {
    const id = badge.getAttribute("id");
    const collections = document.querySelector(".movie-collection");
    const xhr = new XMLHttpRequest();
    xhr.onload = function () {

        badge.classList.remove("yes");
        if (collections) {
            setTimeout(() => {
                collections.classList.add("hide");
            }, 0);
            setTimeout(() => {
                collections.classList.add("d-none");
            }, 300);
        }
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
    const trash = rating.querySelector(".trash");
    stars.forEach(star => {
        star.addEventListener("mouseover", hoverStars);
        star.addEventListener("mouseleave", leaveStars);
        star.addEventListener("click", setMovieRating);
    })
    trash.addEventListener("click", setMovieRating);
    setStars(user);
    rating.classList.add("visible");
}

function terminateRating(badge) {
    const user = badge.parentElement;
    const rating = user.querySelector(".rating");
    if (rating) {
        const stars = rating.querySelectorAll(".star");
        const trash = rating.querySelector(".trash");
        stars.forEach(star => {
            star.removeEventListener("mouseover", hoverStars);
            star.removeEventListener("mouseleave", leaveStars);
            star.removeEventListener("click", setMovieRating);
        })
        trash.removeEventListener("click", setMovieRating);
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
    const movieCollection = document.querySelector(".movie-collection");

    if (movieCollection === null) {
        return;
    }

    const collections = movieCollection.querySelectorAll(".item");

    collections.forEach(collection => {
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
    xhr.open("GET", _app_movie_collection_toggle + "?c=" + id + "&m=" + movie + "&a=" + (collection.classList.contains("selected") ? "a" : "r"));
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

