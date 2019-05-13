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
    const formContainer = document.body;
    const theForm = document.querySelector('.multiform__form');
    const section = document.querySelectorAll('.multiform__section');
    let savedState, theBalls;
    let state = {};


    // Adds the progress completed dots to the top of the form
    const addBalls = () => {
        const ballContainer = document.querySelector('.multiform__ball__container');

        section.forEach(() => {
            ballContainer.insertAdjacentHTML('beforeend', '<div class="multiform__ball"></div>');
        });
        
        theBalls = document.querySelectorAll('.multiform__ball');
    
        // give the first ball an active class
        theBalls[0].classList.add('multiform__active');
    };



    // Wrapper for creating the div elements
    const createDivContents = (target, content) => {
        target.innerHTML = '';
        target.insertAdjacentHTML('beforeend', content);
    };



    // Gets what time of the day it is ('morning, evening, etc')
    const getTheTime = () => {
        const timeOfDay = document.querySelector('.multiform__time');
        createDivContents(timeOfDay, getUsersTime());
    };



    const buildSliders = () => {
        const marketingSlider = document.querySelector('.multiform__marketing-slider');
        const developmentSlider = document.querySelector('.multiform__development-slider');

        // Target, min, max, start, step
        if (theForm.dataset.form === 'marketing') {
            createRangeSlider(marketingSlider, 500, 5000, [500, 3000], 500);
        }

        if (theForm.dataset.form === 'development') {
            createRangeSlider(developmentSlider, 1000, 3000, [1000, 1500], 500);
        }
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



    // Processes getting new state and saving it
    // state - the current state
    const processState = () => {
        // Get the form elements
        const theFormArray = Array.from(theForm.elements);
        //const theFormArray = getVisibleFields();
        // Filter only the fields that we need
        const filteredFormArray = filteredForm(theFormArray);
        // update old and new state
        const updatedState = updateState(filteredFormArray);
        // push new state
        localStorage.setItem('form_state', JSON.stringify(updatedState));
    };



    // Filters out hidden or blank fields
    // array - raw form values
    const filteredForm = (array) => {
        return array.filter((elm) => {
            if (elm.type != 'button') {
                return elm;
            }
        });
    };



    // Updates the old state with the new values
    // array - filtered form values
    const updateState = (array) => {
        for (let key in array) {
            let tempValue = array[key];
            state[tempValue.name] = tempValue.value;
        }
        console.log(state);
        return state;
    };



    // Updates the DOM with new state
    const updateDOM = () => {
        savedState = JSON.parse(localStorage.getItem('form_state'));
        console.log('savedState: ', savedState);
        const name = document.querySelectorAll('.multiform__name');

        // The user's name
        name.forEach((elm) => {
            createDivContents(elm, savedState.name);
        });
        
        // The hidden fields for the slider values
        if (savedState.form === 'marketing') {
            const marketingSliderLow = document.getElementById('marketing-slider-low');
            const marketingSliderHigh = document.getElementById('marketing-slider-high');

            marketingSliderLow.value = Math.floor(document.querySelector('.multiform__digital-marketing-slider-one.noUi-target.noUi-ltr.noUi-horizontal div.noUi-base div.noUi-origin div.noUi-handle.noUi-handle-lower').getAttribute('aria-valuenow'));
            marketingSliderHigh.value = Math.floor(document.querySelector('.multiform__digital-marketing-slider-one.noUi-target.noUi-ltr.noUi-horizontal div.noUi-base div.noUi-origin div.noUi-handle.noUi-handle-upper').getAttribute('aria-valuenow'));
            marketingSliderLow.value = savedState.marketing-slider-low;
            marketingSliderHigh.value = savedState.marketing-slider-high;
            buildTheSummary()
            console.log(savedState);
        }

        if (state.form === 'development') {
            const developmentSliderLow = document.getElementsByName('developer-slider-low')[0];
            const developmentSliderHigh = document.getElementsByName('developer-slider-high')[0];

            if (isVisible(developmentSliderLow)) {
                developmentSliderLow.value = Math.floor(document.querySelector('.multiform__web-development-slider-two.noUi-target.noUi-ltr.noUi-horizontal div.noUi-base div.noUi-origin div.noUi-handle.noUi-handle-lower').getAttribute('aria-valuenow'));
                developmentSliderHigh.value = Math.floor(document.querySelector('.multiform__web-development-slider-two.noUi-target.noUi-ltr.noUi-horizontal div.noUi-base div.noUi-origin div.noUi-handle.noUi-handle-upper').getAttribute('aria-valuenow'));
                developmentSliderLow.value = savedState.development-slider-low;
                developmentSliderHigh.value = savedState.development-slider-high;
                buildTheSummary()
            }
        }
    };



    const buildTheSummary = () => {
        const fields = [
            state.digitalMarketingOne,
            state.webDevelopmentOne
        ];

        const target = document.querySelector('.multiform__summary');
        const usedFields = fields.filter((field) => {
            return field.low != '' || field != 'undefined';
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



    // This fires every time the 'next' button is clicked
    const processData = () => {
        processState();
        updateDOM();
    };



    // Returns an array of fields that are visible at that moment
    const getVisibleFields = () => {
        const theFields = Array.from(document.querySelectorAll('.multiform__field'));
        const shownFields = theFields.filter((field) => {
            return field.parentNode.parentNode.classList.contains('multiform__show');
        });

        return shownFields;
    };



    // Validates visible fields
    // array - currently visible fields
    const validateFields = (array) => {
        array.forEach((elm) => {
            if (elm.value != '') {
                removeErrorMessage(elm);

                if (elm.name == 'email') {
                    const goodEmail = checkEmail(elm.value);
                    if (!goodEmail ? showErrorMessage(elm) : removeErrorMessage(elm));

                } else if (elm.name == 'phone') {
                    const goodPhone = checkPhone(elm.value);
                    if (!goodPhone ? showErrorMessage(elm) : removeErrorMessage(elm));
                }

            } else {
                showErrorMessage(elm);
            }
        });

        const errors = countFieldErrors(array);
        return errors;
    };



    // Count how many errors there are and return
    const countFieldErrors = (array) => {
        let errors = 0;
        let check;

        for (let j = 0; j < array.length; j++) {
            if (array[j].classList.contains('multiform__error')) {
                errors++;
            }
        }

        if (errors > 0 ? check = false : check = true);
        return check;
    };



    // Checks if the fields are filled out and valid. Adds errors if not.
    const checkRequiredFields = () => {
        const visibleFields = getVisibleFields();
        const checked = validateFields(visibleFields);
        return checked;
    };



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



    const moveSlideForward = (iterator) => {
        // Hide current
        section[iterator - 1].classList.remove('multiform__display');
        setTimeout(() => {
            section[iterator - 1].classList.remove('multiform__show');
            section[iterator - 1].classList.remove('multiform__hide');
        }, 100);
        
        // Show next
        section[iterator].classList.add('multiform__show');
        section[iterator].classList.add('multiform__hide');
        setTimeout(() => {
            section[iterator].classList.remove('multiform__hide');
            section[iterator].classList.add('multiform__display');
        }, 300);
        
        // Move the active ball forward one
        theBalls[iterator - 1].classList.remove('multiform__active');
        theBalls[iterator].classList.add('multiform__active');

        scrollTo(formContainer);
    };



    const moveSlideBackward = (iterator) => {
        // Hide current
        section[iterator + 1].classList.add('multiform__hide');
        section[iterator + 1].classList.remove('multiform__display');
        setTimeout(() => {
            section[iterator + 1].classList.remove('multiform__show');
            section[iterator].classList.add('multiform__show');
        }, 100);
        
        // Show next
        setTimeout(() => {
            section[iterator].classList.add('multiform__display');
        }, 200);
        
        // Move the active ball back one
        theBalls[iterator + 1].classList.remove('multiform__active');
        theBalls[iterator].classList.add('multiform__active');

        scrollTo(formContainer);
    };



    const setupEventListeners = () => {
        const next = document.querySelectorAll('.multiform__button__next');
        const previous = document.querySelectorAll('.multiform__button__previous');
        const count = section.length;
        let i = 0;

        // All of the 'next' buttons
        next.forEach(elm => {
            elm.addEventListener('click', () => {
                if (i < count - 1) {
                    // Check for data before moving to the next step
                    const validData = checkRequiredFields();
                    if (validData) {
                        i++;
                        moveSlideForward(i);
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
                    moveSlideBackward(i);
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