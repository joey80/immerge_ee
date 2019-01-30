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
    const nav = document.querySelector('.header__nav');
    const linkParent = header.getElementsByTagName('li');
    const mobileButton = document.querySelector('.header__mobile-menu');
    const teamImage = document.querySelectorAll('.team__image');
    let menuClosed = true;


    // Shrink logo and header height on scroll
    const handleScroll = () => {
        if (!menuClosed ? closeMenu() : null);
        const windowTopPos = window.pageYOffset;

        if (windowTopPos > 250) {
            if (!header.classList.contains('js__has-scrolled')) {
                header.classList.add('js__has-scrolled');
                return;
            }
        } else if (windowTopPos < 250) {
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

    const closeMenu = () => {
        nav.classList.remove('js__is-visible');
        menuClosed = true;
        return;
    };

    const openMenu = () => {
        nav.classList.add('js__is-visible');
        menuClosed = false;
        return;
    };

    const flipTeamImage = (event) => {
        event.target.children[0].src = event.target.dataset.hover;
        //let hoverSrc = event.target.dataset.hover;
        //console.log('Target: ', event.target.children[0].src);
        //console.log('Original SRC: ', event.target.dataset.original);
        //console.log('Hover SRC: ', event.target.dataset.hover);
        //event.target.src = event.target.dataset.hover;

        //targetSrc = hoverSrc;
    };

    // Toggles the mobile menu
    const handleMobileMenu = () => {
        if (nav.classList.contains('js__is-visible') ? closeMenu() : openMenu());
    };

    const setupEventListeners = () => {

        // Header animation on scroll
        window.addEventListener('scroll', function() {
            handleScroll();
        });

        // Mobile menu button toggle
        mobileButton.addEventListener('click', function() {
            handleMobileMenu();
        });

        // Team image flip
        document.body.addEventListener('mouseover', function(event) {
            if (event.target.classList.contains('js-image')) {
                event.target.src = event.target.dataset.hover;
            }
        });

        document.body.addEventListener('mouseout', function(event) {
            if (event.target.classList.contains('js-image')) {
                event.target.src = event.target.dataset.original;
            }
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
