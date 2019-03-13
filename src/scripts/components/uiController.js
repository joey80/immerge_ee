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
    const scrollButton = document.querySelector('.scroll-to-top');
    const searchIcon = document.querySelector('.header__search__icon');
    const searchInput = document.querySelector('.header__search__input');
    const blogPee = document.querySelectorAll('.blog__card__content--list > p');
    let menuClosed = true;


    // Shrink logo and header height on scroll
    const handleScroll = () => {
        if (!menuClosed ? closeMenu() : null);
        const windowTopPos = window.pageYOffset;

        if (windowTopPos > 250) {
            if (!header.classList.contains('js__has-scrolled')) {
                header.classList.add('js__has-scrolled');
                scrollButton.classList.remove('scroll-to-top--hide');
                return;
            }
        } else if (windowTopPos < 250) {
            if (header.classList.contains('js__has-scrolled')) {
                header.classList.remove('js__has-scrolled');
                scrollButton.classList.add('scroll-to-top--hide');
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

    // Loops through an array of blog content p tags, strips
    // the length down and add an elipse
    const trimBlogPee = () => {
        let peeString;
        let tinyPee;
        for (let pee of blogPee) {
            peeString = pee.innerHTML;
            tinyPee = peeString.substring(0, 330);
            pee.innerHTML = tinyPee;
            pee.insertAdjacentHTML('beforeend', ' ... ');
        }
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

        // Team image flip In
        document.body.addEventListener('mouseover', function(event) {
            if (event.target.classList.contains('js-image')) {
                event.target.src = event.target.dataset.hover;
            }
        });

        // Team image flip out
        document.body.addEventListener('mouseout', function(event) {
            if (event.target.classList.contains('js-image')) {
                event.target.src = event.target.dataset.original;
            }
        });

        // Scroll To Top Button Event
        scrollButton.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                left: 0,
                behavior: 'smooth'
            });
        });

        // Controls The Search Icon In The Nav
        searchIcon.addEventListener('click', function() {
            if (nav.classList.contains('header__nav--show')) {
                nav.classList.remove('header__nav--show');
                nav.classList.add('header__nav--hide');
                setTimeout(function() {
                    searchInput.classList.add('header__search__input--show');
                    searchIcon.classList.remove('fa-search');
                    searchIcon.classList.add('fa-times');
                    searchIcon.classList.add('fa-lg');
                }, 500);
            } else if (nav.classList.contains('header__nav--hide')) {
                searchInput.classList.remove('header__search__input--show');
                nav.classList.remove('header__nav--hide');
                nav.classList.add('header__nav--show');
                setTimeout(function(){
                    searchIcon.classList.remove('fa-times');
                    searchIcon.classList.remove('fa-lg');
                    searchIcon.classList.add('fa-search');
                }, 100);
            } else {
                nav.classList.add('header__nav--hide');
                setTimeout(function() {
                    searchInput.classList.add('header__search__input--show');
                    searchIcon.classList.remove('fa-search');
                    searchIcon.classList.add('fa-times');
                    searchIcon.classList.add('fa-lg');
                }, 500);
            }
        });

    
    };

    return {
        init: function() {
            setupEventListeners();
            handleMenuLinks(linkParent);
            if (blogPee.length > 0) {
                trimBlogPee();
            }
        }
    };

})();

export { uiController };
