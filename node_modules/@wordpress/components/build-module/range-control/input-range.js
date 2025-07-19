/**
 * WordPress dependencies
 */
import { forwardRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { InputRange as BaseInputRange } from './styles/range-control-styles';
import { jsx as _jsx } from "react/jsx-runtime";
function InputRange(props, ref) {
  const {
    describedBy,
    label,
    value,
    ...otherProps
  } = props;
  return /*#__PURE__*/_jsx(BaseInputRange, {
    ...otherProps,
    "aria-describedby": describedBy,
    "aria-label": label,
    "aria-hidden": false,
    ref: ref,
    tabIndex: 0,
    type: "range",
    value: value
  });
}
const ForwardedComponent = forwardRef(InputRange);
export default ForwardedComponent;
//# sourceMappingURL=input-range.js.map