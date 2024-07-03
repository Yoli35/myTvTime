// import Map from "ol/Map";
const Map = ol.Map;
// import {Tile as TileLayer, Vector as VectorLayer} from "ol/layer";
const TileLayer = ol.layer.Tile;
const VectorLayer = ol.layer.Vector;
// import {OSM, Vector as VectorSource} from "ol/source";
const OSM = ol.source.OSM;
const VectorSource = ol.source.Vector;
// import View from "ol/View";
const View = ol.View;
// import {useGeographic} from "ol/proj";
const {useGeographic} = ol.proj;
// import Feature from "ol/Feature";
const Feature = ol.Feature;
// import Point from "ol/geom/Point";
const Point = ol.geom.Point;
const MultiLineString = ol.geom.MultiLineString;

/**
 * @typedef {{"lon": number, "lat": number}} TanCoordinate
 * @typedef {{"coordinates": Array<TanCoordinate>, "type": string}} TanGeometry
 * @typedef {{"type": string,  "geometry": {TanGeometry}, "properties": {Object}}} TanShape
 * @typedef {{"route_id": string, "route_short_name": string, "route_long_name": string, "route_type": string, "route_color": string, "route_url": string, "shape": {TanShape}, "geo_point_2d": {TanCoordinate}}} TanRoute
 */
export class LocalisationModule {
    constructor() {
        const globs = document.querySelector(".global-data").textContent;
        /**
         * @type {Array<TanRoute>}
         */
        this.routes = globs.routes;
        this.init();
    }

    init() {
        console.log(this.routes['5'].results[0]);
        this.localisation();
    }

    localisation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(this.showPosition.bind(this));
        } else {
            alert("Geolocation is not supported by this browser.");
        }
    }

    showPosition(position) {
        /**
         * @type {TanRoute} route
         */
        const route = this.routes['5'].results[0];
        /**
         * @type {TanShape} shape
         */
        const shape = route.shape;
        const color = route.route_color;
        /**
         * @type {TanGeometry} geometry
         */
        const geometry = shape.geometry;

        const coordinatesArray = geometry.coordinates;
        // const type = geometry.type;

        useGeographic();
        const map = new Map({
            layers: [
                new TileLayer({
                    source: new OSM(),
                }),
            ],
            target: 'map',
            view: new View({
                center: [position.coords.longitude, position.coords.latitude],
                zoom: 15,
            }),
        });
        console.log(position);
        const multiLine = new MultiLineString(coordinatesArray);
        const feature = new Feature({
            geometry: multiLine,
            labelPoint: new Point(route.geo_point_2d.coordinates),
            name: route.route_short_name,
        });
        const source = new VectorSource({
            features: [feature],
        });
        const vectorLayer = new VectorLayer({
            source: source,
            style: {
                'fill-color': 'rgba(255, 255, 255, 0.6)',
                'stroke-width': 5,
                'stroke-color': '#' + color,
                'circle-radius': 5,
                'circle-fill-color': 'rgba(255, 255, 255, 0.6)',
                'circle-stroke-width': 1,
                'circle-stroke-color': '#' + color,
            },
        });
        map.addLayer(vectorLayer);
    }
}