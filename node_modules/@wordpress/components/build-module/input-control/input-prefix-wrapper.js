/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

import { contextConnect, useContextSystem } from '../context';
import { PrefixSuffixWrapper } from './styles/input-control-styles';
import { jsx as _jsx } from "react/jsx-runtime";
function UnconnectedInputControlPrefixWrapper(props, forwardedRef) {
  const derivedProps = useContextSystem(props, 'InputControlPrefixWrapper');
  return /*#__PURE__*/_jsx(PrefixSuffixWrapper, {
    ...derivedProps,
    isPrefix: true,
    ref: forwardedRef
  });
}

/**
 * A convenience wrapper for the `prefix` when you want to apply
 * standard padding in accordance with the size variant.
 *
 * ```jsx
 * import {
 *   __experimentalInputControl as InputControl,
 *   __experimentalInputControlPrefixWrapper as InputControlPrefixWrapper,
 * } from '@wordpress/components';
 *
 * <InputControl
 *   prefix={<InputControlPrefixWrapper>@</InputControlPrefixWrapper>}
 * />
 * ```
 */
export const InputControlPrefixWrapper = contextConnect(UnconnectedInputControlPrefixWrapper, 'InputControlPrefixWrapper');
export default InputControlPrefixWrapper;
//# sourceMappingURL=input-prefix-wrapper.js.map