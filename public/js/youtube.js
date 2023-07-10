function initYoutube(id, locale, paths) {

    const _user_id = id;
    const _locale = locale;
    const _yt_video_page = paths[0].substring(0, paths[0].length - 1);
    const _yt_videos_more = paths[1];

    const moreButton = document.getElementById('more');
    const seeMore = document.getElementById('see-more');
    const videoList = document.getElementById('result');
    const txt = {
        'published_at': {'fr': 'Publiée le', 'en': 'Published at', 'de': 'Veröffentlicht am', 'es': 'Publicado en'},
    }

    const ytLink = document.getElementById('link');
    // ytLink.addEventListener("input", () => {
    //     setTimeout(function () {
    //         ytLink.value = "";
    //     }, 2000);
    // });
    document.addEventListener("visibilitychange", () => {
        if (document.visibilityState === 'visible') {
            ytLink.focus();
            ytLink.select();
        }
    });

    setInterval(() => {
        const redirect = document.querySelector(".redirect");
        if (redirect) {
            const video = redirect.getAttribute("data-id");
            const userAlreadyLinked = redirect.getAttribute("data-user-already-linked");
            redirect.remove();
            window.location.href = _yt_video_page + video + (userAlreadyLinked ? '?user-already-linked=1' : '');
        }
    }, 1000);

    if (moreButton) {

        moreButton.addEventListener('click', () => {

            const h1 = document.getElementById('h1');
            const videos = document.getElementsByClassName('yt-video');
            const options = {year: 'numeric', month: 'numeric', day: 'numeric'};
            const sort = document.querySelector("#sort").value;
            const order = document.querySelector("#order").value;

            total_results = parseInt(h1.getAttribute('data-total-results'));
            let current_results = videos.length;

            const xhr = new XMLHttpRequest();
            xhr.onload = function () {
                const response = JSON.parse(this.response);
                const results = response['results'];
                const count = results.length;

                for (let i = 0; i < count; i++) {
                    let result = results[i];
                    let newResult = document.createElement("div");
                    newResult.setAttribute("class", "yt-result");
                    let aVideo = document.createElement("a");
                    aVideo.setAttribute("href", _yt_video_page + result['id'].toString());
                    let newVideo = document.createElement("div");
                    newVideo.setAttribute("class", "yt-video");
                    let thumbnail = document.createElement("div");
                    thumbnail.setAttribute("class", "yt-thumbnail");
                    let img = document.createElement("img");
                    img.setAttribute("src", result['thumbnailMediumPath']);
                    img.setAttribute("alt", result['title']);
                    // Ajouter la durée
                    let duration = document.createElement("div");
                    duration.setAttribute("class", "duration");
                    duration.appendChild(document.createTextNode(duration2Time(result['contentDuration'])));
                    thumbnail.appendChild(img);
                    thumbnail.appendChild(duration);
                    if (result['tags'].length) {
                        let tags = document.createElement("div");
                        tags.classList.add("tags");
                        result['tags'].forEach(tag => {
                            let tagButton = newTagElement(tag);
                            tags.appendChild(tagButton);
                        })
                        thumbnail.appendChild(tags);
                    }
                    let details = document.createElement("div");
                    details.setAttribute("class", "details");
                    let channel = document.createElement("div");
                    channel.setAttribute("class", "channel");
                    let aChannel = document.createElement("a");
                    let href = 'https://www.youtube.com/' + (result['channel']['customUrl'] === null ? result['channel']['youtubeId'] : result['channel']['customUrl']);
                    aChannel.setAttribute("href", href);
                    aChannel.setAttribute("target", "_blank");
                    let span = document.createElement("span");
                    span.setAttribute("data-descr", result['channel']['title']);
                    if (result['channel']['thumbnailDefaultUrl']) {
                        let imgChannel = document.createElement("img");
                        imgChannel.setAttribute("src", result['channel']['thumbnailDefaultUrl']);
                        imgChannel.setAttribute("alt", result['channel']['title']);
                        span.appendChild(imgChannel);
                    } else {
                        let fChannel = document.createTextNode(result['channel']['title'].charAt(0));
                        span.appendChild(fChannel);
                    }
                    aChannel.appendChild(span);
                    channel.appendChild(aChannel);
                    let infos = document.createElement("div");
                    infos.setAttribute("class", "infos");
                    let info = document.createElement("div");
                    info.setAttribute("class", "info");
                    info.appendChild(document.createTextNode(result['title']));
                    infos.appendChild(info);
                    info = document.createElement("div");
                    info.setAttribute("class", "info");
                    let dateT = result['publishedAt'];
                    let released = new Date(dateT);
                    info.appendChild(document.createTextNode(txt.published_at[_locale] + ' : ' + released.toLocaleDateString(undefined, options)));
                    infos.appendChild(info);
                    details.appendChild(channel);
                    details.appendChild(infos);

                    newVideo.appendChild(thumbnail);
                    newVideo.appendChild(details);
                    aVideo.appendChild(newVideo);
                    newResult.appendChild(aVideo);

                    videoList.insertBefore(newResult, seeMore);
                }
                //
                // If everything is displayed, we make the 'See more results' button disappear
                //
                if (current_results + count === total_results) {
                    seeMore.setAttribute("style", "display: none;");
                }
            }

            xhr.open("GET", _yt_videos_more + '?id=' + _user_id + '&sort=' + sort + '&order=' + order + '&offset=' + current_results);
            xhr.send();
        });
    }
}

function newTagElement(tag, list = false) {

    let newTagButton = document.createElement("div");
    newTagButton.classList.add("tag");
    newTagButton.appendChild(document.createTextNode('#' + tag['label']));

    if (list) {
        let closeButton = document.createElement("div");
        closeButton.classList.add("close");
        closeButton.setAttribute("data-id", tag['id']);
        let circleXMark = document.createElement("i");
        circleXMark.classList.add("fa-solid", "fa-circle-xmark");
        closeButton.appendChild(circleXMark);
        newTagButton.appendChild(closeButton);
    }

    return newTagButton;
}

function duration2Time(duration) {

    let time;
    let hours = Math.floor(duration / 3600);
    let minutes = Math.floor(duration / 60);
    let secondes = (duration % 60);

    if (hours > 0 || minutes > 0) {
        secondes = (secondes < 10) ? '0' + secondes.toString() : secondes.toString();
    }
    if (hours > 0) {
        minutes = (minutes < 10) ? '0' + minutes.toString() : minutes.toString();
        time = hours.toString() + ':' + minutes;
    } else {
        time = minutes.toString();
    }
    time += ':' + secondes;

    return time;
}