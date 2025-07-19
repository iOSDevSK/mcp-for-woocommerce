/**
 * External dependencies
 */
import { View } from 'react-native';
/**
 * WordPress dependencies
 */
import { withPreferredColorScheme } from '@wordpress/compose';
/**
 * Internal dependencies
 */
import DropdownMenu from '../../dropdown-menu';
import styles from './style.scss';
import { jsx as _jsx } from "react/jsx-runtime";
function ToolbarGroupCollapsed({
  controls = [],
  getStylesFromColorScheme,
  passedStyle,
  ...props
}) {
  return /*#__PURE__*/_jsx(View, {
    style: [getStylesFromColorScheme(styles.container, styles.containerDark), passedStyle],
    children: /*#__PURE__*/_jsx(DropdownMenu, {
      controls: controls,
      ...props
    })
  });
}
export default withPreferredColorScheme(ToolbarGroupCollapsed);
//# sourceMappingURL=toolbar-group-collapsed.native.js.map