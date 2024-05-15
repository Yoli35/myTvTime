import {ToolTips} from "./ToolTips.js";

let gThis;

export class YoutubePlaylistModule {

    constructor(globs) {
        gThis = this;
        this.app_youtube_video = globs.app_youtube_video;
        this.app_youtube_add_video = globs.app_youtube_add_video;
        this.app_youtube_remove_video = globs.app_youtube_remove_video;
        this.toolTips = new ToolTips();
        this.xhr = new XMLHttpRequest();

        this.toolTips.init();
        this.initYoutube();
    }

    initYoutube() {
        const addVideoDivs = document.querySelectorAll('.add-video');
        addVideoDivs.forEach((addVideoDiv) => {
            addVideoDiv.addEventListener('click', this.setAddListener);
        });
        const removeVideoDivs = document.querySelectorAll('.remove-video');
        removeVideoDivs.forEach((removeVideoDiv) => {
            removeVideoDiv.addEventListener('click', this.setRemoveListener);
        });

        const copyLinkDiv = document.querySelector('#copy-link');
        copyLinkDiv.addEventListener('click', (e) => {
            e.preventDefault();
            const link = copyLinkDiv.getAttribute('data-link');
            navigator.clipboard.writeText(link).then(() => {
                gThis.flashMessage('success', 'Link copied to clipboard!', '');
            });
        });

        const toggleSortListDiv = document.querySelector('#sort-list');
        toggleSortListDiv.addEventListener('click', (e) => {
            e.preventDefault();
            const videos = document.querySelector('.videos');
            videos.classList.toggle('invert');
        });
    }

    setAddListener(e) {
        const div = e.currentTarget;
        e.preventDefault();
        const link = div.getAttribute('data-link');
        gThis.addVideo(link, e.currentTarget);
    }

    setRemoveListener(e) {
        const div = e.currentTarget;
        e.preventDefault();
        const id = div.getAttribute('data-id');
        gThis.removeVideo(id, e.currentTarget);
    }

    addVideo(link, addVideoDiv) {
        this.xhr.onload = function () {
            let {status, message, subMessage, videoId} = JSON.parse(this.response);
            const videoToolsDiv = addVideoDiv.closest('.video-tools');
            const toolsDiv = videoToolsDiv.querySelector('.tools');
            const aToVideo = document.createElement('a');
            aToVideo.href = gThis.app_youtube_video + videoId;
            aToVideo.innerHTML = '<i class="fas fa-arrow-right-long"></i>';
            toolsDiv.appendChild(aToVideo);
            videoToolsDiv.setAttribute('data-title', videoToolsDiv.getAttribute('data-title') + videoId);
            addVideoDiv.classList.remove('add-video');
            addVideoDiv.classList.add('remove-video');
            addVideoDiv.innerHTML = '<i class="fas fa-minus"></i>';
            addVideoDiv.setAttribute('data-id', videoId);
            addVideoDiv.removeEventListener('click', gThis.setAddListener);
            addVideoDiv.addEventListener('click', gThis.setRemoveListener);
            const videoDiv = videoToolsDiv.closest('.video');
            videoDiv.classList.add('watched');

            gThis.flashMessage(status, message, subMessage);
        }
        this.xhr.open("GET", this.app_youtube_add_video + '?playlist=1&link=' + link);
        this.xhr.send();
    }

    removeVideo(id, removeVideoDiv) {
        this.xhr.onload = function () {
            let {status, message, subMessage} = JSON.parse(this.response);
            const videoToolsDiv = removeVideoDiv.closest('.video-tools');
            const aToVideo = videoToolsDiv.querySelector('a');
            aToVideo.remove();
            videoToolsDiv.setAttribute('data-title', 'Id: ' + videoToolsDiv.getAttribute('data-id') + ' / Youtube video id: ');
            removeVideoDiv.classList.remove('remove-video');
            removeVideoDiv.classList.add('add-video');
            removeVideoDiv.innerHTML = '<i class="fas fa-plus"></i>';
            removeVideoDiv.removeAttribute('data-id');
            removeVideoDiv.removeEventListener('click', gThis.setRemoveListener);
            removeVideoDiv.addEventListener('click', gThis.setAddListener);
            const videoDiv = videoToolsDiv.closest('.video');
            videoDiv.classList.remove('watched');

            gThis.flashMessage(status, message, subMessage);
        }
        this.xhr.open("GET", this.app_youtube_remove_video + id + '?playlist=1');
        this.xhr.send();
    }

    flashMessage(status, message, subMessage) {
        const flashMessagesDiv = document.querySelector('.flash-messages');
        const flashMessageDiv = document.createElement('div');
        flashMessageDiv.classList.add('flash-message');
        flashMessageDiv.classList.add(status);
        flashMessageDiv.innerHTML = message + (subMessage.length ? ('<br>' + subMessage) : '');
        flashMessagesDiv.appendChild(flashMessageDiv);

        const closeDiv = document.createElement('div');
        closeDiv.classList.add('close');
        closeDiv.innerHTML = '<i class="fa-solid fa-xmark"></i>';
        closeDiv.addEventListener('click', () => {
            flashMessageDiv.remove();
        });
        flashMessageDiv.appendChild(closeDiv);
    }
}

