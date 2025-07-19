/**
 * External dependencies
 */
import * as Ariakit from '@ariakit/react';

/**
 * WordPress dependencies
 */
import { forwardRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { jsx as _jsx } from "react/jsx-runtime";
/**
 * Accessible Disclosure component that controls visibility of a section of
 * content. It follows the WAI-ARIA Disclosure Pattern.
 */
const UnforwardedDisclosureContent = ({
  visible,
  children,
  ...props
}, ref) => {
  const disclosure = Ariakit.useDisclosureStore({
    open: visible
  });
  return /*#__PURE__*/_jsx(Ariakit.DisclosureContent, {
    store: disclosure,
    ref: ref,
    ...props,
    children: children
  });
};
export const DisclosureContent = forwardRef(UnforwardedDisclosureContent);
export default DisclosureContent;
//# sourceMappingURL=index.js.map