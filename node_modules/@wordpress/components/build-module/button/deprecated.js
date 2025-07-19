/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
import deprecated from '@wordpress/deprecated';
import { forwardRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Button from '.';
import { jsx as _jsx } from "react/jsx-runtime";
function UnforwardedIconButton({
  label,
  labelPosition,
  size,
  tooltip,
  ...props
}, ref) {
  deprecated('wp.components.IconButton', {
    since: '5.4',
    alternative: 'wp.components.Button',
    version: '6.2'
  });
  return /*#__PURE__*/_jsx(Button, {
    ...props,
    ref: ref,
    tooltipPosition: labelPosition,
    iconSize: size,
    showTooltip: tooltip !== undefined ? !!tooltip : undefined,
    label: tooltip || label
  });
}
export default forwardRef(UnforwardedIconButton);
//# sourceMappingURL=deprecated.js.map