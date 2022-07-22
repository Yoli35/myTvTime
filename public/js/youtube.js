let json, ids, preview, infos, exportFile;
let _user_id;
let _locale;
let _yt_video_page, _yt_videos_more;
let _yt_id_url;

function initYoutube(id, locale, paths) {

    _user_id = id;
    _locale = locale;
    _yt_video_page = paths[0].substring(0, paths[0].length - 1);;
    _yt_videos_more = paths[1];

    const moreButton = document.getElementById('more');
    const seeMore = document.getElementById('see-more');
    const videoList = document.getElementById('result');
    const txt = {
        'published_at': {'fr': 'Publiée le', 'en': 'Published at', 'de': 'Veröffentlicht am', 'es': 'Publicado en'},
    }

    if (moreButton) {

        moreButton.addEventListener('click', () => {

            const h1 = document.getElementById('h1');
            const videos = document.getElementsByClassName('yt-video');
            const options = { year: 'numeric', month: 'numeric', day: 'numeric' };

            total_results = parseInt(h1.getAttribute('data-total-results'));
            let current_results = videos.length;

            $.ajax({
                url: _yt_videos_more,
                method: 'GET',
                data: {id: _user_id, offset: current_results},
                success: function (data) {
                    let results = data['results'];
                    let count = results.length;

                    for (let i = 0; i < count; i++) {
                        let result = results[i];
                        let newVideo = document.createElement("div");
                        newVideo.setAttribute("class", "yt-video");
                        newVideo.setAttribute("id", result['id']);
                        let aVideo = document.createElement("a");
                        aVideo.setAttribute("href", _yt_video_page + result['id'].toString());
                        let thumbnail = document.createElement("div");
                        thumbnail.setAttribute("class", "thumbnail");
                        let img = document.createElement("img");
                        img.setAttribute("src", result['thumbnail_medium_path']);
                        img.setAttribute("alt", result['title']);
                        // Ajouter la durée
                        thumbnail.appendChild(img);
                        let details = document.createElement("div");
                        details.setAttribute("class", "details");
                        let channel = document.createElement("div");
                        channel.setAttribute("class", "channel");
                        let aChannel = document.createElement("a");
                        aChannel.setAttribute("href", result['channel']['custom_url'] ? _yt_custom_url : _yt_id_url);
                        aChannel.setAttribute("target", "_blank");
                        let span = document.createElement("span");
                        span.setAttribute("data-descr", result['channel']['title']);
                        if (result['channel']['thumbnail_default_url']) {
                            let imgChannel = document.createElement("img");
                            imgChannel.setAttribute("src", result['channel']['thumbnail_default_url']);
                            imgChannel.setAttribute("alt", result['channel']['title']);
                            span.appendChild(imgChannel);
                        }
                        else {
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
                        let dateT = result['published_at'];
                        let released = new Date(dateT);
                        info.appendChild(document.createTextNode(txt.published_at[_locale] + ' : ' + released.toLocaleDateString(undefined, options)));
                        infos.appendChild(info);
                        details.appendChild(channel);
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
            })
        });
    }
}

