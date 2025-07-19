/**
 * External dependencies
 */
import { colord } from 'colord';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { InputControl } from '../input-control';
import { Text } from '../text';
import { COLORS } from '../utils/colors-values';
import InputControlPrefixWrapper from '../input-control/input-prefix-wrapper';
import { jsx as _jsx } from "react/jsx-runtime";
export const HexInput = ({
  color,
  onChange,
  enableAlpha
}) => {
  const handleChange = nextValue => {
    if (!nextValue) {
      return;
    }
    const hexValue = nextValue.startsWith('#') ? nextValue : '#' + nextValue;
    onChange(colord(hexValue));
  };
  const stateReducer = (state, action) => {
    const nativeEvent = action.payload?.event?.nativeEvent;
    if ('insertFromPaste' !== nativeEvent?.inputType) {
      return {
        ...state
      };
    }
    const value = state.value?.startsWith('#') ? state.value.slice(1).toUpperCase() : state.value?.toUpperCase();
    return {
      ...state,
      value
    };
  };
  return /*#__PURE__*/_jsx(InputControl, {
    prefix: /*#__PURE__*/_jsx(InputControlPrefixWrapper, {
      children: /*#__PURE__*/_jsx(Text, {
        color: COLORS.theme.accent,
        lineHeight: 1,
        children: "#"
      })
    }),
    value: color.toHex().slice(1).toUpperCase(),
    onChange: handleChange,
    maxLength: enableAlpha ? 9 : 7,
    label: __('Hex color'),
    hideLabelFromVision: true,
    size: "__unstable-large",
    __unstableStateReducer: stateReducer,
    __unstableInputWidth: "9em"
  });
};
//# sourceMappingURL=hex-input.js.map