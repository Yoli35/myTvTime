import {ToolTips} from "./ToolTips.js";

let gThis;

export class YoutubeIndexModule {

    constructor(globs) {
        gThis = this;
        this.app_youtube_video = globs.app_youtube_video;
        this.app_youtube_more = globs.app_youtube_more;
        this.app_youtube_add_video = globs.app_youtube_add_video;
        this.youtube_settings_save = globs.youtube_settings_save;
        this.app_youtube_video_series = globs.app_youtube_video_series;
        this.app_youtube_preview_video_series = globs.app_youtube_preview_video_series;
        this.app_youtube_count_videos = globs.app_youtube_count_videos;
        this.userId = globs.userId;
        this.locale = globs.locale;
        this.videoCount = globs.videoCount;
        this.toolTips = new ToolTips();
        this.xhr = new XMLHttpRequest();
        this.seeMore = document.getElementById('see-more');
        this.videoList = document.getElementById('result');
        this.ytLink = document.getElementById('link');
        this.ytPage = document.getElementById('page');
        this.ytSort = document.getElementById('sort');
        this.ytOrder = document.getElementById('order');
        this.ytReload = document.getElementById('reload');
        this.moreButton = document.getElementById('more');
        this.searchOnYt = document.querySelector('.search-on-yt');
        this.newListDialog = document.querySelector('#new-list-dialog');
        this.totalResults = 0;
        this.txt = {
            'episode': {'fr': 'Épisode', 'en': 'Episode', 'de': 'Episode', 'es': 'Episodio'},
            'episode_number': {'fr': 'Numéro', 'en': 'Number', 'de': 'Nummer', 'es': 'Número'},
            'episode_title': {'fr': 'Titre', 'en': 'Title', 'de': 'Titel', 'es': 'Título'},
            'part': {'fr': 'Partie', 'en': 'Part', 'de': 'Teil', 'es': 'Parte'},
            'published_at': {'fr': 'Publiée le', 'en': 'Published at', 'de': 'Veröffentlicht am', 'es': 'Publicado en'},
            'teil': {'fr': 'Partie', 'en': 'Part', 'de': 'Teil', 'es': 'Parte'},
            'year': {'fr': 'Année', 'en': 'Year', 'de': 'Jahr', 'es': 'Año'},
            'video': {'fr': 'Vidéo', 'en': 'Video', 'de': 'Video', 'es': 'Video'},
            'videos': {'fr': 'Vidéos', 'en': 'Videos', 'de': 'Videos', 'es': 'Videos'},
        }

        this.toolTips.init();
        this.initYoutube();
    }

