/**
 * Internal dependencies
 */
import CustomGradientBar from '../custom-gradient-picker/gradient-bar';
import { getColorStopsFromColors, getGradientFromCSSColors, getColorsFromColorStops } from './utils';
import { jsx as _jsx } from "react/jsx-runtime";
const PLACEHOLDER_VALUES = ['#333', '#CCC'];
export default function CustomDuotoneBar({
  value,
  onChange
}) {
  const hasGradient = !!value;
  const values = hasGradient ? value : PLACEHOLDER_VALUES;
  const background = getGradientFromCSSColors(values);
  const controlPoints = getColorStopsFromColors(values);
  return /*#__PURE__*/_jsx(CustomGradientBar, {
    disableInserter: true,
    background: background,
    hasGradient: hasGradient,
    value: controlPoints,
    onChange: newColorStops => {
      const newValue = getColorsFromColorStops(newColorStops);
      onChange(newValue);
    }
  });
}
//# sourceMappingURL=custom-duotone-bar.js.map