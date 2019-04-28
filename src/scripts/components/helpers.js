import dayjs from 'dayjs';

/**
* helper.js - A Collection Of Helper Functions
*
* Author - Joey Leger (2018)
* Description - These are helpful
*
*/


// Checks if the element is visible on the screen
export function isVisible(elm) {
    if (elm === null) {
        return false;
    } else if (elm === 'undefined') {
        return false;
    } else if (elm.offsetWidth || elm.offsetHeight) {
        return true;
    } else {
        return false;
    }
};

// Returns time of day of the user's timezone
export function getUsersTime() {
    const currentHour = dayjs().hour();
    let timeOfDay;
    
    if (currentHour > 4 && currentHour < 12) {
        timeOfDay = 'Morning';
    } else if (currentHour > 11 && currentHour < 17) {
        timeOfDay = 'Afternoon';
    } else {
        timeOfDay = 'Evening';
    }

    return timeOfDay;
};

// Checks if an email is in a valid format
export function checkEmail(email) {
    if (!/^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/.test(email)) {
        return false;

    } else {
        return true;
    }
};

// Checks if a phone number is in a valid US format
export function checkPhone(phone) {
    if (!/[0-9]{3}-[0-9]{3}-[0-9]{4}$/.test(phone)) {
        return false;

    } else {
        return true;
    }
};

export function scrollTo(elm) {
    window.scroll({
        behavior: 'smooth',
        left: 0,
        top: elm.offsetTop - 100
    });
};
