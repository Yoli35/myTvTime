import {ToolTips} from "./ToolTips.js";

let thisGlobal;

export class Shows {

    constructor(globs) {
        thisGlobal = this;
        this.app_series_alert = globs.app_series_alert;
        this.app_series_alert_provider = globs.app_series_alert_provider;
        this.app_series_toggle_favorite = globs.app_series_toggle_favorite;
        this.app_series_duration = globs.app_series_duration;
        this.app_series_toggle_shifted = globs.app_series_toggle_time_shifted;
        this.app_series_viewing = globs.app_series_viewing;
        this.app_series_upcoming_date = globs.app_series_upcoming_date;
        this.app_user_connected = globs.app_user_connected;
        this.episodeClicked = {viewed: 0, episodeNumber: 0, seasonNumber: 0};
        this.number_of_episodes = globs.number_of_episodes;
        this.serieId = globs.serieId;
        this.still = globs.still;
        this.userId = globs.userId;
        this.xhr = new XMLHttpRequest();
        // this.mouseX = 0;
        // this.mouseY = 0;
        this.currentDialog = null;

        this.toolTips = new ToolTips();

        this.init();
    }

    init() {

        if (this.userId && this.serieId) {
            this.initViewedEpisodes(document.querySelector(".viewed-episode"));
            this.episodeAddEvent();

            const checkFavorites = document.querySelectorAll(".favorite");
            checkFavorites.forEach(checkFavorite => {
                checkFavorite.querySelector("input[type='checkbox']").addEventListener("change", thisGlobal.toggleFavorite);
            });

            const checkTimeShifted = document.querySelectorAll(".timeShifted");
            checkTimeShifted.forEach(check => {
                check.querySelector("input[type='checkbox']").addEventListener("change", thisGlobal.toggleTimeShifted);
            });

            const test = document.querySelector("#test");
            test?.addEventListener("click", thisGlobal.getViewedEpisodesDuration);

            const alert = document.querySelector(".alert-next-episode");
            alert?.addEventListener("click", thisGlobal.toggleAlert);

            const nextEpisodeProvider = document.querySelector(".next-episode-provider");
            if (nextEpisodeProvider) {
                const nextEpisodeProviderList = nextEpisodeProvider.querySelectorAll(".next-episode-provider-list-item");
                nextEpisodeProviderList.forEach(nextEpisodeProviderListItem => {
                    nextEpisodeProviderListItem.addEventListener("click", thisGlobal.toggleNextEpisodeProvider);
                });
            }

            const upcomingSave = document.querySelector("#upcoming_save");
            upcomingSave?.addEventListener("click", thisGlobal.saveUpcomingDate);
        }
        this.toolTips.init();

        const seasonLinkDivs = document.querySelectorAll(".season-link");
        seasonLinkDivs.forEach(seasonLinkDiv => {
            seasonLinkDiv.addEventListener("click", ()=> { seasonLinkDiv.classList.add("clicked"); });
        });
    }

