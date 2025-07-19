/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

import { contextConnect } from '../../context';
import { View } from '../../view';
import { useFlexItem } from './hook';
import { jsx as _jsx } from "react/jsx-runtime";
function UnconnectedFlexItem(props, forwardedRef) {
  const flexItemProps = useFlexItem(props);
  return /*#__PURE__*/_jsx(View, {
    ...flexItemProps,
    ref: forwardedRef
  });
}

/**
 * `FlexItem` is a primitive layout component that aligns content within layout
 * containers like `Flex`.
 *
 * ```jsx
 * import { Flex, FlexItem } from '@wordpress/components';
 *
 * function Example() {
 *   return (
 *     <Flex>
 *       <FlexItem>...</FlexItem>
 *     </Flex>
 *   );
 * }
 * ```
 */
export const FlexItem = contextConnect(UnconnectedFlexItem, 'FlexItem');
export default FlexItem;
//# sourceMappingURL=component.js.map