/**
 * WordPress dependencies
 */
import { forwardRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import RovingTabIndexItem from './roving-tab-index-item';
import { jsx as _jsx } from "react/jsx-runtime";
function UnforwardedTreeGridItem({
  children,
  ...props
}, ref) {
  return /*#__PURE__*/_jsx(RovingTabIndexItem, {
    ref: ref,
    ...props,
    children: children
  });
}

/**
 * `TreeGridItem` is used to create a tree hierarchy.
 * It is not a visually styled component, but instead helps with adding
 * keyboard navigation and roving tab index behaviors to tree grid structures.
 *
 * @see {@link https://www.w3.org/TR/wai-aria-practices/examples/treegrid/treegrid-1.html}
 */
export const TreeGridItem = forwardRef(UnforwardedTreeGridItem);
export default TreeGridItem;
//# sourceMappingURL=item.js.map