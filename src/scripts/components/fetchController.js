/**
* fetchController.js - Controller for dynamically fetching data
*
* Author - Joey Leger (2019)
* Description - Fetch API controller with polyfills for promises and fetch for
*               older browsers
*
*/
const fetchController = (function() {

    const parent = document.querySelector('.content');
    const main = document.querySelector('.parent-content');

    const fetchURL = (url) => {
        // Clear the div
        main.innerHTML = '';
    
        // Add loading icon
        main.innerHTML = 'Loading Results';
    
        // Fetch the page
        fetch(`${URL}`)
        .then(function(response) {
            // When the page is loaded convert it to text
            console.log(response.text);
            return response.text()
        })
        .then(function(html) {
            // Initialize the DOM parser
            var parser = new DOMParser();
            // Parse the text
            var doc = parser.parseFromString(html, "text/html");
            var docArticle = doc.querySelector('.content').innerHTML;
            parent.innerHTML = docArticle;
        })
        .catch(function(err) {  
            console.log('Failed to fetch page: ', err);  
        });
    };
    
    const setupEventListeners = () => {
        document.body.addEventListener('click', function(event) {
            if (event.target.classList.contains('js-btn')) {
                const targetURL = event.target.nextSibling.attributes.href.value;
                console.log(targetURL);
                fetchURL(targetURL);
            }
        });
    };

    return {
        init: function() {
            setupEventListeners();
        }
    };

})();

export { fetchController };
