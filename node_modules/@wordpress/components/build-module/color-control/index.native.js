/**
 * Internal dependencies
 */
import ColorCell from '../mobile/bottom-sheet/color-cell';
import { jsx as _jsx } from "react/jsx-runtime";
function ColorControl({
  label,
  onPress,
  color,
  withColorIndicator,
  ...props
}) {
  return /*#__PURE__*/_jsx(ColorCell, {
    label: label,
    onPress: onPress,
    color: color,
    withColorIndicator: withColorIndicator,
    ...props
  });
}
export default ColorControl;
//# sourceMappingURL=index.native.js.map