    getViewedEpisodesDuration(evt) {
        const button = evt.currentTarget;
        const durationString = document.querySelector('.duration-string');
        const loading = document.querySelector('.viewed-update');
        const txt = {
            'fr': {
                'Total duration of episodes seen among': 'Durée totale des épisodes vus parmi',
                'year': 'année',
                'years': 'années',
                'month': 'mois',
                'months': 'mois',
                'day': 'jour',
                'days': 'jours',
                'hour': 'heure',
                'hours': 'heures',
                'minute': 'minute',
                'minutes': 'minutes',
                'serie': 'série',
                'series': 'séries',
                'season': 'saison',
                'seasons': 'saisons',
                'episode': 'épisode',
                'episodes': 'épisodes',
                'and': 'et',
            },
            'en': {
                'Total duration of episodes seen among': 'Total duration of episodes seen among',
                'year': 'year',
                'years': 'years',
                'month': 'month',
                'months': 'months',
                'day': 'day',
                'days': 'days',
                'hour': 'hour',
                'hours': 'hours',
                'minute': 'minute',
                'minutes': 'minutes',
                'serie': 'serie',
                'series': 'series',
                'season': 'season',
                'seasons': 'seasons',
                'episode': 'episode',
                'episodes': 'episodes',
                'and': 'and',
            },
            'de': {
                'Total duration of episodes seen among': 'Gesamtdauer der gesehenen Episoden unter',
                'year': 'Jahr',
                'years': 'Jahre',
                'month': 'Monat',
                'months': 'Monate',
                'day': 'Tag',
                'days': 'Tage',
                'hour': 'Stunde',
                'hours': 'Stunden',
                'minute': 'Minute',
                'minutes': 'Minuten',
                'serie': 'Serie',
                'series': 'Serien',
                'season': 'Staffel',
                'seasons': 'Staffeln',
                'episode': 'Episode',
                'episodes': 'Episoden',
                'and': 'und',
            },
            'es': {
                'Total duration of episodes seen among': 'Duración total de episodios vistos entre',
                'year': 'año',
                'years': 'años',
                'month': 'mes',
                'months': 'meses',
                'day': 'día',
                'days': 'días',
                'hour': 'hora',
                'hours': 'horas',
                'minute': 'minuto',
                'minutes': 'minutos',
                'serie': 'serie',
                'series': 'series',
                'season': 'temporada',
                'seasons': 'temporadas',
                'episode': 'episodio',
                'episodes': 'episodios',
                'and': 'y',
            },
        }
        let data = {
            duration: 0,
            episodeCount: 0,
            log: "",
            nullDurationCount: 0,
            seasonCount: 0,
            serieCount: 0,
            time: "0"
        };
        thisGlobal.xhr.onload = function () {
            data = JSON.parse(this.response);
            console.log({data});
            let duration = data.duration;
            let convertedTime = thisGlobal.convertSeconds(duration * 60);
            let locale = document.querySelector("html").getAttribute("lang");
            durationString.innerHTML = txt[locale]['Total duration of episodes seen among'] + "&nbsp;: "
                + data.serieCount + "&nbsp;" + (data.serieCount > 1 ? txt[locale].series : txt[locale].serie) + ", "
                + data.seasonCount + "&nbsp;" + (data.seasonCount > 1 ? txt[locale].seasons : txt[locale].season) + " " + txt[locale]['and'] + " "
                + data.episodeCount + "&nbsp;" + (data.episodeCount > 1 ? txt[locale].episodes : txt[locale].episode)
                + (data.nullDurationCount ? (" (" + data.nullDurationCount + ")") : "") + "&nbsp;: <b>"
                + (convertedTime.years ? (convertedTime.years + "&nbsp;" + (convertedTime.years > 1 ? txt[locale].years : txt[locale].year)) : "") + " "
                + (convertedTime.months ? (convertedTime.months + "&nbsp;" + (convertedTime.months > 1 ? txt[locale].months : txt[locale].month)) : "") + " "
                + (convertedTime.days ? (convertedTime.days + "&nbsp;" + (convertedTime.days > 1 ? txt[locale].days : txt[locale].day)) : "") + " "
                + (convertedTime.hours ? (convertedTime.hours + "&nbsp;" + (convertedTime.hours > 1 ? txt[locale].hours : txt[locale].hour)) : "") + " "
                + (convertedTime.minutes ? (convertedTime.minutes + "&nbsp;" + (convertedTime.minutes > 1 ? txt[locale].minutes : txt[locale].minute)) : "")
                + "</b>";
            // + "(" + data.time + "sec)";
            button.remove();

            loading.classList.remove("show");
            setTimeout(() => {
                loading.close()
            }, 300);
        }
        thisGlobal.xhr.open("GET", thisGlobal.app_series_duration /*+ "?id=" + serieId*/);
        thisGlobal.xhr.send();
        loading.showModal();
        setTimeout(() => {
            loading.classList.add("show")
        }, 0);

    }

    convertSeconds(seconds) {
        let years = Math.floor(seconds / 31536000);
        seconds -= years * 31536000;
        let months = Math.floor(seconds / 2628000);
        seconds -= months * 2628000;
        let days = Math.floor(seconds / 86400);
        seconds -= days * 86400;
        let hours = Math.floor(seconds / 3600);
        seconds -= hours * 3600;
        let minutes = Math.floor(seconds / 60);
        seconds -= minutes * 60;
        return {
            years: years,
            months: months,
            days: days,
            hours: hours,
            minutes: minutes,
            seconds: seconds
        };
    }

