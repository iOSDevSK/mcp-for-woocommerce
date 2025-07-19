/**
 * External dependencies
 */
import clsx from 'clsx';

/**
 * WordPress dependencies
 */
import { forwardRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { jsx as _jsx } from "react/jsx-runtime";
function UnforwardedPanelRow({
  className,
  children
}, ref) {
  return /*#__PURE__*/_jsx("div", {
    className: clsx('components-panel__row', className),
    ref: ref,
    children: children
  });
}

/**
 * `PanelRow` is a generic container for rows within a `PanelBody`.
 * It is a flex container with a top margin for spacing.
 */
export const PanelRow = forwardRef(UnforwardedPanelRow);
export default PanelRow;
//# sourceMappingURL=row.js.map