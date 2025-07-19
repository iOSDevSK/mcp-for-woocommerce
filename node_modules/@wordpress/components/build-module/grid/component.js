/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

import { contextConnect } from '../context';
import { View } from '../view';
import useGrid from './hook';
import { jsx as _jsx } from "react/jsx-runtime";
function UnconnectedGrid(props, forwardedRef) {
  const gridProps = useGrid(props);
  return /*#__PURE__*/_jsx(View, {
    ...gridProps,
    ref: forwardedRef
  });
}

/**
 * `Grid` is a primitive layout component that can arrange content in a grid configuration.
 *
 * ```jsx
 * import {
 * 	__experimentalGrid as Grid,
 * 	__experimentalText as Text
 * } from `@wordpress/components`;
 *
 * function Example() {
 * 	return (
 * 		<Grid columns={ 3 }>
 * 			<Text>Code</Text>
 * 			<Text>is</Text>
 * 			<Text>Poetry</Text>
 * 		</Grid>
 * 	);
 * }
 * ```
 */
export const Grid = contextConnect(UnconnectedGrid, 'Grid');
export default Grid;
//# sourceMappingURL=component.js.map