let total_results;
let _user_id, _locale;
let _tik_tok_more, _tik_tok_video;
const txt = {
    'added_at': {'fr': 'Ajoutée le ', 'en': 'Added at', 'de': 'Hinzugefügt am', 'es': 'Añadido en'},
    '': {'fr': '', 'en': '', 'de': '', 'es': ''},
}

function initTiktok(locale, paths, id) {

    _user_id = id;
    _locale = locale;
    _tik_tok_more = paths[0];
    _tik_tok_video = paths[1].substring(0, paths[1].length - 1);

    const myLink = document.getElementById('link');
    myLink.addEventListener("input", () => {
        setTimeout(function () {
            $(myLink).val("");
        }, 2000);
    });

    document.addEventListener("visibilitychange", () => {
        if (document.visibilityState === 'visible') {
            myLink.focus();
            myLink.select();
            // navigator.clipboard.readText().then(
            //     clipText => input.val = clipText);
        }
    });

    const moreButton = document.getElementById('more');
    const seeMore = document.getElementById('see-more');
    const videoList = document.getElementById('result');

    if (moreButton) {

        moreButton.addEventListener('click', () => {

            const h1 = document.getElementById('h1');
            const videos = document.getElementsByClassName('tt-video');
            const options = {weekday: "long", year: 'numeric', month: 'long', day: 'numeric', hour12: false};

            total_results = parseInt(h1.getAttribute('data-total-results'));
            let current_results = videos.length;

            const xhr = new XMLHttpRequest();
            xhr.onload = function() {
                const response = JSON.parse(this.response);
                const results = response['results'];
                const count = results.length;

                for (let i = 0; i < count; i++) {
                    let result = results[i];
                    let newVideo = document.createElement("div");
                    newVideo.setAttribute("class", "tt-video");
                    let aVideo = document.createElement("a");
                    aVideo.setAttribute("href", _tik_tok_video + result['id'].toString());
                    let thumbnail = document.createElement("div");
                    thumbnail.setAttribute("class", "thumbnail");
                    let img = document.createElement("img");
                    img.setAttribute("src", result['thumbnail_url']);
                    img.setAttribute("alt", result['title']);
                    let title = document.createElement("div");
                    title.setAttribute("class", "title");
                    title.appendChild(document.createTextNode(result['title']));
                    thumbnail.appendChild(img);
                    thumbnail.appendChild(title);
                    let details = document.createElement("div");
                    details.setAttribute("class", "details");
                    let author = document.createElement("div");
                    author.setAttribute("class", "author");
                    let aAuthor = document.createElement('a');
                    aAuthor.setAttribute("href", result['author_url']);
                    aAuthor.setAttribute("target", "_blank");
                    let span = document.createElement("span");
                    span.setAttribute("data_desc", result['author_name']);
                    span.appendChild(document.createTextNode(result['author_name'].charAt(0)));
                    aAuthor.appendChild(span);
                    author.appendChild(aAuthor);
                    let infos = document.createElement("div");
                    infos.setAttribute("class", "infos");
                    let info = document.createElement("div");
                    info.setAttribute("class", "info mt-2");
                    let dateT = result['added_at'].substring(0, 10) + 'T' + result['added_at'].substring(11, 19);
                    let added = new Date(dateT);
                    info.appendChild(document.createTextNode(txt.added_at[_locale] + ' : ' + added.toLocaleTimeString(undefined, options)));
                    infos.appendChild(info);
                    details.appendChild(author);
                    details.appendChild(infos);
                    aVideo.appendChild(thumbnail);
                    aVideo.appendChild(details);
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

            xhr.open("GET", _tik_tok_more + '?id=' + _user_id + '&offset=' + current_results);
            xhr.send();
        });
    }
}