    toggleFavorite(evt) {
        const fav = evt.target.checked ? 1 : 0;

        let url = thisGlobal.app_series_toggle_favorite + '/' + thisGlobal.userId + '/' + thisGlobal.serieId + '/' + fav;

        thisGlobal.xhr.onload = function () {
            let data = JSON.parse(this.response);
            let message = evt.target.closest(".favorite").querySelector(".message");
            // console.log({data});
            message.innerHTML = data.message;
            message.classList.remove("added", "removed");
            message.classList.add(data.class);
        }
        thisGlobal.xhr.open("GET", url);
        thisGlobal.xhr.send();
    }

    toggleTimeShifted(evt) {
        const shifted = evt.target.checked ? 1 : 0;

        let url = thisGlobal.app_series_toggle_shifted + '/' + thisGlobal.userId + '/' + thisGlobal.serieId + '/' + shifted;

        thisGlobal.xhr.onload = function () {
            let data = JSON.parse(this.response);
            console.log(data);
            window.location.reload();
        }
        thisGlobal.xhr.open("GET", url);
        thisGlobal.xhr.send();
    }

    saveUpcomingDate(e) {
        e.preventDefault();
        e.stopPropagation();
        const month = document.querySelector("#upcoming_month").value;
        const year = document.querySelector("#upcoming_year").value;
        const span = document.querySelector(".no-date").querySelector("span");

        let url = thisGlobal.app_series_upcoming_date + '?id=' + thisGlobal.serieId;
        if (month) url += '&month=' + month;
        if (year) url += '&year=' + year;

        thisGlobal.xhr.onload = function () {
            let data = JSON.parse(this.response);
            console.log(data);
            if (data.year) {
                span.innerHTML = "(" + (data.month ? data.month + "/" : "") + data.year + ")";
            } else {
                const locale = document.querySelector("html").getAttribute("lang");
                const txt = {
                    'fr': "Pas de date",
                    'en': "No date",
                    'de': "Kein Datum",
                    'es': "Sin fecha",
                }
                span.innerHTML = "— " + txt[locale];
            }
        }
        thisGlobal.xhr.open("GET", url);
        thisGlobal.xhr.send();
    }

    episodeAddEvent() {
        const episodes = document.querySelectorAll(".ep");
        episodes.forEach(episode => {
            episode.addEventListener('mousemove', this.bubbleIt);
            episode.addEventListener('mouseenter', this.bubbleItShow);
            episode.addEventListener('mouseleave', this.bubbleItHide);
            episode.addEventListener('click', this.toggleView);
        })
    }

    bubbleItShow(evt) {
        const bubbleV2 = document.querySelector(".bubble-v2");
        const name = evt.currentTarget.getAttribute("data-name");
        const airDate = evt.currentTarget.getAttribute("data-air-date");
        const vote = evt.currentTarget.getAttribute("data-vote");
        const locale = evt.currentTarget.getAttribute("data-locale");
        const stillPath = evt.currentTarget.getAttribute("data-still-path");
        const voteText = {"fr": "Votre note : ", "en": "Your vote: ", "de": "Ihre Stimme : ", "es": "Su voto: "};

        if (stillPath) {
            const img = document.createElement("img");
            img.setAttribute("src", thisGlobal.still + stillPath);
            img.setAttribute("alt", name);
            bubbleV2.querySelector(".still").appendChild(img);
        }
        bubbleV2.querySelector(".infos").innerHTML = "<div>" + name + "</div><div>" + airDate + "</div>" + (vote > 0 ? ("<div>" + voteText[locale] + vote + " / 10</div>") : "");

        bubbleV2.setAttribute("style", "translate: " + evt.pageX + "px " + evt.pageY + "px;");
        bubbleV2.classList.add("display");
        bubbleV2.classList.add("show");
    }

