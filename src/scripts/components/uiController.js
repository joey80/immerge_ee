import { isVisible } from './helpers';

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
    const slideArray = Array.from(document.querySelectorAll('.the-slide'));
    const sliderContainer = document.querySelector('.slider-container');
    const portfolioImages = Array.from(document.querySelectorAll('.portfolio__item__image'));
    let slider;
    let menuClosed = true;
    const readButton = document.getElementById('js-read');
    const cancelButton = document.getElementById('js-read-cancel');


    // Shrink logo and header height on scroll
    const handleScroll = () => {
        if (!menuClosed ? closeMenu() : null);
        const windowTopPos = window.pageYOffset;

        if (windowTopPos > 250) {
            if (!header.classList.contains('js__has-scrolled')) {
                header.classList.add('js__has-scrolled');
                searchInput.classList.add('js__has-scrolled');
                scrollButton.classList.remove('scroll-to-top--hide');
                return;
            }
        } else if (windowTopPos < 250) {
            if (header.classList.contains('js__has-scrolled')) {
                header.classList.remove('js__has-scrolled');
                searchInput.classList.remove('js__has-scrolled');
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

    // Hide and show nav links when the search icon is clicked
    const hideNav = () => {
        nav.classList.remove('header__nav--show');
        nav.classList.add('header__nav--hide');
        searchInput.classList.add('header__search__input--show');
        setTimeout(function() {
            searchIcon.classList.remove('fa-search');
            searchIcon.classList.add('fa-times');
            searchIcon.classList.add('fa-lg');
        }, 500);
    };

    const showNav = () => {
        searchInput.classList.remove('header__search__input--show');
        nav.classList.remove('header__nav--hide');
        nav.classList.add('header__nav--show');
        setTimeout(function(){
            searchIcon.classList.remove('fa-times');
            searchIcon.classList.remove('fa-lg');
            searchIcon.classList.add('fa-search');
        }, 100);
    };

    // Loops through an array of blog content p tags, strips
    // the length down and adds an elipse
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

    // Creates the slider of logos at the bottom of the screen
    const createSlider = () => {
        buildSlides();
        buildSlider();
    };

    const buildSlides = () => {
        slideArray.forEach((slide) => {
            const url = slide.dataset.url;
            const size = slide.dataset.size;
            slide.style.backgroundImage = `url(${url})`;
            slide.style.backgroundSize = `${size}%`;
        });
    };

    const buildSlider = () => {
        console.log('build slider');
        console.log('slide array: ', slideArray);
        slider = tns({
            container: '.my-slider',
            items: 3,
            center: true,
            swipeAngle: false,
            speed: 1000,
            autoplay: true,
            loop: true,
            controls: false
        });
    };

    const buildPortfolioImages = () => {
        portfolioImages.forEach((elm) => {
            const imageSrc = elm.dataset.src;
            elm.style.backgroundImage = `url(${imageSrc})`;
        });
    };

    const setupEventListeners = () => {

        // Header animation on scroll
        window.addEventListener('scroll', function() {

            // We dont want it to animate on tablet or mobile
            if(!isVisible(mobileButton)) {
                handleScroll();
            }
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
            setTimeout(window.scrollTo(0,0),100);
        });

        // Controls The Search Icon In The Nav
        searchIcon.addEventListener('click', function() {
            if (nav.classList.contains('header__nav--show') ? hideNav() : showNav());
        });

        if (readButton != null) {
            readButton.addEventListener('click', event => {
                event.preventDefault();
                const content = document.querySelector('.blog__card__content').innerHTML;
                const cleanContent = content.replace(/(<([^>]+)>)/ig,"");
                const msg = new SpeechSynthesisUtterance();
                const voices = window.speechSynthesis.getVoices();
                msg.voice = voices[10]; // Note: some voices don't support altering params
                msg.voiceURI = 'native';
                msg.volume = 1; // 0 to 1
                msg.rate = 1.2; // 0.1 to 10
                msg.pitch = 1; //0 to 2
                msg.text = cleanContent;
                msg.lang = 'en-US';
    
                window.speechSynthesis.speak(msg);
            });
        }
        
        if (cancelButton != null) {
            cancelButton.addEventListener('click', event => {
                event.preventDefault();
                window.speechSynthesis.cancel();
            });
        }
    };

    return {
        init: function() {
            setupEventListeners();
            handleMenuLinks(linkParent);
            if (blogPee.length > 0 ? trimBlogPee() : '');
            if (isVisible(sliderContainer) === true ? createSlider() : '');
            buildPortfolioImages();
        }
    };

})();

export { uiController };
