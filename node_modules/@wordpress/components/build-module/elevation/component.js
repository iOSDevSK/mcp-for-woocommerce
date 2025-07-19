/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

import { contextConnect } from '../context';
import { View } from '../view';
import { useElevation } from './hook';
import { jsx as _jsx } from "react/jsx-runtime";
function UnconnectedElevation(props, forwardedRef) {
  const elevationProps = useElevation(props);
  return /*#__PURE__*/_jsx(View, {
    ...elevationProps,
    ref: forwardedRef
  });
}

/**
 * `Elevation` is a core component that renders shadow, using the component
 * system's shadow system.
 *
 * The shadow effect is generated using the `value` prop.
 *
 * ```jsx
 * import {
 *	__experimentalElevation as Elevation,
 *	__experimentalSurface as Surface,
 *	__experimentalText as Text,
 * } from '@wordpress/components';
 *
 * function Example() {
 *   return (
 *     <Surface>
 *       <Text>Code is Poetry</Text>
 *       <Elevation value={ 5 } />
 *     </Surface>
 *   );
 * }
 * ```
 */
export const Elevation = contextConnect(UnconnectedElevation, 'Elevation');
export default Elevation;
//# sourceMappingURL=component.js.map