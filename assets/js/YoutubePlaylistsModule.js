import {ToolTips} from "./ToolTips.js";

let gThis;

export class YoutubePlaylistsModule {

    constructor() {
        const globsData = document.querySelector('#global-data')?.textContent;
        const globs = JSON.parse(globsData);
        gThis = this;
        this.app_youtube_add_playlist = globs.app_youtube_add_playlist;
        this.ytLink = document.getElementById('new-playlist');
        this.filter = document.getElementById('playlist-filter');
        this.toolTips = new ToolTips();
        this.xhr = new XMLHttpRequest();

        this.toolTips.init();
        this.initYoutube();
    }

    initYoutube() {
        this.ytLink.addEventListener("paste", (e) => {
            const link = e.clipboardData.getData('text');
            this.addVideo(link);
        });
        this.ytLink.addEventListener("keypress", this.pasteLinkWithKeyboard.bind(this));

        this.filter.addEventListener("input", this.filterPlaylists.bind(this));

        document.addEventListener("visibilitychange", this.focusLink.bind(this));
        this.focusLink();
    }

    focusLink() {
        if (document.visibilityState === 'visible') {
            this.ytLink.focus();
            this.ytLink.select();
        }
    }

    pasteLinkWithKeyboard(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            if (this.ytLink.value.length >= 11) {
                this.addVideo(this.ytLink.value);
            }
        }
    }

    addVideo(link) {
        this.xhr.onload = function () {
            window.location.reload();
        }
        this.xhr.open("GET", this.app_youtube_add_playlist + '?link=' + link);
        this.xhr.send();
    }

    filterPlaylists() {
        const filter = this.filter.value.toUpperCase();
        const playlists = document.querySelectorAll('.playlist');
        if (filter === '') {
            playlists.forEach((playlist) => {
                playlist.style.display = "flex";
            });
            return;
        }
        playlists.forEach((playlist) => {
            const title = playlist.querySelector('.title');
            if (title.innerHTML.toUpperCase().indexOf(filter) > -1) {
                playlist.style.display = "flex";
            } else {
                playlist.style.display = "none";
            }
        });
    }
}

