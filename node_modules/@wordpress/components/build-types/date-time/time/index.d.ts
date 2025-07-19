import type { TimePickerProps } from '../types';
/**
 * TimePicker is a React component that renders a clock for time selection.
 *
 * ```jsx
 * import { TimePicker } from '@wordpress/components';
 * import { useState } from '@wordpress/element';
 *
 * const MyTimePicker = () => {
 *   const [ time, setTime ] = useState( new Date() );
 *
 *   return (
 *     <TimePicker
 *       currentTime={ date }
 *       onChange={ ( newTime ) => setTime( newTime ) }
 *       is12Hour
 *     />
 *   );
 * };
 * ```
 */
export declare function TimePicker({ is12Hour, currentTime, onChange, dateOrder: dateOrderProp, hideLabelFromVision, }: TimePickerProps): import("react").JSX.Element;
export declare namespace TimePicker {
    var TimeInput: typeof import("./time-input").TimeInput;
}
export default TimePicker;
//# sourceMappingURL=index.d.ts.map