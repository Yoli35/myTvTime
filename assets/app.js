import './styles/app.scss';
import './styles/activity.css';
import './styles/alarm.css';
import './styles/article.css';
import './styles/chat.scss';
import './styles/cv.scss';
import './styles/diaporama.scss';
import './styles/event.scss';
import './styles/form.scss';
import './styles/localisation.scss';
import './styles/logs.scss';
import './styles/movie.scss';
import './styles/movie-list.scss';
import './styles/search.scss';
import './styles/serie.scss';
import './styles/user.scss';
import './styles/youtube.scss';

import './js/vanilla/cookie-consent.js';
import './js/vanilla/flash.js';
import './js/mydropzone_controller.js';

// start the Stimulus application
import './bootstrap.js';

import {ActivityChallenge} from "ActivityChallenge";
import {Activity} from "Activity";
import {AddOverviewModule} from "AddOverviewModule";
import {AlarmSet} from "AlarmSet";
import {DirectLinkModule} from "DirectLinkModule";
import {EventModule} from "EventModule";
import {FilterSeriesModule} from "FilterSeriesModule";
import {LocalisationModule} from "LocalisationModule";
import {LocalizeModule} from "LocalizeModule";
import {MovieListModule} from "MovieListModule";
import {MultiSearch} from "MultiSearch";
import {SeriesShow} from "SeriesShow";
import {Series} from "Series";
import {SettingsModule} from "SettingsModule";
import {ToolTips} from "ToolTips";
import {WatchProvidersModule} from "WatchProvidersModule";
import {YoutubeIndexModule} from "YoutubeIndexModule";
import {YoutubePlaylistModule} from "YoutubePlaylistModule";
import {YoutubePlaylistsModule} from "YoutubePlaylistsModule";

new AlarmSet();
new MultiSearch();
new SettingsModule();

const toolTips = new ToolTips();
toolTips.init();

const activity = document.querySelector('.activity');
if (activity) {
    new Activity();
}

const activityChallenge = document.querySelector('.activity-challenge');
if (activityChallenge) {
    new ActivityChallenge();
}

const userSeriePage = document.querySelector('.serie-page.user-series');
if (userSeriePage) {
    new AddOverviewModule();
    new DirectLinkModule();
    new LocalizeModule();
}

const seriePage = document.querySelector('.serie-page');
if (seriePage) {
    new SeriesShow();
}

const seasonPage = document.querySelector('.season-page');
if (seasonPage) {
    new WatchProvidersModule();
}

const mySeries = document.querySelector('.my-series');
if (mySeries) {
    new Series();
}

const myEvents = document.querySelector('.my-events');
if (myEvents) {
    new EventModule();
}

const movieListPage = document.querySelector('.movie-list-page');
if (movieListPage) {
    new MovieListModule();
}

const localisation = document.querySelector('.localisation');
if (localisation) {
    new LocalisationModule();
}

const filterSeries = document.querySelector('.filter-series');
if (filterSeries) {
    new FilterSeriesModule();
}

const ytVideos = document.querySelector('.yt-videos');
if (ytVideos) {
    new YoutubeIndexModule();
}

const playListsPage = document.querySelector('.playlists.list-page');
if (playListsPage) {
    new YoutubePlaylistsModule();
}

const playlistPage = document.querySelector('.playlists.playlist-page');
if (playlistPage) {
    new YoutubePlaylistModule();
}
