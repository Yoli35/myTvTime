import {ToolTips} from "./ToolTips.js";

let gThis;

export class YoutubePlaylistModule {

    constructor() {
        const globsData = document.querySelector('#global-data')?.textContent;
        const globs = JSON.parse(globsData);
        gThis = this;
        this.app_youtube_video = globs.app_youtube_video;
        this.app_youtube_add_video = globs.app_youtube_add_video;
        this.app_youtube_remove_video = globs.app_youtube_remove_video;
        this.tags = globs.tags;
        this.inputTagSelector = "#new-tag";
        this.toolTips = new ToolTips();
        this.xhr = new XMLHttpRequest();

        this.toolTips.init();
        this.initYoutube();
        this.autocomplete();
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

    autocomplete() {
        /** @type {HTMLInputElement} */
        const inputTag = document.querySelector(this.inputTagSelector);
        const field = inputTag.closest(".tags-field");
        // const selector = "input[id=" + inputTag + "]";
        inputTag.focus();

        this.createTagList(this.inputTagSelector);

        inputTag.addEventListener("input", () => {
            const tagList = field.querySelector(".tag-list");
            let value = gThis.removeAccent(inputTag.value);
            if (!value.length) {
                gThis.hideList(tagList);
                return;
            }
            if (!tagList.classList.contains("visible")) {
                gThis.showList(tagList);
            }
            const tagItems = tagList.querySelectorAll(".tag-item");
            const activeTag = tagList.querySelector(".tag-item.active");
            activeTag?.classList.remove("active");
            tagItems.forEach((tagItem) => {
                const label = tagItem.getAttribute("data-value");
                if (label.includes(value)) {
                    tagItem.style.display = "block";
                } else {
                    tagItem.style.display = "none";
                }
            });
        });

        inputTag.addEventListener("keydown", function (e) {
            const tagList = field.querySelector(".tag-list");
            if (e.keyCode === 40) { // arrow DOWN key
                e.preventDefault();
                // e.stopImmediatePropagation();
                e.stopPropagation();
                if (!tagList.classList.contains("visible")) {
                    gThis.showList(tagList);
                }
                gThis.setActiveTagItem('next');
            } else if (e.keyCode === 38) { // arrow UP key
                e.preventDefault();
                // e.stopImmediatePropagation();
                e.stopPropagation();
                if (!tagList.classList.contains("visible")) {
                    gThis.showList(tagList);
                }
                gThis.setActiveTagItem('previous');
            } else if (e.keyCode === 13) { // the ENTER key
                e.preventDefault();
                const activeTag = tagList.querySelector(".active");
                if (activeTag) {
                    e.stopImmediatePropagation();
                    activeTag.click();
                } else {
                    let tags = field.parentElement.querySelector(".tags").querySelectorAll(".tag");
                    if (tags.length) gThis.applyTags();
                }
            }
        });

        document.addEventListener("click", (e) => {
            const tagList = field.querySelector(".tag-list");
            if (e.target !== tagList) this.hideList(tagList);
        });
    }

    createTagList(inputElement) {
        const searchTag = document.querySelector(inputElement);
        const field = searchTag.closest(".tags-field");
        const tagList = document.createElement("div");
        tagList.classList.add("tag-list");
        field.appendChild(tagList);

        /** var {{'id': number, 'label': string, 'selected': boolean}} tag */
        this.tags.forEach((tag) => {
            const tagItem = document.createElement("div");
            tagItem.classList.add("tag-item");
            tagItem.setAttribute("data-id", tag.id);
            tagItem.setAttribute("data-value", gThis.removeAccent(tag.label));
            tagItem.innerText = tag.label;
            if (this.inputTagSelector === "#search-tag") {
                tagItem.addEventListener("click", gThis.addSearchTag);
            } else {
                tagItem.addEventListener("click", gThis.addVideoTag);
            }
            tagList.appendChild(tagItem);
        });
    }

    showList(tagList) {
        const tagItems = tagList.querySelectorAll(".tag-item");
        tagItems.forEach((tagItem) => {
            const id = parseInt(tagItem.getAttribute("data-id"));
            const tag = gThis.tagFromList(id);
            if (tag.selected)
                tagItem.classList.add("selected");
            else
                tagItem.classList.remove("selected");
            tagItem.classList.remove("other");
        });
        gThis.currentFocus = -1;
        tagList.classList.add("visible");
        setTimeout(() => {
            tagList.classList.add("show");
        }, 0);
    }

    hideList(tagList) {
        tagList.classList.remove("show");
        setTimeout(() => {
            tagList.classList.remove("visible");
        }, 250);
        const tagItems = tagList.querySelectorAll(".tag-item");
        tagItems.forEach((tagItem) => {
            tagItem.classList.remove("active");
        });
    }

    /** @param {number} id
     * @param {boolean|null} selected
     * @return {Tag|null}
     */
    tagFromList(id, selected = null) {
        for (let i = 0; i < gThis.tags.length; i++) {
            /** @var {Tag} tag */
            let tag = gThis.tags[i];
            if (id === tag.id) {
                if (selected !== null) tag.selected = selected;
                return tag;
            }
        }
        return null;
    }

    removeAccent(str) {
        str = str.toLowerCase();

        let from = `àáäâèéëêìíïîòóöôùúüûñç`;
        let to = "aaaaeeeeiiiioooouuuunc";
        for (let i = 0, l = from.length; i < l; i++) {
            str = str.replace(new RegExp(from.charAt(i), 'g'), to.charAt(i));
        }

        return str;
    }

    flashMessage(status, message, subMessage) {
        const flashMessagesDiv = document.querySelector('.flash-messages');
        const flashMessageDiv = document.createElement('div');
        flashMessageDiv.classList.add('flash-message');
        flashMessageDiv.classList.add(status);
        flashMessageDiv.innerHTML = message + (subMessage.length ? ('<br>' + subMessage) : '');
        flashMessagesDiv.appendChild(flashMessageDiv);

        /*const closeDiv = document.createElement('div');
        closeDiv.classList.add('close');
        closeDiv.innerHTML = '<i class="fa-solid fa-xmark"></i>';
        closeDiv.addEventListener('click', () => {
            flashMessageDiv.remove();
        });
        flashMessageDiv.appendChild(closeDiv);*/

        // <div class="closure-countdown">
        //     <div>
        //         <i class="fa-solid fa-xmark"></i>
        //     </div>
        //     <div class="circle-start"></div>
        //     <div class="circle-end"></div>
        // </div>
        const closureCountdownDiv = document.createElement('div');
        closureCountdownDiv.classList.add('closure-countdown');
        const div1 = document.createElement('div');
        div1.innerHTML = '<i class="fa-solid fa-xmark"></i>';
        closureCountdownDiv.appendChild(div1);
        const div2 = document.createElement('div');
        div2.classList.add('circle-start');
        closureCountdownDiv.appendChild(div2);
        const div3 = document.createElement('div');
        div3.classList.add('circle-end');
        closureCountdownDiv.appendChild(div3);
        flashMessageDiv.appendChild(closureCountdownDiv);

        const closure = flashMessageDiv.querySelector('.closure-countdown');
        const start = new Date();
        const i = setInterval(() => {
            const now = new Date();
            const progress = 360 * (1 - ((now - start) / 30000) % 1);
            closure.style.backgroundImage = `conic-gradient(var(--clr) 0deg, var(--clr) ${progress}deg, var(--cd) ${progress}deg, var(--cd) 360deg)`;
        }, 100);
        setTimeout(() => {
            clearInterval(i);
            closure.style.backgroundImage = 'none';
        }, 30000);

        setTimeout(() => {
            flashMessageDiv.remove();
        }, 30000);
    }
}

