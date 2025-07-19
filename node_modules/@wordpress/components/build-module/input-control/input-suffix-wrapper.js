/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

import { contextConnect, useContextSystem } from '../context';
import { PrefixSuffixWrapper } from './styles/input-control-styles';
import { jsx as _jsx } from "react/jsx-runtime";
function UnconnectedInputControlSuffixWrapper(props, forwardedRef) {
  const derivedProps = useContextSystem(props, 'InputControlSuffixWrapper');
  return /*#__PURE__*/_jsx(PrefixSuffixWrapper, {
    ...derivedProps,
    ref: forwardedRef
  });
}

/**
 * A convenience wrapper for the `suffix` when you want to apply
 * standard padding in accordance with the size variant.
 *
 * ```jsx
 * import {
 *   __experimentalInputControl as InputControl,
 *   __experimentalInputControlSuffixWrapper as InputControlSuffixWrapper,
 * } from '@wordpress/components';
 *
 * <InputControl
 *   suffix={<InputControlSuffixWrapper>%</InputControlSuffixWrapper>}
 * />
 * ```
 */
export const InputControlSuffixWrapper = contextConnect(UnconnectedInputControlSuffixWrapper, 'InputControlSuffixWrapper');
export default InputControlSuffixWrapper;
//# sourceMappingURL=input-suffix-wrapper.js.map