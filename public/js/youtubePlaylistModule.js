import {ToolTips} from "./ToolTips.js";

let gThis;

export class YoutubePlaylistModule {

    constructor(globs) {
        gThis = this;
        this.app_youtube_video = globs.app_youtube_video;
        this.app_youtube_add_video = globs.app_youtube_add_video;
        this.toolTips = new ToolTips();
        this.xhr = new XMLHttpRequest();

        this.toolTips.init();
        this.initYoutube();
    }

    initYoutube() {
        const addVideoDivs = document.querySelectorAll('.add-video');
        addVideoDivs.forEach((addVideoDiv) => {
            addVideoDiv.addEventListener('click', (e) => {
                e.preventDefault();
                const link = addVideoDiv.getAttribute('data-link');
                gThis.addVideo(link, e.currentTarget);
            });
        });

        const copyLinkDiv = document.querySelector('#copy-link');
        copyLinkDiv.addEventListener('click', (e) => {
            e.preventDefault();
            const link = copyLinkDiv.getAttribute('data-link');
            navigator.clipboard.writeText(link).then(() => {
                const flashMessagesDiv = document.querySelector('.flash-messages');
                const flashMessageDiv = document.createElement('div');
                flashMessageDiv.classList.add('flash-message');
                flashMessageDiv.classList.add('flash-message-success');
                flashMessageDiv.innerHTML = 'Link copied to clipboard';
                flashMessagesDiv.appendChild(flashMessageDiv);
                // <div class="close"><i class="fa-solid fa-xmark"></i></div>
                const closeDiv = document.createElement('div');
                closeDiv.classList.add('close');
                closeDiv.innerHTML = '<i class="fa-solid fa-xmark"></i>';
                closeDiv.addEventListener('click', () => {
                    flashMessageDiv.remove();
                });
                flashMessageDiv.appendChild(closeDiv);
            });
        });
    }

    addVideo(link, addVideoDiv) {
        this.xhr.onload = function () {
            let {status, message, subMessage, videoId} = JSON.parse(this.response);
            const channelDiv = addVideoDiv.closest('.channel');
            const aToVideo = document.createElement('a');
            aToVideo.href = gThis.app_youtube_video + videoId;
            aToVideo.innerHTML = '<i class="fas fa-arrow-right-long"></i>';
            channelDiv.appendChild(aToVideo);
            addVideoDiv.remove();
            const videoDiv = channelDiv.closest('.video');
            videoDiv.classList.add('watched');

            const flashMessagesDiv = document.querySelector('.flash-messages');
            const flashMessageDiv = document.createElement('div');
            flashMessageDiv.classList.add('flash-message');
            flashMessageDiv.classList.add('flash-message-' + status);
            flashMessageDiv.innerHTML = message + '<br>' + subMessage;
            flashMessagesDiv.appendChild(flashMessageDiv);
            // <div class="close"><i class="fa-solid fa-xmark"></i></div>
            const closeDiv = document.createElement('div');
            closeDiv.classList.add('close');
            closeDiv.innerHTML = '<i class="fa-solid fa-xmark"></i>';
            closeDiv.addEventListener('click', () => {
                flashMessageDiv.remove();
            });
            flashMessageDiv.appendChild(closeDiv);
        }
        this.xhr.open("GET", this.app_youtube_add_video + '?playlist=1&link=' + link);
        this.xhr.send();
    }
}

