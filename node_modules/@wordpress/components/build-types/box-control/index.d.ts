import type { BoxControlProps } from './types';
/**
 * A control that lets users set values for top, right, bottom, and left. Can be
 * used as an input control for values like `padding` or `margin`.
 *
 * ```jsx
 * import { useState } from 'react';
 * import { BoxControl } from '@wordpress/components';
 *
 * function Example() {
 *   const [ values, setValues ] = useState( {
 *     top: '50px',
 *     left: '10%',
 *     right: '10%',
 *     bottom: '50px',
 *   } );
 *
 *   return (
 *     <BoxControl
 *       __next40pxDefaultSize
 *       values={ values }
 *       onChange={ setValues }
 *     />
 *   );
 * };
 * ```
 */
declare function BoxControl({ __next40pxDefaultSize, id: idProp, inputProps, onChange, label, values: valuesProp, units, sides, splitOnAxis, allowReset, resetValues, presets, presetKey, onMouseOver, onMouseOut, }: BoxControlProps): import("react").JSX.Element;
export { applyValueToSides } from './utils';
export default BoxControl;
//# sourceMappingURL=index.d.ts.map