import {SettingsModule} from "./SettingsModule.js";

const saturationValue = document.querySelector('#settings-saturation').value;
const theme = document.querySelector('#settings-theme').value;
const globsSettings = document.querySelector('#globs-settings').textContent;
new SettingsModule({'saturationValue': saturationValue, 'theme': theme}, JSON.parse(globsSettings));