import { isVisible, getUsersTime } from './helpers';

/**
* formController.js
*
* Author - Joey Leger (2019)
* Description - Controller for multi-page form
*
*/
const formController = (() => {

    // Set up some variables
    const next = document.querySelectorAll('.next');
    const previous = document.querySelectorAll('.previous');
    const section = document.querySelectorAll('.multiform__section');
    const ballContainer = document.querySelector('.multiform__ball__container');
    const theForm = document.querySelector('.multiform');
    const name = document.querySelectorAll('.multiform__name');
    const project = document.querySelector('.multiform__project');
    const timeOfDay = document.querySelector('.multiform__time');
    const nameInput = document.getElementsByName('firstname')[0];
    const projectInput = document.getElementsByName('project')[0];
    const count = section.length;
    let state = {};
    let theBalls;
    let time;
    let i = 0;

    // Adds the progress completed dots to the top of the form
    const addBalls = () => {
        section.forEach(() => {
            const markup = `<div class="multiform__ball"></div>`;
            ballContainer.insertAdjacentHTML('beforeend', markup);
        });
        
        theBalls = document.querySelectorAll('.multiform__ball');
    
        // give the first ball an active class
        theBalls[0].classList.add('multiform__active');
    };

    const checkRequiredFields = () => {
        let errors = 0;
        let check;

        // Get the actively show fields and check if they are filled out
        const theFields = document.querySelectorAll('.multiform__field');
        const totalFields = Array.from(theFields);
        const shownFields = totalFields.filter((field) => {
            return field.parentNode.classList.contains('multiform__show');
        });

        shownFields.forEach((elm) => {
            if (elm.value == '') {
                elm.classList.add('multiform__error');
            } else {
                elm.classList.remove('multiform__error');
            }
        });

        for (let j = 0; j < shownFields.length; j++) {
            if (shownFields[j].classList.contains('multiform__error')) {
                errors++;
            }
        }

        if (errors > 0 ? check = false : check = true);
        
        return check;
    };

    const getTheTime = () => {
        time = getUsersTime();
        timeOfDay.innerHTML = '';
        timeOfDay.insertAdjacentHTML('beforeend', time);
    };

    const getInformation = () => {
        const savedState = JSON.parse(localStorage.getItem('state'));
        name.forEach((elm) => {
            elm.innerHTML = '';
            elm.insertAdjacentHTML('beforeend', savedState.name);
        });
        project.innerHTML = '';
        project.insertAdjacentHTML('beforeend', (savedState.project == 'Both' ? `<h4><strong>Now You're Talking!</strong><br />You Would Be Getting The Best Of Both Worlds` : `<h4><strong>Great choice!</strong><br />${savedState.project} Is Something We Excel At</h4>`));
    };

    const saveInformation = () => {
        state.name = nameInput.value;
        state.project = projectInput.value;
        state.time = getUsersTime();
        localStorage.setItem('state', JSON.stringify(state));
    };

    const setupEventListeners = () => {
        next.forEach(elm => {
            elm.addEventListener('click', () => {
                if (i < count - 1) {

                    // Check for data before moving to the next step
                    const validData = checkRequiredFields();
                    if (validData) {
                        i++;
                        
                        // Hide current
                        section[i - 1].classList.add('multiform__hide');
                        setTimeout(() => {
                            section[i - 1].classList.remove('multiform__show');
                            section[i - 1].classList.remove('multiform__display');
                            section[i - 1].classList.remove('multiform__hide');
                        }, 100);
                        
                        // Show next
                        section[i].classList.add('multiform__show');
                        setTimeout(() => {
                            section[i].classList.add('multiform__display');
                        }, 300);
                        
                        theBalls[i - 1].classList.remove('multiform__active');
                        theBalls[i].classList.add('multiform__active');
                    }
                }

                // Set and get the inputs we need to track
                saveInformation();
                getInformation();
            });
        });
          
        previous.forEach(elm => {
            elm.addEventListener('click', () => {
                if (i >= 1) {
                    i--;

                    // Hide current
                    section[i + 1].classList.remove('multiform__display');
                    setTimeout(() => {
                        section[i + 1].classList.remove('multiform__show');
                        section[i].classList.add('multiform__show');
                        section[i].classList.add('multiform__hide');
                    }, 100);
                    
                    // Show next
                    setTimeout(() => {
                        section[i].classList.add('multiform__display');
                        section[i].classList.remove('multiform__hide');
                    }, 200);
                    
                    theBalls[i + 1].classList.remove('multiform__active');
                    theBalls[i].classList.add('multiform__active');
                }
            });
        });
    };


    return {
        init: () => {
            if (isVisible(theForm)) {
                addBalls();
                getTheTime();
                setupEventListeners();
            }
        }
    };

})();

export { formController };