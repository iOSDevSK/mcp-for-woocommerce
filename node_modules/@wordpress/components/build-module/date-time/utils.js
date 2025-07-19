/**
 * External dependencies
 */
import { toDate } from 'date-fns';

/**
 * Internal dependencies
 */

import { COMMIT, PRESS_DOWN, PRESS_UP } from '../input-control/reducer/actions';

/**
 * Like date-fn's toDate, but tries to guess the format when a string is
 * given.
 *
 * @param input Value to turn into a date.
 */
export function inputToDate(input) {
  if (typeof input === 'string') {
    return new Date(input);
  }
  return toDate(input);
}

/**
 * Converts a 12-hour time to a 24-hour time.
 * @param hours
 * @param isPm
 */
export function from12hTo24h(hours, isPm) {
  return isPm ? (hours % 12 + 12) % 24 : hours % 12;
}

/**
 * Converts a 24-hour time to a 12-hour time.
 * @param hours
 */
export function from24hTo12h(hours) {
  return hours % 12 || 12;
}

/**
 * Creates an InputControl reducer used to pad an input so that it is always a
 * given width. For example, the hours and minutes inputs are padded to 2 so
 * that '4' appears as '04'.
 *
 * @param pad How many digits the value should be.
 */
export function buildPadInputStateReducer(pad) {
  return (state, action) => {
    const nextState = {
      ...state
    };
    if (action.type === COMMIT || action.type === PRESS_UP || action.type === PRESS_DOWN) {
      if (nextState.value !== undefined) {
        nextState.value = nextState.value.toString().padStart(pad, '0');
      }
    }
    return nextState;
  };
}

/**
 * Validates the target of a React event to ensure it is an input element and
 * that the input is valid.
 * @param event
 */
export function validateInputElementTarget(event) {
  var _ownerDocument$defaul;
  // `instanceof` checks need to get the instance definition from the
  // corresponding window object — therefore, the following logic makes
  // the component work correctly even when rendered inside an iframe.
  const HTMLInputElementInstance = (_ownerDocument$defaul = event.target?.ownerDocument.defaultView?.HTMLInputElement) !== null && _ownerDocument$defaul !== void 0 ? _ownerDocument$defaul : HTMLInputElement;
  if (!(event.target instanceof HTMLInputElementInstance)) {
    return false;
  }
  return event.target.validity.valid;
}
//# sourceMappingURL=utils.js.map