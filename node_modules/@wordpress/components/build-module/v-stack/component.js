/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

import { contextConnect } from '../context';
import { View } from '../view';
import { useVStack } from './hook';
import { jsx as _jsx } from "react/jsx-runtime";
function UnconnectedVStack(props, forwardedRef) {
  const vStackProps = useVStack(props);
  return /*#__PURE__*/_jsx(View, {
    ...vStackProps,
    ref: forwardedRef
  });
}

/**
 * `VStack` (or Vertical Stack) is a layout component that arranges child
 * elements in a vertical line.
 *
 * `VStack` can render anything inside.
 *
 * ```jsx
 * import {
 * 	__experimentalText as Text,
 * 	__experimentalVStack as VStack,
 * } from `@wordpress/components`;
 *
 * function Example() {
 * 	return (
 * 		<VStack>
 * 			<Text>Code</Text>
 * 			<Text>is</Text>
 * 			<Text>Poetry</Text>
 * 		</VStack>
 * 	);
 * }
 * ```
 */
export const VStack = contextConnect(UnconnectedVStack, 'VStack');
export default VStack;
//# sourceMappingURL=component.js.map