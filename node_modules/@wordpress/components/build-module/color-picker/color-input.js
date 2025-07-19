/**
 * Internal dependencies
 */
import { RgbInput } from './rgb-input';
import { HslInput } from './hsl-input';
import { HexInput } from './hex-input';
import { jsx as _jsx } from "react/jsx-runtime";
export const ColorInput = ({
  colorType,
  color,
  onChange,
  enableAlpha
}) => {
  const props = {
    color,
    onChange,
    enableAlpha
  };
  switch (colorType) {
    case 'hsl':
      return /*#__PURE__*/_jsx(HslInput, {
        ...props
      });
    case 'rgb':
      return /*#__PURE__*/_jsx(RgbInput, {
        ...props
      });
    default:
    case 'hex':
      return /*#__PURE__*/_jsx(HexInput, {
        ...props
      });
  }
};
//# sourceMappingURL=color-input.js.map