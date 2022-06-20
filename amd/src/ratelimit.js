/**
 * Quiz rate limiting JS code.
 *
 * @module    mod_quiz/add_question_modal_launcher
 * @copyright 2021 Martin Gauk, TU Berlin <gauk@math.tu-berlin.de>
 */

import $ from 'jquery';
import Ajax from 'core/ajax';
import ModalFactory from 'core/modal_factory';

const form = '#mod_quiz_preflight_form';
const button = form + ' input#id_submitbutton';

export const init = (maxDelay) => {
    const maxDelayUntil = Date.now() + maxDelay * 1000;
    $(button).click((e) => {
        e.preventDefault();
        $(button).prop("disabled", true);
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
    });
};

const delaySubmit = function(seconds, message = '') {
    Y.use('moodle-core-formchangechecker', function() {
        M.core_formchangechecker.set_form_submitted();
    });

    if (seconds === 0) {
        $(form).submit();
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
    const buttonVal = $(button).val();

    const checkSubmitCancelled = () => {
        // Submit is cancelled when the form is not visible, i.e. the modal was closed.
        if ($(form).is(":visible")) {
            return false;
        }
        clearInterval(interval);
        clearTimeout(timeout);
        $(button).prop("disabled", false);
        $(button).val(buttonVal);
        return true;
    };

    const updateButtonValue = () => {
        const secsLeft = Math.round((endTime - Date.now()) / 1000);
        const formatted = Math.floor(secsLeft / 60).toString() + ':' +
            (secsLeft % 60).toString().padStart(2, '0');
        $(button).val(buttonVal + ' (' + formatted + ')');
        checkSubmitCancelled();
    };

    updateButtonValue();
    const interval = setInterval(updateButtonValue, 1000);

    const timeout = setTimeout(() => {
        clearInterval(interval);
        if (!checkSubmitCancelled()) {
            $(form).submit();
        }
    }, seconds * 1000);
};
