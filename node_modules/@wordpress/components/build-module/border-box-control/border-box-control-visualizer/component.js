/**
 * Internal dependencies
 */
import { View } from '../../view';
import { contextConnect } from '../../context';
import { useBorderBoxControlVisualizer } from './hook';
import { jsx as _jsx } from "react/jsx-runtime";
const BorderBoxControlVisualizer = (props, forwardedRef) => {
  const {
    value,
    ...otherProps
  } = useBorderBoxControlVisualizer(props);
  return /*#__PURE__*/_jsx(View, {
    ...otherProps,
    ref: forwardedRef
  });
};
const ConnectedBorderBoxControlVisualizer = contextConnect(BorderBoxControlVisualizer, 'BorderBoxControlVisualizer');
export default ConnectedBorderBoxControlVisualizer;
//# sourceMappingURL=component.js.map