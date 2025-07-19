/**
 * WordPress dependencies
 */
import { swatch } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import ColorIndicator from '../color-indicator';
import Icon from '../icon';
import { getGradientFromCSSColors } from './utils';
import { jsx as _jsx } from "react/jsx-runtime";
function DuotoneSwatch({
  values
}) {
  return values ? /*#__PURE__*/_jsx(ColorIndicator, {
    colorValue: getGradientFromCSSColors(values, '135deg')
  }) : /*#__PURE__*/_jsx(Icon, {
    icon: swatch
  });
}
export default DuotoneSwatch;
//# sourceMappingURL=duotone-swatch.js.map