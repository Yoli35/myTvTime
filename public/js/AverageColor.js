export class AverageColor {

    constructor(blockSize = 5) {
        this.blockSize = blockSize;
        this.defaultRGB = {r: 0, g: 0, b: 0, lightness: 0};
        this.canvas = document.createElement("canvas");
        this.context = this.canvas.getContext && this.canvas.getContext("2d");
    }
    getColor(img, square = false) {
        let imageData, width, height,
            i = -4,
            length,
            rgb = {r: 0, g: 0, b: 0, lightness: 0},
            count = 0;

        if (!this.context) {
            return this.defaultRGB;
        }

        height = this.canvas.height = img.naturalHeight || img.offsetHeight || img.height;
        width = this.canvas.width = img.naturalWidth || img.offsetWidth || img.width;

        this.context.drawImage(img, 0, 0);

        try {
            imageData = this.context.getImageData(0, 0, width, height);
        } catch (e) {
            /* security error, img on diff domain */
            return this.defaultRGB;
        }

        length = imageData.data.length;

        if (!square) {
            while ((i += this.blockSize * 4) < length) {
                ++count;
                rgb.r += imageData.data[i];
                rgb.g += imageData.data[i + 1];
                rgb.b += imageData.data[i + 2];
            }

            // ~~ used to floor values
            rgb.r = ~~(rgb.r / count);
            rgb.g = ~~(rgb.g / count);
            rgb.b = ~~(rgb.b / count);
        } else {
            while ((i += this.blockSize * 4) < length) {
                ++count;
                rgb.r += imageData.data[i] * imageData.data[i]
                rgb.g += imageData.data[i + 1] * imageData.data[i + 1];
                rgb.b += imageData.data[i + 2] * imageData.data[i + 2];
            }

            // ~~ used to floor values
            rgb.r = ~~(Math.sqrt(rgb.r / count));
            rgb.g = ~~(Math.sqrt(rgb.g / count));
            rgb.b = ~~(Math.sqrt(rgb.b / count));
        }

        rgb.lightness = (0.2126 * rgb.r + 0.7152 * rgb.g + 0.0722 * rgb.b);

        return rgb;
    }
}

// function getAverageColor(img, square = false) {
//     const blockSize = 5, // only visit every 5 pixels
//         defaultRGB = {r: 0, g: 0, b: 0, lightness: 0}, // for non-supporting envs
//         canvas = document.createElement("canvas"),
//         context = canvas.getContext && canvas.getContext("2d");
//     let imageData, width, height,
//         i = -4,
//         length,
//         rgb = {r: 0, g: 0, b: 0, lightness: 0},
//         count = 0;
//
//     if (!context) {
//         return defaultRGB;
//     }
//
//     height = canvas.height = img.naturalHeight || img.offsetHeight || img.height;
//     width = canvas.width = img.naturalWidth || img.offsetWidth || img.width;
//
//     context.drawImage(img, 0, 0);
//
//     try {
//         imageData = context.getImageData(0, 0, width, height);
//     } catch (e) {
//         /* security error, img on diff domain */
//         return defaultRGB;
//     }
//
//     length = imageData.data.length;
//
//     if (!square) {
//         while ((i += blockSize * 4) < length) {
//             ++count;
//             rgb.r += imageData.data[i];
//             rgb.g += imageData.data[i + 1];
//             rgb.b += imageData.data[i + 2];
//         }
//
//         // ~~ used to floor values
//         rgb.r = ~~(rgb.r / count);
//         rgb.g = ~~(rgb.g / count);
//         rgb.b = ~~(rgb.b / count);
//     } else {
//         while ((i += blockSize * 4) < length) {
//             ++count;
//             rgb.r += imageData.data[i] * imageData.data[i]
//             rgb.g += imageData.data[i + 1] * imageData.data[i + 1];
//             rgb.b += imageData.data[i + 2] * imageData.data[i + 2];
//         }
//
//         // ~~ used to floor values
//         rgb.r = ~~(Math.sqrt(rgb.r / count));
//         rgb.g = ~~(Math.sqrt(rgb.g / count));
//         rgb.b = ~~(Math.sqrt(rgb.b / count));
//     }
//
//     rgb.lightness = (0.2126 * rgb.r + 0.7152 * rgb.g + 0.0722 * rgb.b);
//
//     return rgb;
// }