    bubbleItHide() {
        const bubbleV2 = document.querySelector(".bubble-v2");
        bubbleV2.classList.remove("show");
        setTimeout(() => {
            bubbleV2.classList.remove("display");
        }, 50);
        bubbleV2.querySelector(".still").innerHTML = "";
        bubbleV2.querySelector(".infos").innerHTML = "";
    }

    bubbleIt(evt) {
        const bubbleV2 = document.querySelector(".bubble-v2");
        bubbleV2.setAttribute("style", "translate: " + evt.pageX + "px " + evt.pageY + "px;");
    }

    initViewedEpisodes(dialog) {
        const devices = dialog.querySelectorAll(".device");
        const networks = dialog.querySelectorAll(".network");

        this.currentDialog = dialog;

        dialog.querySelector(".viewed-episode-done").addEventListener("click", () => {
            thisGlobal.closeDialog(dialog, true);
        })
        dialog.querySelector(".viewed-episode-cancel").addEventListener("click", () => {
            thisGlobal.closeDialog(dialog, false);
        })
        dialog.querySelector(".close").addEventListener('click', function () {
            thisGlobal.closeDialog(dialog, false);
        });
        devices.forEach(device => {
            device.addEventListener("click", (evt) => {
                devices.forEach(d => d.classList.remove("active"));
                evt.currentTarget.classList.add("active");
            })
        })
        networks.forEach(network => {
            network.addEventListener("click", (evt) => {
                networks.forEach(n => n.classList.remove("active"));
                evt.currentTarget.classList.add("active");
            })
        })
    }

    openDialog(dialog) {
        const devices = dialog.querySelectorAll(".device");
        const networks = dialog.querySelectorAll(".network");
        const checkThemAllDiv = dialog.querySelector(".check-them-all");

        if (!dialog.querySelector("#rememberChoice").checked) {
            devices.forEach(d => d.classList.remove("active"));
            networks.forEach(n => n.classList.remove("active"));
            checkThemAllDiv.classList.remove("show");
        }

        if (typeof dialog.showModal === "function") {
            dialog.showModal();
            setTimeout(() => {
                dialog.classList.add("show")
            }, 0);
        } else {
            console.error("L'API <dialog> n'est pas prise en charge par ce navigateur.");
            /*dialog.setAttribute("open");
            let offset = document.querySelector("html").scrollTop;
            dialog.setAttribute("style", "translate: 0 " + offset + "px;");
            dialog.classList.remove("d-none");
            dialog.classList.add("d-block");*/
        }
    }

    closeDialog(dialog, toggle) {
        /*dialog.classList.add("d-none");
        dialog.classList.remove("d-block");
        dialog.removeAttribute("style");*/
        dialog.classList.remove("show");
        setTimeout(() => {
            dialog.close()
        }, 300);
        if (toggle) {
            let network = dialog.querySelector(".network.active");
            let device = dialog.querySelector(".device.active");
            let all = dialog.querySelector("#checkThemAll").checked;
            let liveWatch = dialog.querySelector("#liveWatch");
            thisGlobal.toggleEpisodeView(network, device, all, liveWatch);
        }
    }

    toggleView(evt) {
        evt.preventDefault();
        evt.stopPropagation();

        const ep = evt.currentTarget;
        thisGlobal.episodeClicked.viewed = parseInt(ep.getAttribute("data-viewed")) === 0 ? 1 : 0;
        thisGlobal.episodeClicked.episodeNumber = parseInt(ep.getAttribute("data-number"));
        thisGlobal.episodeClicked.seasonNumber = parseInt(ep.parentElement.getAttribute("data-season-number"));

        thisGlobal.mouseX = evt.clientX / evt.view.innerWidth;
        thisGlobal.mouseY = evt.clientY / evt.view.innerHeight;

        if (thisGlobal.episodeClicked.viewed) {
            const checkThemAllDiv = thisGlobal.currentDialog.querySelector(".check-them-all");
            if (thisGlobal.areUnseenEpisodesBefore(parseInt(ep.getAttribute("data-global-index")))) {
                checkThemAllDiv.classList.add("show");
            } else {
                checkThemAllDiv.classList.remove("show");
                checkThemAllDiv.querySelector("#checkThemAll").checked = false;
            }
            thisGlobal.openDialog(thisGlobal.currentDialog);
        } else {
            thisGlobal.toggleEpisodeView();
        }
    }

