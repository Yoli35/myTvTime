<?php

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 */
return [
    'app' => [
        'path' => './assets/app.js',
        'entrypoint' => true,
    ],
    'Activity' => [
        'path' => './assets/js/Activity.js',
    ],
    'ActivityChallenge' => [
        'path' => './assets/js/ActivityChallenge.js',
    ],
    'AddOverviewModule' => [
        'path' => './assets/js/AddOverviewModule.js',
    ],
    'AlarmSet' => [
        'path' => './assets/js/AlarmSet.js',
    ],
    'AnimatedHeader' => [
        'path' => './assets/js/AnimatedHeader.js',
    ],
    'AverageColor' => [
        'path' => './assets/js/AverageColor.js',
    ],
    'DirectLinkModule' => [
        'path' => './assets/js/DirectLinkModule.js',
    ],
    'Discussion' => [
        'path' => './assets/js/Discussion.js',
    ],
    'EventModule' => [
        'path' => './assets/js/EventModule.js',
    ],
    'LocalizeModule' => [
        'path' => './assets/js/LocalizeModule.js',
    ],
    'MovieListModule' => [
        'path' => './assets/js/MovieListModule.js',
    ],
    'MultiSearch' => [
        'path' => './assets/js/MultiSearch.js',
    ],
    'LocalisationModule' => [
        'path' => './assets/js/LocalisationModule.js',
    ],
    'Series' => [
        'path' => './assets/js/Series.js',
    ],
    'SeriesShow' => [
        'path' => './assets/js/SeriesShow.js',
    ],
    'SettingsModule' => [
        'path' => './assets/js/SettingsModule.js',
    ],
    'ToolTips' => [
        'path' => './assets/js/ToolTips.js',
    ],
    'FilterSeriesModule' => [
        'path' => './assets/js/FilterSeriesModule.js',
    ],
    'WatchProvidersModule' => [
        'path' => './assets/js/WatchProvidersModule.js',
    ],
    'YoutubeModule' => [
        'path' => './assets/js/YoutubeModule.js',
    ],
    'YoutubeIndexModule' => [
        'path' => './assets/js/YoutubeIndexModule.js',
    ],
    'YoutubePlaylistModule' => [
        'path' => './assets/js/YoutubePlaylistModule.js',
    ],
    'YoutubePlaylistsModule' => [
        'path' => './assets/js/YoutubePlaylistsModule.js',
    ],
    '@hotwired/stimulus' => [
        'version' => '3.2.2',
    ],
];
