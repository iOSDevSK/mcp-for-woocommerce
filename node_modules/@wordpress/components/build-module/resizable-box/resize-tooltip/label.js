/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
import { forwardRef } from '@wordpress/element';
import { isRTL } from '@wordpress/i18n';

/**
 * Internal dependencies
 */

import { POSITIONS } from './utils';
import { TooltipWrapper, Tooltip, LabelText } from './styles/resize-tooltip.styles';
import { jsx as _jsx } from "react/jsx-runtime";
const CORNER_OFFSET = 4;
const CURSOR_OFFSET_TOP = CORNER_OFFSET * 2.5;
function Label({
  label,
  position = POSITIONS.corner,
  zIndex = 1000,
  ...props
}, ref) {
  const showLabel = !!label;
  const isBottom = position === POSITIONS.bottom;
  const isCorner = position === POSITIONS.corner;
  if (!showLabel) {
    return null;
  }
  let style = {
    opacity: showLabel ? 1 : undefined,
    zIndex
  };
  let labelStyle = {};
  if (isBottom) {
    style = {
      ...style,
      position: 'absolute',
      bottom: CURSOR_OFFSET_TOP * -1,
      left: '50%',
      transform: 'translate(-50%, 0)'
    };
    labelStyle = {
      transform: `translate(0, 100%)`
    };
  }
  if (isCorner) {
    style = {
      ...style,
      position: 'absolute',
      top: CORNER_OFFSET,
      right: isRTL() ? undefined : CORNER_OFFSET,
      left: isRTL() ? CORNER_OFFSET : undefined
    };
  }
  return /*#__PURE__*/_jsx(TooltipWrapper, {
    "aria-hidden": "true",
    className: "components-resizable-tooltip__tooltip-wrapper",
    ref: ref,
    style: style,
    ...props,
    children: /*#__PURE__*/_jsx(Tooltip, {
      className: "components-resizable-tooltip__tooltip",
      style: labelStyle,
      children: /*#__PURE__*/_jsx(LabelText, {
        as: "span",
        children: label
      })
    })
  });
}
const ForwardedComponent = forwardRef(Label);
export default ForwardedComponent;
//# sourceMappingURL=label.js.map