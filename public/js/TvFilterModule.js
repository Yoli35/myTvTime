import {ToolTips} from "./ToolTips.js";

export class TvFilterModule {
    constructor(globs) {
        this.app_series_filter_save_settings = globs.app_series_filter_save_settings;
        this.app_series_filter_load_settings = globs.app_series_filter_load_settings;
        this.logos = globs.logos;
        this.init();
    }

    init() {
        this.toolTips = new ToolTips();
        this.toolTips.init(null, "orange");

        const resetForm = document.getElementById('reset-form');
        resetForm.addEventListener('click', this.resetForm.bind(this));
        const saveForm = document.getElementById('save-form');
        saveForm.addEventListener('click', this.saveForm.bind(this));
        const toggleView = document.getElementById('toggle-view');
        toggleView.addEventListener('click', this.toggleView.bind(this));
        /** @var {HTMLSelectElement} watchProviderSelect */
        const watchProviderSelect = document.getElementById('tv_filter_with_watch_providers');
        watchProviderSelect.addEventListener('change', this.toggleWatchProvider.bind(this));
    }

    resetForm() {
        /** @var {HTMLFormElement} form */
        const form = document.querySelector('form[name=tv_filter]');
        form.reset();
    }

    saveForm() {
        /** @var {HTMLFormElement} form */
        const form = document.querySelector('form[name=tv_filter]');
        const formData = new FormData(form);
        const data = {};
        for (const [key, value]  of formData.entries()) {
            data[key] = value;
        }
        console.log(data);
    }

    toggleView() {
        const toggleViewIcon = document.getElementById('toggle-view');
        const formRows = document.querySelectorAll('.form-row:not(:first-child,:last-child)');
        const lastRow = document.querySelector('.form-row:last-of-type');
        if (toggleViewIcon.classList.contains('fa-circle-arrow-down')) {
            toggleViewIcon.classList.remove('fa-circle-arrow-down');
            toggleViewIcon.classList.add('fa-circle-arrow-up');
            formRows.forEach((row) => {
                if (row === lastRow) {
                    return;
                }
                row.classList.remove('d-none');
            });
        } else {
            toggleViewIcon.classList.remove('fa-circle-arrow-up');
            toggleViewIcon.classList.add('fa-circle-arrow-down');
            formRows.forEach((row) => {
                if (row === lastRow) {
                    return;
                }
                row.classList.add('d-none');
            });
        }
    }

    toggleWatchProvider() {
        /** @var {HTMLSelectElement} watchProviderSelect */
        const watchProviderSelect = document.getElementById('tv_filter_with_watch_providers');
        const watchProvider = watchProviderSelect.value;
        const selectFormGroup = watchProviderSelect.closest('.form-group');
        const logoFormField = selectFormGroup.querySelector('.form-field:has(.logo)');
        if (watchProvider === '') {
            logoFormField.classList.add('d-none');
            return;
        }
        const logo = this.logos[watchProvider];
        const logoImg = logoFormField.querySelector('img');
        if (!logoImg) {
            const img = document.createElement('img');
            img.src = logo;
            logoFormField.appendChild(img);
        } else {
            logoImg.src = logo;
        }
        logoFormField.classList.remove('d-none');
    }
}