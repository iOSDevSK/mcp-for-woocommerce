/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

import { contextConnect } from '../../context';
import { View } from '../../view';
import { useFlexBlock } from './hook';
import { jsx as _jsx } from "react/jsx-runtime";
function UnconnectedFlexBlock(props, forwardedRef) {
  const flexBlockProps = useFlexBlock(props);
  return /*#__PURE__*/_jsx(View, {
    ...flexBlockProps,
    ref: forwardedRef
  });
}

/**
 * `FlexBlock` is a primitive layout component that adaptively resizes content
 * within layout containers like `Flex`.
 *
 * ```jsx
 * import { Flex, FlexBlock } from '@wordpress/components';
 *
 * function Example() {
 *   return (
 *     <Flex>
 *       <FlexBlock>...</FlexBlock>
 *     </Flex>
 *   );
 * }
 * ```
 */
export const FlexBlock = contextConnect(UnconnectedFlexBlock, 'FlexBlock');
export default FlexBlock;
//# sourceMappingURL=component.js.map