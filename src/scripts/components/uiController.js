/**
* uiController.js - UI Controller
*
* Author - Joey Leger (2019)
* Description - Controls the UI client-side functionality for Immerge
*
*/
const uiController = (function() {

    // Set up some variables
    const header = document.querySelector('.header');
    const linkParent = header.getElementsByTagName('li');
    

    // Shrink logo and header height on scroll
    const handleScroll = () => {
        const windowTopPos = window.pageYOffset;

        if (windowTopPos > 250) {
            if (!header.classList.contains('js__has-scrolled')) {
                header.classList.add('js__has-scrolled');
                return;
            }
        } else if (windowTopPos == 0) {
            if (header.classList.contains('js__has-scrolled')) {
                header.classList.remove('js__has-scrolled');
                return;
            }
        }
    };


    // Add a drop down arrow if the header links have a submenu
    const handleMenuLinks = (lists) => {
        for (let list of lists) {
            if (list.children.length >= 2) {
                list.classList.add('js__has-child');
            }
        }
    };

    
    const setupEventListeners = () => {
        window.addEventListener('scroll', function() {
            handleScroll();
        });

    
    };

    return {
        init: function() {
            setupEventListeners();
            handleMenuLinks(linkParent);
        }
    };

})();

export { uiController };
