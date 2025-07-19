/**
 * WordPress dependencies
 */
import { memo } from '@wordpress/element';
/**
 * Internal dependencies
 */
import { BackdropUI } from './styles/input-control-styles';
import { jsx as _jsx } from "react/jsx-runtime";
function Backdrop({
  disabled = false,
  isBorderless = false
}) {
  return /*#__PURE__*/_jsx(BackdropUI, {
    "aria-hidden": "true",
    className: "components-input-control__backdrop",
    disabled: disabled,
    isBorderless: isBorderless
  });
}
const MemoizedBackdrop = memo(Backdrop);
export default MemoizedBackdrop;
//# sourceMappingURL=backdrop.js.map