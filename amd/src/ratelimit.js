/**
 * Quiz rate limiting JS code.
 *
 * @module    mod_quiz/add_question_modal_launcher
 * @copyright 2021 Martin Gauk, TU Berlin <gauk@math.tu-berlin.de>
 */

import $ from 'jquery';
import Ajax from 'core/ajax';
import ModalFactory from 'core/modal_factory';
import {markFormSubmitted} from 'core_form/changechecker';

const form = '#mod_quiz_preflight_form';
const button = form + ' input#id_submitbutton';

export const init = (maxDelay) => {
    const maxDelayUntil = Date.now() + maxDelay * 1000;
    // Eventhandling
    const handleClick = (e) => {
        if (e.target.id !== 'id_submitbutton') {
            return;
        }
        e.preventDefault();
        e.stopPropagation();
        const buttonEl = document.querySelector(button);
        buttonEl.disabled = true;

        Ajax.call([{
            methodname: 'quizaccess_ratelimit_get_waiting_time',
            args: {},
            done: (response) => {
                maxDelay = Math.floor((maxDelayUntil - Date.now()) / 1000);
                maxDelay = Math.max(0, maxDelay);
                if (response.seconds >= maxDelay) {
                    // Spread the delay between 0 and maxdelay evenly.
                    const rand = Math.floor(Math.random() * maxDelay);
                    delaySubmit(rand, response.message);
                } else {
                    delaySubmit(response.seconds, response.message);
                }
            },
            fail: () => {
                // Do a random short delay.
                const rand = Math.floor(Math.random() * 10);
                delaySubmit(rand);
            }
        }]);
    };

    // Register click listener to root element '#mod_quiz_preflight_form' in capture phase to prevent propagation
    // to the button-click listener in mod/quiz/amd/src/preflight.js:66 and therefore stop this event from firing.
    const formElement = document.querySelector(form);
    if (formElement) {
        formElement.addEventListener('click', handleClick, true);
    }
    // Save eventlistener reference for later removal in submitForm()
    formElement._handleClick = handleClick;
};

const delaySubmit = function(seconds, message = '') {
    const buttonEl = document.querySelector(button);
    if (seconds === 0) {
        submitForm();
        return;
    }

    // Tell the user what is happening when the delay is too long.
    if (seconds > 10) {
        ModalFactory.create({
            body: message,
        }).then(
            (modal) => modal.show()
        ).catch(
            () => null
        );
    }

    const endTime = Date.now() + seconds * 1000;
    const buttonVal = buttonEl.value;

    const checkSubmitCancelled = () => {
        // Submit is cancelled when the form is not visible, i.e. the modal was closed.
        if ($(form).is(":visible")) {
            return false;
        }
        clearInterval(interval);
        clearTimeout(timeout);
        buttonEl.disabled = false;
        buttonEl.value = buttonVal;
        return true;
    };

    const updateButtonValue = () => {
        const secsLeft = Math.round((endTime - Date.now()) / 1000);
        const formatted = Math.floor(secsLeft / 60).toString() + ':' +
            (secsLeft % 60).toString().padStart(2, '0');
        buttonEl.value = buttonVal + ' (' + formatted + ')';
        checkSubmitCancelled();
    };

    updateButtonValue();
    const interval = setInterval(updateButtonValue, 1000);

    const timeout = setTimeout(() => {
        clearInterval(interval);
        if (!checkSubmitCancelled()) {
            submitForm();
        }
    }, seconds * 1000);
};

const submitForm = function() {
    const formElement = document.querySelector(form);
    // Remove eventlistener from the init function
    if (formElement._handleClick) {
        formElement.removeEventListener('click', formElement._handleClick, true);
    }
    markFormSubmitted(formElement);
    const buttonEl = document.querySelector(button);
    buttonEl.disabled = false;
    buttonEl.click();
};