    areUnseenEpisodesBefore(index) {
        if (index === 1) return false;
        const eps = document.querySelector("main").querySelector(".seasons").querySelectorAll(".ep");

        for (let i = 0; i < index - 1; i++) {
            if (parseInt(eps[i].getAttribute("data-viewed")) === 0) return true;
        }
        return false;
    }

    toggleEpisodeView(network = null, device = null, all = false, liveWatch = null) {

        // stillConnected();

        let networkId = null, networkType = null, deviceType = null, live = null;

        if (network) {
            networkType = network.getAttribute("data-type");
            networkId = network.getAttribute("data-id");
        }
        if (device) {
            deviceType = device.getAttribute("data-type");
        }
        if (liveWatch) {
            live = liveWatch.checked;
        }

        const e_viewed = thisGlobal.episodeClicked.viewed;
        const e_number = thisGlobal.episodeClicked.episodeNumber;
        const s_number = thisGlobal.episodeClicked.seasonNumber;
        const update = document.querySelector(".viewed-update-db-world");

        thisGlobal.xhr.onload = function () {
            let data = JSON.parse(this.response);
            let blocks = data['blocks'];
            let blockNextEpisodeToWatch = data['blockNextEpisodeToWatch'];
            let viewedEpisodes = data['viewedEpisodes'];
            let episodeText = data['episodeText'];
            let seasonCompleted = data['seasonCompleted'];

            blocks.forEach(block => {
                if (block['episode_count']) {
                    // console.log({block});
                    const details = document.querySelector(".details[data-season-number=\"" + block['season'] + "\"]");
                    if (details) {
                        const oldViewingDiv = details.querySelector(".viewing").parentElement;
                        const newViewingDiv = document.createElement("div");
                        newViewingDiv.innerHTML = block.view.content;
                        details.insertBefore(newViewingDiv, oldViewingDiv);
                        details.removeChild(oldViewingDiv);
                    }
                }
            })
            thisGlobal.updateViewCount(".view-count", ".view-average", viewedEpisodes, episodeText);
            thisGlobal.episodeAddEvent();

            const oldNextEpisodeToWatch = document.querySelector(".next-episode-to-watch")?.parentElement;
            if (blockNextEpisodeToWatch) {
                const newNextEpisodeToWatch = document.createElement("div");
                newNextEpisodeToWatch.classList.add("next-block");
                newNextEpisodeToWatch.innerHTML = blockNextEpisodeToWatch.content;
                if (oldNextEpisodeToWatch) {
                    const info = oldNextEpisodeToWatch.closest(".info");
                    info.insertBefore(newNextEpisodeToWatch, oldNextEpisodeToWatch);
                    info.removeChild(oldNextEpisodeToWatch);
                } else {
                    const infos = document.querySelector(".infos");
                    const lastInfo = infos.querySelector(".info:last-child");
                    let nextBlock = lastInfo?.querySelector(".next-block");
                    if (!nextBlock) {
                        nextBlock = document.createElement("div");
                        nextBlock.classList.add("next-block");
                        lastInfo.appendChild(nextBlock);
                    }
                    if (!nextBlock)
                        nextBlock.appendChild(newNextEpisodeToWatch);
                }


                const alert = document.querySelector(".alert-next-episode");
                if (alert) {
                    alert.addEventListener("click", thisGlobal.toggleAlert);
                    thisGlobal.alertToolTips(alert);
                }
            }

            update.classList.remove("show");
            setTimeout(() => {
                update.close()
            }, 300);

            if (seasonCompleted) {
                // setTimeout(celebrate, 300);
            }
        }
        thisGlobal.xhr.open("GET", thisGlobal.app_series_viewing + '?id=' + serie + "&s=" + s_number + "&e=" + e_number + "&v=" + e_viewed + (networkType ? "&network-type=" + networkType : "") + (networkId ? "&network-id=" + networkId : "") + (deviceType ? "&device-type=" + deviceType : "") + (all ? "&all=" + 1 : "") + (live ? "&live=1" : ""));
        thisGlobal.xhr.send();
        update.showModal();
        setTimeout(() => {
            update.classList.add("show")
        }, 0);

    }

