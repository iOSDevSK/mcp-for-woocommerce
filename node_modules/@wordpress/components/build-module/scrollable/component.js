/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

import { contextConnect } from '../context';
import { View } from '../view';
import { useScrollable } from './hook';
import { jsx as _jsx } from "react/jsx-runtime";
function UnconnectedScrollable(props, forwardedRef) {
  const scrollableProps = useScrollable(props);
  return /*#__PURE__*/_jsx(View, {
    ...scrollableProps,
    ref: forwardedRef
  });
}

/**
 * `Scrollable` is a layout component that content in a scrollable container.
 *
 * ```jsx
 * import { __experimentalScrollable as Scrollable } from `@wordpress/components`;
 *
 * function Example() {
 * 	return (
 * 		<Scrollable style={ { maxHeight: 200 } }>
 * 			<div style={ { height: 500 } }>...</div>
 * 		</Scrollable>
 * 	);
 * }
 * ```
 */
export const Scrollable = contextConnect(UnconnectedScrollable, 'Scrollable');
export default Scrollable;
//# sourceMappingURL=component.js.map