/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

import { contextConnect } from '../../context';
import { useFlex } from './hook';
import { FlexContext } from './../context';
import { View } from '../../view';
import { jsx as _jsx } from "react/jsx-runtime";
function UnconnectedFlex(props, forwardedRef) {
  const {
    children,
    isColumn,
    ...otherProps
  } = useFlex(props);
  return /*#__PURE__*/_jsx(FlexContext.Provider, {
    value: {
      flexItemDisplay: isColumn ? 'block' : undefined
    },
    children: /*#__PURE__*/_jsx(View, {
      ...otherProps,
      ref: forwardedRef,
      children: children
    })
  });
}

/**
 * `Flex` is a primitive layout component that adaptively aligns child content
 * horizontally or vertically. `Flex` powers components like `HStack` and
 * `VStack`.
 *
 * `Flex` is used with any of its two sub-components, `FlexItem` and
 * `FlexBlock`.
 *
 * ```jsx
 * import { Flex, FlexBlock, FlexItem } from '@wordpress/components';
 *
 * function Example() {
 *   return (
 *     <Flex>
 *       <FlexItem>
 *         <p>Code</p>
 *       </FlexItem>
 *       <FlexBlock>
 *         <p>Poetry</p>
 *       </FlexBlock>
 *     </Flex>
 *   );
 * }
 * ```
 */
export const Flex = contextConnect(UnconnectedFlex, 'Flex');
export default Flex;
//# sourceMappingURL=component.js.map