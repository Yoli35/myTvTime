let thisGlobal;

export class WatchProvidersModule {

    constructor() {
        thisGlobal = this;
        this.init();
    }

    init() {
        const viewProviders = document.querySelectorAll('.view-providers');
        viewProviders.forEach((viewProvider) => {
            const otherProviders = viewProvider.querySelector(".provider.other");
            otherProviders.addEventListener("click", (e) => {
                this.viewProviders(e);
            });
        });
        this.initDialog();
    }

    viewProviders(e) {
        const target = e.target;
        const view = target.closest(".view");
        this.viewingId = view.getAttribute("data-id");

        const dialog = document.querySelector("#watch-provider-dialog");
        document.querySelector("body").classList.add("frozen");
        dialog.showModal();
    }

    initDialog() {
        const dialog = document.querySelector("#watch-provider-dialog");

        dialog.addEventListener("close", () => {
            document.querySelector("body").classList.remove("frozen");
            if (dialog.returnValue === "select") {
                // Apply selection
                const selectedWP = document.querySelector('input[name="watch-provider"]:checked').value;
                const locale = document.querySelector('html').getAttribute('lang');
                const urlNetwork = "/" + locale + "/series/episode/view/network/" + this.viewingId + "/" + selectedWP;

                const xhr = new XMLHttpRequest();
                xhr.onload = function () {
                    const {result, networkId} = JSON.parse(this.responseText);
                    const view = document.querySelector('.view[data-id="' + thisGlobal.viewingId + '"]');
                    const viewProvider = view.querySelector('.view-provider');
                    const userProvider = viewProvider.querySelector('.provider');
                    const form = document.querySelector('#watch-provider-dialog').querySelector('form');
                    const selectedWPLabel = form.querySelector('label[for="watch-provider-' + selectedWP + '"]');
                    const img = selectedWPLabel.querySelector('img');
                    const src = img?.getAttribute('src');
                    const name = img?.getAttribute('alt');

                    if (result === 'success') {
                        if (networkId !== -1) {
                            const img = userProvider.querySelector('img') || document.createElement('img');
                            img.setAttribute('src', src);
                            img.setAttribute('alt', name);
                            img.setAttribute('title', name);

                            userProvider.appendChild(img);
                            userProvider.classList.remove('other');
                            userProvider.setAttribute('data-id', networkId);
                        } else {
                            userProvider.querySelector('img')?.remove();
                            userProvider.classList.add('other');
                            userProvider.setAttribute('data-id', -1);
                        }
                    }
                }
                xhr.open("GET", urlNetwork, true);
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
                dialog.close("select");
            }
        });
    }
}