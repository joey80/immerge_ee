import { isVisible, getUsersTime, checkEmail, checkPhone, scrollTo } from './helpers';
import nouislider from 'nouislider';
import wnumb from 'wnumb';
import 'nouislider/distribute/nouislider.css';

/**
* formController.js
*
* Author - Joey Leger (2019)
* Description - Controller for multi-page form
*
*/
const formController = (() => {

    // Set up some variables
    const next = document.querySelectorAll('.multiform__button__next');
    const previous = document.querySelectorAll('.multiform__button__previous');
    const section = document.querySelectorAll('.multiform__section');
    const ballContainer = document.querySelector('.multiform__ball__container');
    const formContainer = document.body;
    //const formContainer = document.querySelector('.multiform');
    const theForm = document.querySelector('.multiform');
    const name = document.querySelectorAll('.multiform__name');
    const project = document.querySelector('.multiform__project');
    const timeOfDay = document.querySelector('.multiform__time');
    const digitalMarketing = document.querySelector('.multiform__digital-marketing');
    const webDevelopment = document.querySelector('.multiform__web-development');
    const digitalMarketingSliderOne = document.querySelector('.multiform__digital-marketing-slider-one');
    const webDevelopmentSliderOne = document.querySelector('.multiform__web-development-slider-one');
    const digitalMarketingSliderTwo = document.querySelector('.multiform__digital-marketing-slider-two');
    const webDevelopmentSliderTwo = document.querySelector('.multiform__web-development-slider-two');
    const nameInput = document.getElementsByName('firstname')[0];
    const projectInput = document.getElementsByName('project')[0];
    let marketingSliderOneLow = document.getElementById('form-input-digital_marketing_slider_one_low');
    let marketingSliderOneHigh = document.getElementById('form-input-digital_marketing_slider_one_high');
    let marketingSliderTwoLow = document.getElementById('form-input-digital_marketing_slider_two_low');
    let marketingSliderTwoHigh = document.getElementById('form-input-digital_marketing_slider_two_high');
    let developmentSliderOneLow = document.getElementById('form-input-web_development_slider_one_low');
    let developmentSliderOneHigh = document.getElementById('form-input-web_development_slider_one_high');
    let developmentSliderTwoLow = document.getElementById('form-input-web_development_slider_two_low');
    let developmentSliderTwoHigh = document.getElementById('form-input-web_development_slider_two_high');
    const count = section.length;
    let savedState, theBalls, slider1, slider2, slider3, slider4;
    let state = {};
    let i = 0;
    
    

    // Adds the progress completed dots to the top of the form
    const addBalls = () => {
        section.forEach(() => {
            ballContainer.insertAdjacentHTML('beforeend', '<div class="multiform__ball"></div>');
        });
        
        theBalls = document.querySelectorAll('.multiform__ball');
    
        // give the first ball an active class
        theBalls[0].classList.add('multiform__active');
    };



    // Checks if the fields are filled out and valid. Adds errors if not.
    const checkRequiredFields = () => {
        let errors = 0;
        let check;

        // Get the actively shown fields and check if they are filled out
        const theFields = document.querySelectorAll('.multiform__field');
        const totalFields = Array.from(theFields);
        const shownFields = totalFields.filter((field) => {
            return field.parentNode.parentNode.classList.contains('multiform__show');
        });

        // Validate the fields that are currently shown
        shownFields.forEach((elm) => {
            
            if (elm.value != '') {
                removeErrorMessage(elm);

                if (elm.dataset.name == 'Email') {
                    const goodEmail = checkEmail(elm.value);
                    if (!goodEmail ? showErrorMessage(elm) : removeErrorMessage(elm));

                } else if (elm.dataset.name == 'Phone') {
                    const goodPhone = checkPhone(elm.value);
                    if (!goodPhone ? showErrorMessage(elm) : removeErrorMessage(elm));
                }

            } else {
                showErrorMessage(elm);
            }
        });

        // Count how many errors there are and return
        for (let j = 0; j < shownFields.length; j++) {
            if (shownFields[j].classList.contains('multiform__error')) {
                errors++;
            }
        }

        if (errors > 0 ? check = false : check = true);
        return check;
    };



    // Gets what time of the day it is ('morning, evening, etc')
    const getTheTime = () => createDivContents(timeOfDay, getUsersTime());



    // Shows the error message
    const showErrorMessage = (elm) => {
        // Show the error message and add the error classes
        createDivContents(elm.nextElementSibling, elm.dataset.error);
        elm.classList.add('multiform__error');
        elm.parentNode.classList.add('multiform__error-group');

        // Remove the error animation after it has ran
        setTimeout(() => {
            elm.parentNode.classList.remove('multiform__error-group');
        }, 900);
    };



    // Removes the error message
    const removeErrorMessage = (elm) => {
        createDivContents(elm.nextElementSibling, '');
        elm.classList.remove('multiform__error');
    };



    // Saves the state to LocalStorage
    const saveInformation = () => {
        state.name = nameInput.value[0].toUpperCase() + nameInput.value.slice(1).toLowerCase();
        state.project = projectInput.value;

        if (state.project == 'Digital Marketing') {
            state.digitalMarketingOne = {
                name: 'Digital Marketing',
                low: Math.floor(document.querySelector('.multiform__digital-marketing-slider-one.noUi-target.noUi-ltr.noUi-horizontal div.noUi-base div.noUi-origin div.noUi-handle.noUi-handle-lower').getAttribute('aria-valuenow')),
                high: Math.floor(document.querySelector('.multiform__digital-marketing-slider-one.noUi-target.noUi-ltr.noUi-horizontal div.noUi-base div.noUi-origin div.noUi-handle.noUi-handle-upper').getAttribute('aria-valuenow'))
            };

            state.digitalMarketingTwo = {
                name: 'Search Engine Optimization (SEO)',
                low: Math.floor(document.querySelector('.multiform__digital-marketing-slider-two.noUi-target.noUi-ltr.noUi-horizontal div.noUi-base div.noUi-origin div.noUi-handle.noUi-handle-lower').getAttribute('aria-valuenow')),
                high: Math.floor(document.querySelector('.multiform__digital-marketing-slider-two.noUi-target.noUi-ltr.noUi-horizontal div.noUi-base div.noUi-origin div.noUi-handle.noUi-handle-upper').getAttribute('aria-valuenow'))
            };

            state.webDevelopmentOne = {
                name: 'Web Design',
                low: '',
                high: ''
            };
            
            state.webDevelopmentTwo = {
                name: 'Web Development',
                low: '',
                high: ''
            };

        } else if (state.project == 'Web Design And Development') {
            state.digitalMarketingOne = {
                name: 'Digital Marketing',
                low: '',
                high: ''
            };
            
            state.digitalMarketingTwo = {
                name: 'Search Engine Optimization (SEO)',
                low: '',
                high: ''
            };
            
            state.webDevelopmentOne = {
                name: 'Web Design',
                low: Math.floor(document.querySelector('.multiform__web-development-slider-one.noUi-target.noUi-ltr.noUi-horizontal div.noUi-base div.noUi-origin div.noUi-handle.noUi-handle-lower').getAttribute('aria-valuenow')),
                high: Math.floor(document.querySelector('.multiform__web-development-slider-one.noUi-target.noUi-ltr.noUi-horizontal div.noUi-base div.noUi-origin div.noUi-handle.noUi-handle-upper').getAttribute('aria-valuenow'))
            };
            
            state.webDevelopmentTwo = {
                name: 'Web Development',
                low: Math.floor(document.querySelector('.multiform__web-development-slider-two.noUi-target.noUi-ltr.noUi-horizontal div.noUi-base div.noUi-origin div.noUi-handle.noUi-handle-lower').getAttribute('aria-valuenow')),
                high: Math.floor(document.querySelector('.multiform__web-development-slider-two.noUi-target.noUi-ltr.noUi-horizontal div.noUi-base div.noUi-origin div.noUi-handle.noUi-handle-upper').getAttribute('aria-valuenow'))
            };

        } else {
            state.digitalMarketingOne = {
                name: 'Digital Marketing',
                low: Math.floor(document.querySelector('.multiform__digital-marketing-slider-one.noUi-target.noUi-ltr.noUi-horizontal div.noUi-base div.noUi-origin div.noUi-handle.noUi-handle-lower').getAttribute('aria-valuenow')),
                high: Math.floor(document.querySelector('.multiform__digital-marketing-slider-one.noUi-target.noUi-ltr.noUi-horizontal div.noUi-base div.noUi-origin div.noUi-handle.noUi-handle-upper').getAttribute('aria-valuenow'))
            };

            state.digitalMarketingTwo = {
                name: 'Search Engine Optimization (SEO)',
                low: Math.floor(document.querySelector('.multiform__digital-marketing-slider-two.noUi-target.noUi-ltr.noUi-horizontal div.noUi-base div.noUi-origin div.noUi-handle.noUi-handle-lower').getAttribute('aria-valuenow')),
                high: Math.floor(document.querySelector('.multiform__digital-marketing-slider-two.noUi-target.noUi-ltr.noUi-horizontal div.noUi-base div.noUi-origin div.noUi-handle.noUi-handle-upper').getAttribute('aria-valuenow'))
            };

            state.webDevelopmentOne = {
                name: 'Web Design',
                low: Math.floor(document.querySelector('.multiform__web-development-slider-one.noUi-target.noUi-ltr.noUi-horizontal div.noUi-base div.noUi-origin div.noUi-handle.noUi-handle-lower').getAttribute('aria-valuenow')),
                high: Math.floor(document.querySelector('.multiform__web-development-slider-one.noUi-target.noUi-ltr.noUi-horizontal div.noUi-base div.noUi-origin div.noUi-handle.noUi-handle-upper').getAttribute('aria-valuenow'))
            };
            
            state.webDevelopmentTwo = {
                name: 'Web Development',
                low: Math.floor(document.querySelector('.multiform__web-development-slider-two.noUi-target.noUi-ltr.noUi-horizontal div.noUi-base div.noUi-origin div.noUi-handle.noUi-handle-lower').getAttribute('aria-valuenow')),
                high: Math.floor(document.querySelector('.multiform__web-development-slider-two.noUi-target.noUi-ltr.noUi-horizontal div.noUi-base div.noUi-origin div.noUi-handle.noUi-handle-upper').getAttribute('aria-valuenow'))
            };
        }

        localStorage.setItem('state', JSON.stringify(state));
    };



    // Writes the saved information to the form
    const writeInformation = () => {
        savedState = JSON.parse(localStorage.getItem('state'));

        // The user's name
        name.forEach((elm) => {
            createDivContents(elm, savedState.name);
        });

        // Decide on what project to show
        chooseProject();
        
        // The hidden fields for the slider values
        marketingSliderOneLow.value = savedState.digitalMarketingOne.low;
        marketingSliderOneHigh.value = savedState.digitalMarketingOne.high;
        marketingSliderTwoLow.value = savedState.digitalMarketingTwo.low;
        marketingSliderTwoHigh.value = savedState.digitalMarketingTwo.high;
        developmentSliderOneLow.value = savedState.webDevelopmentOne.low;
        developmentSliderOneHigh.value = savedState.webDevelopmentOne.high;
        developmentSliderTwoLow.value = savedState.webDevelopmentTwo.low;
        developmentSliderTwoHigh.value = savedState.webDevelopmentTwo.high;

        buildTheSummary();
    };



    const buildTheSummary = () => {
        const fields = [state.digitalMarketingOne, state.digitalMarketingTwo, state.webDevelopmentOne, state.webDevelopmentTwo];
        const target = document.querySelector('.multiform__summary');
        const usedFields = fields.filter((field) => {
            return field.low != '';
        });

        // Summary title
        createDivContents(document.querySelector('.multiform__summary-title'), `Here is what your budget estimates look like Joey. If everything is correct just click submit and we will be in touch with you right away!`);

        // Contents
        target.innerHTML = '';
        usedFields.forEach((elm) => {
            const markup = `<strong>${elm.name}:</strong> $${elm.low}-$${elm.high}<br />`;
            target.insertAdjacentHTML('beforeend', markup);
        });
    };



    // Handles how to show which project depending on the user's input
    const chooseProject = () => {

        // Clean the slate
        webDevelopment.classList.remove('multiform__remove');
        digitalMarketing.classList.remove('multiform__remove');

        if (savedState.project == 'Digital Marketing') {
            webDevelopment.classList.add('multiform__remove');
        }

        if (savedState.project == 'Web Design And Development') {
            digitalMarketing.classList.add('multiform__remove');
        }

        const projectMarkup = ((savedState.project == 'Both' ? `<h4><strong>Now You're Talking!</strong><br />You Would Be Getting The Best Of Both Worlds` : `<h4><strong>Great choice!</strong><br />${savedState.project} Is Something We Excel At</h4>`));
        createDivContents(project, projectMarkup);
    };



    // Wrapper for creating the div elements
    const createDivContents = (target, content) => {
        target.innerHTML = '';
        target.insertAdjacentHTML('beforeend', content);
    };


    // Wrapper to create range sliders for the project budgets
    const createRangeSlider = (target, min, max, start, step) => {
        nouislider.create(target, {
            start: start,
            tooltips: true,
            connect: true,
            step: step,
            range: {
                'min': min,
                'max': max
            },
            pips: {
                mode: 'steps',
                stepped: true,
                density: 4,
                format: wnumb({
                    decimals: 0,
                    prefix: '$'
                })
            }
        });
    };



    const buildSliders = () => {
        // Target, min, max, start, step
        createRangeSlider(digitalMarketingSliderOne, 500, 5000, [500, 3000], 500);
        createRangeSlider(digitalMarketingSliderTwo, 500, 5000, [500, 1000], 500);
        createRangeSlider(webDevelopmentSliderOne, 1000, 3000, [1000, 1500], 500);
        createRangeSlider(webDevelopmentSliderTwo, 10000, 50000, [3000, 40000], 5000);
    };



    // This fires every time the 'next' button is clicked
    const processData = () => {
        saveInformation();
        writeInformation();
    };



    const setupEventListeners = () => {

        // All of the 'next' buttons
        next.forEach(elm => {
            elm.addEventListener('click', () => {
                if (i < count - 1) {

                    // Check for data before moving to the next step
                    const validData = checkRequiredFields();
                    if (validData) {
                        i++;
                        
                        // Hide current
                        section[i - 1].classList.remove('multiform__display');
                        setTimeout(() => {
                            section[i - 1].classList.remove('multiform__show');
                            section[i - 1].classList.remove('multiform__hide');
                        }, 100);
                        
                        // Show next
                        section[i].classList.add('multiform__show');
                        section[i].classList.add('multiform__hide');
                        setTimeout(() => {
                            section[i].classList.remove('multiform__hide');
                            section[i].classList.add('multiform__display');
                        }, 300);
                        
                        // Move the active ball forward one
                        theBalls[i - 1].classList.remove('multiform__active');
                        theBalls[i].classList.add('multiform__active');

                        scrollTo(formContainer);
                    }
                }

                // Set and get the inputs we need to track
                processData();
            });
        });
        
        // All of the 'previous' buttons
        previous.forEach(elm => {
            elm.addEventListener('click', () => {
                if (i >= 1) {
                    i--;

                    // Hide current
                    section[i + 1].classList.add('multiform__hide');
                    section[i + 1].classList.remove('multiform__display');
                    setTimeout(() => {
                        section[i + 1].classList.remove('multiform__show');
                        section[i].classList.add('multiform__show');
                    }, 100);
                    
                    // Show next
                    setTimeout(() => {
                        section[i].classList.add('multiform__display');
                    }, 200);
                    
                    // Move the active ball back one
                    theBalls[i + 1].classList.remove('multiform__active');
                    theBalls[i].classList.add('multiform__active');

                    scrollTo(formContainer);
                }
            });
        });
    };



    return {
        init: () => {
            if (isVisible(theForm)) {
                addBalls();
                getTheTime();
                buildSliders();
                setupEventListeners();
            }
        }
    };

})();

export { formController };