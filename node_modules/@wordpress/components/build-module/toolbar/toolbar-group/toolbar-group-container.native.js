/**
 * External dependencies
 */
import { StyleSheet, View } from 'react-native';

/**
 * WordPress dependencies
 */
import { usePreferredColorSchemeStyle } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import styles from './style.scss';
import { jsx as _jsx } from "react/jsx-runtime";
const ToolbarGroupContainer = ({
  passedStyle,
  children
}) => {
  const groupStyles = [usePreferredColorSchemeStyle(styles.container, styles.containerDark), {
    borderLeftWidth: StyleSheet.hairlineWidth
  }, passedStyle];
  return /*#__PURE__*/_jsx(View, {
    style: groupStyles,
    children: children
  });
};
export default ToolbarGroupContainer;
//# sourceMappingURL=toolbar-group-container.native.js.map