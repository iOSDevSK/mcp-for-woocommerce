/**
 * External dependencies
 */
import * as Ariakit from '@ariakit/react';
/**
 * WordPress dependencies
 */
import { forwardRef } from '@wordpress/element';
import { isRTL } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ToolbarContext from '../toolbar-context';
import { jsx as _jsx } from "react/jsx-runtime";
function UnforwardedToolbarContainer({
  label,
  ...props
}, ref) {
  const toolbarStore = Ariakit.useToolbarStore({
    focusLoop: true,
    rtl: isRTL()
  });
  return (
    /*#__PURE__*/
    // This will provide state for `ToolbarButton`'s
    _jsx(ToolbarContext.Provider, {
      value: toolbarStore,
      children: /*#__PURE__*/_jsx(Ariakit.Toolbar, {
        ref: ref,
        "aria-label": label,
        store: toolbarStore,
        ...props
      })
    })
  );
}
export const ToolbarContainer = forwardRef(UnforwardedToolbarContainer);
export default ToolbarContainer;
//# sourceMappingURL=toolbar-container.js.map