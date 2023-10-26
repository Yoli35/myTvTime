import {AnimatedHeader} from "./AnimatedHeader.js";
import {ToolTips} from "./ToolTips.js";

let toolTips;

export class ActivityChallenge {

    constructor() {
        this.init();
    }

    init() {
        new AnimatedHeader();
        toolTips = new ToolTips();
        toolTips.init();
    }
}