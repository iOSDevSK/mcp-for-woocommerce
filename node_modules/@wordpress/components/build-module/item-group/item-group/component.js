/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

import { contextConnect } from '../../context';
import { useItemGroup } from './hook';
import { ItemGroupContext, useItemGroupContext } from '../context';
import { View } from '../../view';
import { jsx as _jsx } from "react/jsx-runtime";
function UnconnectedItemGroup(props, forwardedRef) {
  const {
    isBordered,
    isSeparated,
    size: sizeProp,
    ...otherProps
  } = useItemGroup(props);
  const {
    size: contextSize
  } = useItemGroupContext();
  const spacedAround = !isBordered && !isSeparated;
  const size = sizeProp || contextSize;
  const contextValue = {
    spacedAround,
    size
  };
  return /*#__PURE__*/_jsx(ItemGroupContext.Provider, {
    value: contextValue,
    children: /*#__PURE__*/_jsx(View, {
      ...otherProps,
      ref: forwardedRef
    })
  });
}

/**
 * `ItemGroup` displays a list of `Item`s grouped and styled together.
 *
 * ```jsx
 * import {
 *   __experimentalItemGroup as ItemGroup,
 *   __experimentalItem as Item,
 * } from '@wordpress/components';
 *
 * function Example() {
 *   return (
 *     <ItemGroup>
 *       <Item>Code</Item>
 *       <Item>is</Item>
 *       <Item>Poetry</Item>
 *     </ItemGroup>
 *   );
 * }
 * ```
 */
export const ItemGroup = contextConnect(UnconnectedItemGroup, 'ItemGroup');
export default ItemGroup;
//# sourceMappingURL=component.js.map