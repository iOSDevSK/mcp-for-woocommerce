/**
 * External dependencies
 */
import { View, Text, Platform } from 'react-native';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, check } from '@wordpress/icons';
import { usePreferredColorSchemeStyle } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import styles from './styles.scss';
import ActionButton from './action-button';
import { jsx as _jsx } from "react/jsx-runtime";
function ApplyButton({
  onPress
}) {
  const buttonTextStyle = usePreferredColorSchemeStyle(styles['button-text'], styles['button-text-dark']);
  const applyButtonStyle = usePreferredColorSchemeStyle(styles['apply-button-icon'], styles['apply-button-icon-dark']);
  return /*#__PURE__*/_jsx(View, {
    style: styles['apply-button'],
    children: /*#__PURE__*/_jsx(ActionButton, {
      onPress: onPress,
      accessibilityLabel: __('Apply'),
      accessibilityHint: __('Applies the setting'),
      children: Platform.OS === 'ios' ? /*#__PURE__*/_jsx(Text, {
        style: buttonTextStyle,
        maxFontSizeMultiplier: 2,
        children: __('Apply')
      }) : /*#__PURE__*/_jsx(Icon, {
        icon: check,
        size: 24,
        style: applyButtonStyle
      })
    })
  });
}
export default ApplyButton;
//# sourceMappingURL=apply-button.native.js.map