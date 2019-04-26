import { isVisible } from './helpers';

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
    const name = document.querySelector('.multiform__name');
    const project = document.querySelector('.multiform__project');
    const nameInput = document.getElementsByName('firstname')[0];
    const projectInput = document.getElementsByName('project')[0];
    const count = section.length;
    let state = {};
    let theBalls;
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

    const getInformation = () => {
        const savedState = JSON.parse(localStorage.getItem('state'));
        name.innerHTML = '';
        name.insertAdjacentHTML('beforeend', `${savedState.name}!`);
        project.innerHTML = '';
        project.insertAdjacentHTML('beforeend', `Great choice ${savedState.name}! ${savedState.project} is something that we excel at.`);
    };

    const saveInformation = () => {
        state.name = nameInput.value;
        state.project = projectInput.value;
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
                        section[i - 1].classList.remove('multiform__show');
                        section[i].classList.add('multiform__show');
                        
                        theBalls[i - 1].classList.remove('multiform__active');
                        theBalls[i].classList.add('multiform__active');
                    }
                }

                saveInformation();
                getInformation();
            });
        });
          
        previous.forEach(elm => {
            elm.addEventListener('click', () => {
                if (i >= 1) {
                    i--;
                    section[i + 1].classList.remove('multiform__show');
                    section[i].classList.add('multiform__show');
                    
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
                setupEventListeners();
            }
        }
    };

})();

export { formController };