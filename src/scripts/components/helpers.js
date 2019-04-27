import dayjs from 'dayjs';

/**
* helper.js - A Collection Of Helper Functions
*
* Author - Joey Leger (2018)
* Description - These are helpful
*
*/


// Functions
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

export function getUsersTime() {
    const currentHour = dayjs().hour();
    let timeOfDay;

    if (currentHour > 4 && currentHour < 12) {
        timeOfDay = 'Morning';
    } else if (currentHour > 11 && currentHour < 5) {
        timeOfDay = 'Afternoon';
    } else {
        timeOfDay = 'Evening';
    }

    return timeOfDay;
};
