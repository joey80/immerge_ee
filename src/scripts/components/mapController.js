import { isVisible } from './helpers';

/**
* mapController.js - Controller for leaflet js
*
* Author - Joey Leger (2018)
* Description - Controls the UI for the map
*
*/
const mapController = (function() {

    // Set up some variables
    const map = document.getElementById('map');
    let mymap;
    let popup;

    // Functions
    const createMap = () => {

        // Set up map variables
        const L = require('leaflet');
        const mapBoxToken = 'pk.eyJ1Ijoiam9leTgwIiwiYSI6ImNqcHIycWF2ajE1dDQzeG56dDNsdG40ZzAifQ.FvTJQ69PrRFCGl1IVdMzYQ';
        const mapStyle = 'cjpugsacx08jh2qqox7gs2qnh';
        mymap = L.map('map').setView([37.4316, -78.6569], 8);
        popup = L.popup();
        const redIcon = new L.Icon({
            iconUrl: 'https://cdn.rawgit.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });
        const marker = L.marker([38.0301, -79.0336], {icon: redIcon}).addTo(mymap);
        
        // Create the map
        L.tileLayer(`https://api.mapbox.com/styles/v1/joey80/${mapStyle}/tiles/256/{z}/{x}/{y}?access_token={accessToken}`, {
            attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, <a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="https://www.mapbox.com/">Mapbox</a>',
            maxZoom: 18,
            id: 'mapbox.streets',
            accessToken: mapBoxToken
        }).addTo(mymap);

        // Add the marker
        marker.bindPopup("<b>Stuarts Draft, Virginia</b><br>This is a description! :)").openPopup();

        // Close all map popups on load
        mymap.closePopup();
    };

    const onMapClick = (e) => {
        popup
        .setLatLng(e.latlng)
        .setContent("You clicked the map at " + e.latlng.toString())
        .openOn(mymap);
    };
    
    const setupEventListeners = () => {
        mymap.on('click', onMapClick);
    };

    return {
        init: function() {
            if (isVisible(map)) {
                createMap();
                setupEventListeners();
            }
        }
    };

})();

export { mapController };
