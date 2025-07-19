/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
import { forwardRef } from '@wordpress/element';
import deprecated from '@wordpress/deprecated';
import { jsx as _jsx } from "react/jsx-runtime";
function stopPropagation(event) {
  event.stopPropagation();
}
const IsolatedEventContainer = forwardRef((props, ref) => {
  deprecated('wp.components.IsolatedEventContainer', {
    since: '5.7'
  });

  // Disable reason: this stops certain events from propagating outside of the component.
  // - onMouseDown is disabled as this can cause interactions with other DOM elements.
  /* eslint-disable jsx-a11y/no-static-element-interactions */
  return /*#__PURE__*/_jsx("div", {
    ...props,
    ref: ref,
    onMouseDown: stopPropagation
  });
  /* eslint-enable jsx-a11y/no-static-element-interactions */
});
export default IsolatedEventContainer;
//# sourceMappingURL=index.js.map