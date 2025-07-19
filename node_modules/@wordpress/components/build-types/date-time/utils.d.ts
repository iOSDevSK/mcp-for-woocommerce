/**
 * Internal dependencies
 */
import type { InputState } from '../input-control/reducer/state';
import type { InputAction } from '../input-control/reducer/actions';
/**
 * Like date-fn's toDate, but tries to guess the format when a string is
 * given.
 *
 * @param input Value to turn into a date.
 */
export declare function inputToDate(input: Date | string | number): Date;
/**
 * Converts a 12-hour time to a 24-hour time.
 * @param hours
 * @param isPm
 */
export declare function from12hTo24h(hours: number, isPm: boolean): number;
/**
 * Converts a 24-hour time to a 12-hour time.
 * @param hours
 */
export declare function from24hTo12h(hours: number): number;
/**
 * Creates an InputControl reducer used to pad an input so that it is always a
 * given width. For example, the hours and minutes inputs are padded to 2 so
 * that '4' appears as '04'.
 *
 * @param pad How many digits the value should be.
 */
export declare function buildPadInputStateReducer(pad: number): (state: InputState, action: InputAction) => {
    _event?: import("react").SyntheticEvent;
    error: unknown;
    initialValue?: string;
    isDirty: boolean;
    isDragEnabled: boolean;
    isDragging: boolean;
    isPressEnterToChange: boolean;
    value?: string;
};
/**
 * Validates the target of a React event to ensure it is an input element and
 * that the input is valid.
 * @param event
 */
export declare function validateInputElementTarget(event: React.SyntheticEvent): boolean;
//# sourceMappingURL=utils.d.ts.map