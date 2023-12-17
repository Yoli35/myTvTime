let thisGlobal;
export class SettingsModule {

    constructor(settings, globs) {
        thisGlobal = this;
        this.saturationValue = settings.saturationValue;
        this.theme = settings.theme;
        this.tempTheme = settings.theme;
        this.app_set_settings = globs.app_set_settings.slice(0, -3);
        this.initSettings();
    }

    initSettings() {
        const search = document.querySelector('#settings');
        search.addEventListener("click", () => {
            this.settings();
        });
        document.querySelector(":root").style.setProperty('--gradient-saturation', this.saturationValue + "%");
        this.initDialog()
        this.setTheme(this.theme);
    }

    settings() {
        const dialog = document.querySelector("#settings-dialog");
        document.querySelector("body").classList.add("frozen");
        thisGlobal.satuationValue = document.querySelector(":root").style.getPropertyValue('--gradient-saturation').slice(0, -1);
        const themeSelect = dialog.querySelector("#settings-theme");
        themeSelect.value = thisGlobal.theme;
        thisGlobal.tempTheme = thisGlobal.theme;
        dialog.showModal();
    }

    initDialog() {
        const dialog = document.querySelector("#settings-dialog");
        const saturationRange = dialog.querySelector("#settings-saturation");
        const themeSelect = dialog.querySelector("#settings-theme");

        saturationRange.addEventListener("input", () => {
            const saturationValue = saturationRange.value;
            const span = dialog.querySelector("#settings-saturation-value");
            const root = document.querySelector(":root");
            span.textContent = saturationValue;
            root.style.setProperty('--gradient-saturation', saturationValue + "%");
        });

        themeSelect.addEventListener("change", () => {
            const themeValue = themeSelect.value;
            thisGlobal.setTheme(themeValue);
            thisGlobal.theme = themeValue;
        });

        dialog.addEventListener("close", () => {
            document.querySelector("body").classList.remove("frozen");
            if (dialog.returnValue === "cancel") {
                // restaurer les valeurs
                const root = document.querySelector(":root");
                root.style.setProperty('--gradient-saturation', thisGlobal.satuationValue + "%");
                thisGlobal.setTheme(thisGlobal.tempTheme);
            }
            if (dialog.returnValue === "ok") {
                // sauvegarder les valeurs dans la table settings de la base de données
                const root = document.querySelector(":root");
                const saturationValue = root.style.getPropertyValue('--gradient-saturation').slice(0, -1);
                thisGlobal.satuationValue = saturationValue;
                const settings = {
                    saturation: saturationValue,
                    theme: thisGlobal.theme
                };
                const settingsJson = JSON.stringify(settings);

                const xhr = new XMLHttpRequest();
                xhr.onload = function () {
                    console.log(JSON.parse(this.response));
                    if (thisGlobal.theme === "auto") {
                        document.querySelector("body").removeAttribute("class");
                    }
                }
                xhr.open("GET", thisGlobal.app_set_settings + settingsJson);
                xhr.send();
            }
        });

        dialog.addEventListener("keydown", (evt) => {
            if (evt.key === "Escape") {
                evt.preventDefault();
                evt.stopPropagation();
                dialog.close("cancel");
            }
            if (evt.key === "Enter") {
                evt.preventDefault();
                evt.stopPropagation();
                dialog.close("ok");
            }
        });
    }

    setTheme(themeValue) {
        if (themeValue === "light") {
            document.querySelector("body").classList.remove("dark");
            document.querySelector("body").classList.add("light");
        }
        if (themeValue === "dark") {
            document.querySelector("body").classList.remove("light");
            document.querySelector("body").classList.add("dark");
        }
        if (themeValue === "auto") {
            document.querySelector("body").classList.remove("light");
            document.querySelector("body").classList.remove("dark");
        }
    }
}