    updateViewCount(textSelector, graphSelector, viewedEpisodes, episodeText) {
        let percent = Math.round(viewedEpisodes / thisGlobal.number_of_episodes * 100);
        if (percent > 100) percent = 100;
        const viewCount = thisGlobal.updateVote([graphSelector, percent]);
        if (viewCount) {
            viewCount.querySelector(".percentage").innerHTML = percent + "%";
            document.querySelector(textSelector).innerHTML = viewedEpisodes + " " + episodeText;
        }
    }

    updateVote([selector, value]) {
        const element = document.querySelector(selector);
        if (element === null) return;
        element.querySelector(".circle").setAttribute("style", "background: conic-gradient(var(--gradient-grey-60) 0%, var(--gradient-grey-60) " + value + "%, var(--gradient-grey-10) " + value + "%);");
        element.querySelector(".circle-start").setAttribute("style", "translate: 0 -1.5em;");
        element.querySelector(".circle-end").setAttribute("style", "transform: rotate(" + (value * 3.6) + "deg) translateY(-1.5em)");
        return element;
    }

    // stillConnected() {
    //     thisGlobal.xhr.onload = function () {
    //         let data = JSON.parse(this.response);
    //
    //         if (!data.connected) {
    //             window.location.reload();
    //         }
    //     }
    //     thisGlobal.xhr.open("GET", thisGlobal.app_user_connected);
    //     thisGlobal.xhr.send();
    // }

    toggleAlert(evt) {
        const alert = evt.currentTarget;
        const actualUrl = window.location.href;
        const tmdb = actualUrl.includes("tmdb");
        const serieId = window.location.href.match(/.+\/(\d+).*/)[1];
        let url = thisGlobal.app_series_alert + serieId;
        let data = {'success': false, 'alertMessage': ''};
        alert.classList.toggle("active");
        url += tmdb ? "/tmdb" : "/show";
        if (alert.classList.contains("active")) {
            alert.innerHTML = '<i class="fa-regular fa-bell"></i>';
            url += "/1";
        } else {
            alert.innerHTML = '<i class="fa-regular fa-bell-slash"></i>';
            url += "/0";
        }
        thisGlobal.xhr.onload = function () {
            data = JSON.parse(this.response);
            console.log({data});
            if (data.success) {
                const alertNextEpisode = document.querySelector(".alert-next-episode");
                alertNextEpisode.setAttribute("data-title", data.alertMessage);
                const tooltip = document.querySelector(".tool-tips.show");
                if (tooltip) {
                    tooltip.setAttribute("data-title", data.alertMessage);
                    tooltip.querySelector(".body").innerHTML = data.alertMessage;
                }
            }
        }
        thisGlobal.xhr.open("GET", url);
        thisGlobal.xhr.send();
    }

    toggleNextEpisodeProvider(evt) {
        const item = evt.currentTarget;
        const providerId = item.getAttribute("data-provider-id");
        const region = item.getAttribute("data-provider-region");
        let data = {'success': false, 'block': '<div class="no-provider"><div>?</div></div>'};
        const show = window.location.href.includes("show");
        const serieId = window.location.href.match(/.+\/(\d+).*/)[1];
        let url = thisGlobal.app_series_alert_provider + serieId + '/' + providerId + '?show=' + (show ? 1 : 0) + '&region=' + region;

        thisGlobal.xhr.onload = function () {
            data = JSON.parse(this.response);
            console.log({data});
            if (data.success) {
                // modify the div to show the new provider
                const nextEpisodeProvider = document.querySelector(".next-episode-provider");
                nextEpisodeProvider.innerHTML = data.block;
            }
        }
        thisGlobal.xhr.open("GET", url);
        thisGlobal.xhr.send();
    }

    alertToolTips(element) {
        this.toolTips.initElement(element);
    }
}