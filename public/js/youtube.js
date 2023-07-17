function initYoutube(id, locale, paths) {

    const _user_id = id;
    const _locale = locale;
    const _yt_video_page = paths[0].slice(0, -1);
    const _yt_videos_more = paths[1];
    const _yt_video_add = paths[2];
    const _youtube_settings_save = paths[3];

    const moreButton = document.getElementById('more');
    const seeMore = document.getElementById('see-more');
    const videoList = document.getElementById('result');
    const txt = {
        'published_at': {'fr': 'Publiée le', 'en': 'Published at', 'de': 'Veröffentlicht am', 'es': 'Publicado en'},
    }

    const ytLink = document.getElementById('link');
    const ytPage = document.getElementById('page');
    const ytSort = document.getElementById('sort');
    const ytOrder = document.getElementById('order');
    const ytReload = document.getElementById('reload');

    ytLink.addEventListener("paste", (e) => {
        const link = e.clipboardData.getData('text');
        addVideo(link);
    });
    ytLink.addEventListener("keypress", (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            if (ytLink.value.length >= 11) {
                addVideo(ytLink.value);
            }
        }
    });
    ytPage.addEventListener("click", savePageState);
    ytSort.addEventListener("change", saveSortState);
    ytOrder.addEventListener("change", saveOrderState);
    ytReload.addEventListener("click", loadVideos);
    moreButton?.addEventListener('click', loadVideos);

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

    function loadVideos(e) {
        const reload = document.querySelector('.reload');
        const doReload = e.currentTarget.getAttribute('id') === 'reload';

        e.preventDefault();
        e.stopPropagation();

        const h1 = document.getElementById('h1');
        const videos = document.querySelectorAll('.yt-video');
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

            if (doReload) {
                videos.forEach(video => {
                    video.closest('.yt-result').remove();
                });
                reload.classList.remove('active');
            }

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

        if (doReload)
            xhr.open("GET", _yt_videos_more + '?id=' + _user_id + '&sort=' + sort + '&order=' + order + '&limit=' + current_results);
        else
            xhr.open("GET", _yt_videos_more + '?id=' + _user_id + '&sort=' + sort + '&order=' + order + '&offset=' + current_results);
        xhr.send();
    }
    function addVideo(link) {
        const xhr = new XMLHttpRequest();
        xhr.onload = function () {
            const response = JSON.parse(this.response);
            const gotoVideoPage = response['gotoVideoPage'];
            const userAlreadyLinked = response['userAlreadyLinked'];

            if (gotoVideoPage) {
                window.location.href = _yt_video_page + response['justAdded'] + (userAlreadyLinked ? '?user-already-linked=1' : '');
            }

            ytLink.value = "";
            ytLink.focus();

            const h1 = document.getElementById('h1');
            const h2 = document.getElementById('time-spend');
            const result = document.querySelector(".result");
            const videosBlock = response['videosBlock'];
            const videoCount = response['videoCount'];
            const h1innerText = response['h1innerText'];
            const time2Human = response['time2Human'];

            h1.setAttribute("data-total-results", videoCount);
            h1.innerHTML = h1innerText;
            h2.innerText = time2Human;

            showStatus(response);

            result.innerHTML = "Wait...";
            result.innerHTML = videosBlock.content;
        }
        xhr.open("GET", _yt_video_add + '?link=' + link);
        xhr.send();
    }

    function savePageState(e) {
        const page = e.target.checked;

        const xhr = new XMLHttpRequest();
        xhr.onload = function () {
            const response = JSON.parse(this.response);
            showStatus(response);
        }
        xhr.open("GET", _youtube_settings_save + '?page=' + (page ? 1 : 0));
        xhr.send();
    }

    function saveSortState(e) {
        const sort = e.target.value;

        const xhr = new XMLHttpRequest();
        xhr.onload = function () {
            const response = JSON.parse(this.response);
            showStatus(response);
            setTimeout(function () {
                ytReload.parentElement.classList.add("active");
            }, 0);
        }
        xhr.open("GET", _youtube_settings_save + '?sort=' + encodeURIComponent(sort));
        xhr.send();
    }

    function saveOrderState(e) {
        const order = e.target.value;

        const xhr = new XMLHttpRequest();
        xhr.onload = function () {
            const response = JSON.parse(this.response);
            showStatus(response);
            setTimeout(function () {
                ytReload.parentElement.classList.add("active");
            }, 0);
        }
        xhr.open("GET", _youtube_settings_save + '?order=' + encodeURIComponent(order));
        xhr.send();
    }

    function showStatus(response) {
        const status = response['status'];
        const message = '<strong>' + response['message'] + '</strong><br>' + response['subMessage'];

        const statusDiv = document.getElementById('status');
        statusDiv.innerHTML = message;
        const width = statusDiv.getBoundingClientRect().width;
        statusDiv.setAttribute("style", "left: calc(50% - " + (width / 2) + "px);");
        statusDiv.classList.add(status);
        ytLink.focus();

        setTimeout(() => {
            statusDiv.classList.remove(status);
        }, 5000);
        setTimeout(() => {
            statusDiv.innerHTML = "";
            statusDiv.removeAttribute("style");
        }, 5250);
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