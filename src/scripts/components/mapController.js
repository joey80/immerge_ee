import { isVisible } from './helpers';

/**
* mapController.js - Google Maps v3 Controller
*
* Author - Joey Leger (2019)
* Description - Controller for creating a google map and adding markers to it
*
*/
const mapController = (function() {

    // Set up some variables
    const myMap = document.querySelector('.footer__section__map');
    let map;
    let points = [];
    let count = 0;
    let infoWindowArray = [];

    // Loads the google map when the page loads
    const loadMap = () => {
        map = new google.maps.Map(myMap, {
            center: {lat: 38.4511588, lng: -78.87048979999997},
            zoom: 13
        });
    };

    // Iterate over each class in the DOM collection and push
    // its content to the points array
    const buildMapMarkers = () => {
        let latitude = 38.4511588,
            longitude = -78.87048979999997,
            name = 'Immerge',
            phone = '(540) 437-9617',
            address = '139 N. Liberty St., Suite 202<br />Harrisonburg, VA 22802',

            // Create the info window content that pops up when you click
            // on a location marker
            markup = `
                <div class="infoContainer">
                    <h2>${name}</h2>
                    <p>${address}</p>
                    <p>${phone}</p>
                </div>`;

            // Push it to the array and create the next marker
            points[0] = [latitude, longitude, markup];
    };

    // Add markers to the map from the points array
    const addMapMarkers = (addArray) => {
        for(let i = 0; i < addArray.length; i++) {

            // Set a delay on each marker to stagger them being dropped
            // onto the map
            setTimeout(function() {
                let marker = new google.maps.Marker({
                    position: new google.maps.LatLng(addArray[i][0], addArray[i][1]),
                    animation: google.maps.Animation.DROP,
                    map: map
                });
                let markerContent = addArray[i][2],
                    infoWindow = new google.maps.InfoWindow({
                    content: markerContent
                    });
                
                // add each infoWindow to array so we can close them later
                infoWindowArray.push(infoWindow); 
                        
                // Bind a click event to each marker. First we close all open windows
                // and then open the window for the marker that was clicked
                google.maps.event.addListener(marker, 'click', function() {
                    closeAllInfoWindows(infoWindowArray);
                    infoWindow.open(map, marker);
                });
            }, i * 50);    
        }
    };

    // When clicking on a new marker this makes sure that any other infoWindows
    // get closed first
    const closeAllInfoWindows = (windowArray) => {
        for(let i = 0; i < windowArray.length; i++) {
            infoWindowArray[i].close();
        };
    };

    const setupEventListeners = () => {

    };

    return {
        init: function() {
            if (isVisible(myMap)) {
                loadMap();
                buildMapMarkers();
                addMapMarkers(points);
                setupEventListeners();
            }
        }
    };

})();

export { mapController };
