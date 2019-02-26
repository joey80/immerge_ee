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
