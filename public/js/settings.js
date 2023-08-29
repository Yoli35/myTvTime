import {SettingsModule} from "./SettingsModule.js";

const saturationValue = document.querySelector('#settings-saturation').value;
const globsSettings = document.querySelector('#globs-settings').textContent;
new SettingsModule({'saturationValue': saturationValue}, JSON.parse(globsSettings));