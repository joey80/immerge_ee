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
    const theForm = document.querySelector('.multiform');
    const name = document.querySelectorAll('.multiform__name');
    const project = document.querySelector('.multiform__project');
    const timeOfDay = document.querySelector('.multiform__time');
    const digitalMarketing = document.querySelector('.multiform__marketing');
    const webDevelopment = document.querySelector('.multiform__development');
    const marketingSlider = document.querySelector('.multiform__marketing-slider');
    const developmentSlider = document.querySelector('.multiform__development-slider');
    const nameInput = document.getElementsByName('firstname')[0];
    const projectInput = document.getElementsByName('project')[0];
    let marketingSliderOneLow = document.getElementById('form-input-marketing_slider_low');
    let marketingSliderOneHigh = document.getElementById('form-input-marketing_slider_high');
    let developmentSliderOneLow = document.getElementById('form-input-development_slider_low');
    let developmentSliderOneHigh = document.getElementById('form-input-development_slider_high');
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

        if (state.project == 'marketing') {
            state.marketing = {
                name: 'Digital Marketing',
                low: Math.floor(document.querySelector('.multiform__marketing-slider.noUi-target.noUi-ltr.noUi-horizontal div.noUi-base div.noUi-origin div.noUi-handle.noUi-handle-lower').getAttribute('aria-valuenow')),
                high: Math.floor(document.querySelector('.multiform__marketing-slider.noUi-target.noUi-ltr.noUi-horizontal div.noUi-base div.noUi-origin div.noUi-handle.noUi-handle-upper').getAttribute('aria-valuenow'))
            };

            state.development = {
                name: 'Web Development',
                low: '',
                high: ''
            };

        } else if (state.project == 'development') {
            state.marketing = {
                name: 'Digital Marketing',
                low: '',
                high: ''
            };
            
            state.development = {
                name: 'Web Development',
                low: Math.floor(document.querySelector('.multiform__development-slider.noUi-target.noUi-ltr.noUi-horizontal div.noUi-base div.noUi-origin div.noUi-handle.noUi-handle-lower').getAttribute('aria-valuenow')),
                high: Math.floor(document.querySelector('.multiform__development-slider.noUi-target.noUi-ltr.noUi-horizontal div.noUi-base div.noUi-origin div.noUi-handle.noUi-handle-upper').getAttribute('aria-valuenow'))
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
        marketingSliderOneLow.value = savedState.marketing.low;
        marketingSliderOneHigh.value = savedState.marketing.high;
        developmentSliderOneLow.value = savedState.development.low;
        developmentSliderOneHigh.value = savedState.development.high;

        buildTheSummary();
    };



    const buildTheSummary = () => {
        const fields = [state.marketing, state.development];
        const target = document.querySelector('.multiform__summary');
        const usedFields = fields.filter((field) => {
            return field.low != '';
        });

        // Summary title
        createDivContents(document.querySelector('.multiform__summary-title'), `Here is what your budget estimates look like ${state.name}. If everything is correct just click submit and we will be in touch with you right away!`);

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

        if (savedState.project == 'marketing') {
            webDevelopment.classList.add('multiform__remove');
        }

        if (savedState.project == 'development') {
            digitalMarketing.classList.add('multiform__remove');
        }
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
        createRangeSlider(marketingSlider, 500, 5000, [500, 3000], 500);
        createRangeSlider(developmentSlider, 10000, 50000, [3000, 40000], 5000);
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

                        scrollTo(theForm);
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