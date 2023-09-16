let thisGlobal;
export class SettingsModule {



    constructor(settings, globs) {
        thisGlobal = this;
        this.saturationValue = settings.saturationValue;
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
    }

    settings() {
        const dialog = document.querySelector("#settings-dialog");
        document.querySelector("body").classList.add("frozen");
        thisGlobal.satuationValue = document.querySelector(":root").style.getPropertyValue('--gradient-saturation').slice(0, -1);
        dialog.showModal();
    }

    initDialog() {
        const dialog = document.querySelector("#settings-dialog");
        const saturationRange = dialog.querySelector("#settings-saturation");

        saturationRange.addEventListener("input", () => {
            const saturationValue = saturationRange.value;
            const span = dialog.querySelector("#settings-saturation-value");
            const root = document.querySelector(":root");
            span.textContent = saturationValue;
            root.style.setProperty('--gradient-saturation', saturationValue + "%");
        });

        dialog.addEventListener("close", () => {
            document.querySelector("body").classList.remove("frozen");
            if (dialog.returnValue === "cancel") {
                // restaurer les valeurs
                const root = document.querySelector(":root");
                root.style.setProperty('--gradient-saturation', thisGlobal.satuationValue + "%");
            }
            if (dialog.returnValue === "ok") {
                // sauvegarder les valeurs dans la table settings de la base de données
                const root = document.querySelector(":root");
                const saturationValue = root.style.getPropertyValue('--gradient-saturation').slice(0, -1);
                thisGlobal.satuationValue = saturationValue;
                const settings = {
                    saturation: saturationValue
                };
                const settingsJson = JSON.stringify(settings);

                const xhr = new XMLHttpRequest();
                xhr.onload = function () {
                    console.log(JSON.parse(this.response));
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
}