    initYoutube() {
        this.ytLink.addEventListener("paste", (e) => {
            const link = e.clipboardData.getData('text');
            this.addVideo(link);
        });
        this.ytLink.addEventListener("keypress", this.pasteLinkWithKeyboard.bind(this));
        this.ytPage.addEventListener("click", this.savePageState.bind(this));
        this.ytSort.addEventListener("change", this.saveSortState.bind(this));
        this.ytOrder.addEventListener("change", this.saveOrderState.bind(this));
        this.ytReload.addEventListener("click", this.loadVideos.bind(this));
        this.moreButton?.addEventListener('click', this.loadVideos.bind(this));
        this.searchOnYt?.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            navigator.clipboard
                .readText()
                .then((clipText) => {
                    window.open('https://www.youtube.com/results?search_query=' + clipText, '_blank');
                });
        });

        document.addEventListener("visibilitychange", this.focusLink.bind(this));
        this.focusLink();

        setInterval(this.checkForNewVideos.bind(this), 600000); // 10 minutes

        // const seriesList = document.querySelectorAll('.video-series-item');
        // seriesList.forEach(series => {
        //     const header = series.querySelector('.header');
        //     header.addEventListener('click', (e) => {
        //         e.preventDefault();
        //         e.stopPropagation();
        //         this.loadSeries(series.getAttribute('data-id'));
        //     });
        // });
        // const newList = document.querySelector('.new-list');
        // this.initNewListDialog();
        // newList.addEventListener('click', (e) => {
        //     e.preventDefault();
        //     e.stopPropagation();
        //     // ouvrir le dialogue new-list-dialog
        //     document.querySelector('body').classList.add('frozen');
        //     gThis.newListDialog.showModal();
        // });
    }

    initNewListDialog() {
        /** @type {HTMLDialogElement} */
        const dialog = gThis.newListDialog;
        const matches = dialog.querySelectorAll('.match-item');

        dialog.addEventListener('close', () => {
            document.querySelector('body').classList.remove('frozen');
            if (dialog.returnValue === 'add-new-list') {

            }
        });
        matches.forEach((match) => {
            const input = match.querySelector('input[type=checkbox]');
            input.addEventListener('change', () => {
                match.classList.toggle('active');
                gThis.toggleMatch(match);
            });
            this.toggleMatch(match);
        });

        const formatInput = dialog.querySelector('#youtube_video_series_format');
        const previewButton = dialog.querySelector('button[value=preview]');
        formatInput.addEventListener('input', (e) => {
            const value = e.target.value;
            previewButton.disabled = !value.length;
        });
        previewButton.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const previewDialog = document.querySelector('#list-preview-dialog');
            const resultDiv = previewDialog.querySelector('.result-list');
            const format = dialog.querySelector('#youtube_video_series_format').value;
            const regex = dialog.querySelector('#youtube_video_series_regex').checked;
            const data = JSON.stringify({"format": format, "regex": regex, "matches": []});
            console.log(data);
            resultDiv.innerHTML = '';
            previewDialog.showModal();

            gThis.xhr.onload = function () {
                const response = JSON.parse(this.response);
                const videos = response['videos'];
                console.log(videos);
                const count = videos.length;
                const resultCountDiv = previewDialog.querySelector('.result-count');
                resultCountDiv.innerText = count + ' ' + (count > 1 ? gThis.txt.videos[gThis.locale] : gThis.txt.video[gThis.locale]);
                videos.forEach(video => {
                    const resultItemDiv = document.createElement('div');
                    resultItemDiv.classList.add('result-item');
                    const title = document.createElement('div');
                    title.classList.add('title');
                    title.innerText = video.title;
                    resultItemDiv.appendChild(title);
                    resultDiv.appendChild(resultItemDiv);
                });
            }
            gThis.xhr.open("GET", this.app_youtube_preview_video_series + '?data=' + data);
            gThis.xhr.send();
        });
    }

    toggleMatch(match) {
        const input = match.querySelector('input[type=checkbox]');
        const settings = match.querySelector('.match-item-settings');
        const expr = match.querySelector('input[id$=expr]');
        const name = match.querySelector('input[id$=name]');
        const position = match.querySelector('input[id$=position]');
        const occurrence = match.querySelector('input[id$=occurrence]');
        const type = match.querySelector('select[id$=type]');

        if (input.checked) {
            match.classList.add('active');
            settings.classList.add('active');
            expr.disabled = false;
            name.disabled = false;
            position.disabled = false;
            occurrence.disabled = false;
            type.disabled = false;
            // expr.removeAttribute('disabled');
            // name.removeAttribute('disabled');
            // position.removeAttribute('disabled');
            // occurrence.removeAttribute('disabled');
            // type.removeAttribute('disabled');
        } else {
            match.classList.remove('active');
            settings.classList.remove('active');
            expr.disabled = true;
            name.disabled = true;
            position.disabled = true;
            occurrence.disabled = true;
            type.disabled = true;
            // expr.setAttribute('disabled', 'disabled');
            // name.setAttribute('disabled', 'disabled');
            // position.setAttribute('disabled', 'disabled');
            // occurrence.setAttribute('disabled', 'disabled');
            // type.setAttribute('disabled', 'disabled');
        }
    }

    focusLink() {
        if (document.visibilityState === 'visible') {
            this.ytLink.focus();
            this.ytLink.select();
        }
    }

    checkForNewVideos() {
        this.xhr.onload = function () {
            const response = JSON.parse(this.response);
            const count = response['count'];

            if (gThis.videoCount !== count) {
                gThis.videoCount = count;
                gThis.ytReload.parentElement.classList.add("active");
                gThis.ytReload.click();
            }
        }
        this.xhr.open("GET", this.app_youtube_count_videos);
        this.xhr.send();
    }

    pasteLinkWithKeyboard(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            if (this.ytLink.value.length >= 11) {
                this.addVideo(this.ytLink.value);
            }
        }
    }

    loadVideos(e) {
        const reload = document.querySelector('.reload');
        const doReload = e.currentTarget.getAttribute('id') === 'reload';

        e.preventDefault();
        e.stopPropagation();

        const h1 = document.getElementById('h1');
        const videos = document.querySelectorAll('.yt-video');
        const options = {year: 'numeric', month: 'numeric', day: 'numeric'};
        const sort = document.querySelector("#sort").value;
        const order = document.querySelector("#order").value;

        this.totalResults = parseInt(h1.getAttribute('data-total-results'));
        let current_results = videos.length;

        this.xhr.onload = function () {
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
                aVideo.setAttribute("href", gThis.app_youtube_video + result['id'].toString());
                let newVideo = document.createElement("div");
                newVideo.setAttribute("class", "yt-video");
                let thumbnail = document.createElement("div");
                thumbnail.setAttribute("class", "yt-thumbnail");
                let img = document.createElement("img");
                img.setAttribute("src", result['thumbnailPath']);
                img.setAttribute("alt", result['title']);
                // Ajouter la durée
                let duration = document.createElement("div");
                duration.setAttribute("class", "duration");
                duration.appendChild(document.createTextNode(result['contentDuration']));
                // duration.appendChild(document.createTextNode(gThis.duration2Time(result['contentDuration'])));
                thumbnail.appendChild(img);
                thumbnail.appendChild(duration);
                if (result['tags'].length) {
                    let tags = document.createElement("div");
                    tags.classList.add("tags");
                    result['tags'].forEach(tag => {
                        let tagButton = gThis.newTagElement(tag);
                        tags.appendChild(tagButton);
                    })
                    thumbnail.appendChild(tags);
                }
                let details = document.createElement("div");
                details.setAttribute("class", "details");
                let channel = document.createElement("div");
                channel.setAttribute("class", "channel");
                let aChannel = document.createElement("a");
                let href = 'https://www.youtube.com/' + (result['channel']['customUrl'] ?? result['channel']['youtubeId']);
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
                info.appendChild(document.createTextNode(gThis.txt.published_at[gThis.locale] + ' : ' + released.toLocaleDateString(gThis.locale, options)));
                infos.appendChild(info);
                details.appendChild(channel);
                details.appendChild(infos);

                newVideo.appendChild(thumbnail);
                newVideo.appendChild(details);
                aVideo.appendChild(newVideo);
                newResult.appendChild(aVideo);

                gThis.videoList.insertBefore(newResult, gThis.seeMore);
            }
            //
            // If everything is displayed, we make the 'See more results' button disappear
            //
            if (current_results + count === gThis.totalResults) {
                gThis.seeMore.setAttribute("style", "display: none;");
            }
        }

        if (doReload)
            this.xhr.open("GET", this.app_youtube_more + '?id=' + this.userId + '&sort=' + sort + '&order=' + order + '&limit=' + current_results);
        else
            this.xhr.open("GET", this.app_youtube_more + '?id=' + this.userId + '&sort=' + sort + '&order=' + order + '&offset=' + current_results);
        this.xhr.send();
    }

    addVideo(link) {
        this.xhr.onload = function () {
            const response = JSON.parse(this.response);
            const gotoVideoPage = response['gotoVideoPage'];
            const userAlreadyLinked = response['userAlreadyLinked'];

            if (response['status'] === 'error') {
                alert(response['message']);
                return;
            }

            if (gotoVideoPage) {
                window.location.href = gThis.app_youtube_video + response['justAdded'] + (userAlreadyLinked ? '?user-already-linked=1' : '');
                return; // On slow machines / connections, the page is rebuild before the user is redirected
            }

            gThis.ytLink.value = "";
            gThis.ytLink.focus();

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

            gThis.showStatus(response);

            result.innerHTML = "Wait...";
            result.innerHTML = videosBlock.content;
        }
        this.xhr.open("GET", this.app_youtube_add_video + '?link=' + link);
        this.xhr.send();
    }

    savePageState(e) {
        const page = e.target.checked;

        gThis.xhr.onload = function () {
            const response = JSON.parse(this.response);
            gThis.showStatus(response);
        }
        gThis.xhr.open("GET", this.youtube_settings_save + '?page=' + (page ? 1 : 0));
        gThis.xhr.send();
    }

    saveSortState(e) {
        const sort = e.target.value;

        gThis.xhr.onload = function () {
            const response = JSON.parse(this.response);
            gThis.showStatus(response);
            setTimeout(function () {
                gThis.ytReload.parentElement.classList.add("active");
            }, 0);
        }
        gThis.xhr.open("GET", this.youtube_settings_save + '?sort=' + encodeURIComponent(sort));
        gThis.xhr.send();
    }

    saveOrderState(e) {
        const order = e.target.value;

        gThis.xhr.onload = function () {
            const response = JSON.parse(this.response);
            gThis.showStatus(response);
            setTimeout(function () {
                gThis.ytReload.parentElement.classList.add("active");
            }, 0);
        }
        gThis.xhr.open("GET", this.youtube_settings_save + '?order=' + encodeURIComponent(order));
        gThis.xhr.send();
    }

    loadSeries(id) {
        const series = document.querySelector('.video-series-item[data-id="' + id + '"]');
        if (series.classList.contains('loaded')) {
            series.classList.toggle('open');
            return;
        }
        const countDiv = series.querySelector('.count');
        countDiv.querySelector(".loading").classList.add('active');

        gThis.xhr.onload = function () {
            const response = JSON.parse(this.response);
            const videos = response['videos'];
            countDiv.querySelector(".loading").classList.remove('active');
            const count = videos.length;
            countDiv.querySelector("span").innerHTML = count + ' ' + (count > 1 ? gThis.txt.videos[gThis.locale] : gThis.txt.video[gThis.locale]);
            const videosDiv = series.querySelector('.videos');
            series.classList.add('open');

            videos.forEach(video => {
                console.log(video);
                const videoDiv = document.createElement('div');
                videoDiv.classList.add('video');
                const a = document.createElement('a');
                a.setAttribute('href', gThis.app_youtube_video + video.id);
                const thumbnail = document.createElement('div');
                thumbnail.classList.add('thumbnail');
                const img = document.createElement('img');
                img.setAttribute('src', video.thumbnailPath);
                img.setAttribute('alt', video.title);
                img.setAttribute('loading', 'lazy');
                thumbnail.appendChild(img);
                a.appendChild(thumbnail);
                const title = document.createElement('div');
                title.classList.add('title');
                title.innerText = video.title;
                a.appendChild(title);
                const publishedAt = document.createElement('div');
                publishedAt.classList.add('published-at');
                publishedAt.innerText = video.publishedAt;
                a.appendChild(publishedAt);
                const matches = document.createElement('div');
                matches.classList.add('matches');
                video.matches.forEach(match => {
                    const matchDiv = document.createElement('div');
                    matchDiv.classList.add('match');
                    matchDiv.innerHTML = gThis.txt[match.name][gThis.locale] ?? ('<i>' + match.name + '</i>');
                    matchDiv.innerHTML += ' ' + match.value;
                    matches.appendChild(matchDiv);
                });
                a.appendChild(matches);
                videoDiv.appendChild(a);
                videosDiv.appendChild(videoDiv);
            });

            series.classList.add('loaded');
            series.classList.add('open');
        }
        gThis.xhr.open("GET", this.app_youtube_video_series + id);
        gThis.xhr.send();
    }

    showStatus(response) {
        const status = response['status'];
        const message = '<strong>' + response['message'] + '</strong><br>' + response['subMessage'];

        const statusDiv = document.getElementById('status');
        statusDiv.innerHTML = message;
        const width = statusDiv.getBoundingClientRect().width;
        statusDiv.setAttribute("style", "left: calc(50% - " + (width / 2) + "px);");
        statusDiv.classList.add(status);
        this.ytLink.focus();

        setTimeout(() => {
            statusDiv.classList.remove(status);
        }, 5000);
        setTimeout(() => {
            statusDiv.innerHTML = "";
            statusDiv.removeAttribute("style");
        }, 5250);
    }

    newTagElement(tag, list = false) {

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

    duration2Time(duration) {

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
}

