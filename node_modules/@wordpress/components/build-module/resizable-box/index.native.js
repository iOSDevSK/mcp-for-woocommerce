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
import styles from './style.scss';
import { jsx as _jsx } from "react/jsx-runtime";
function ResizableBox(props) {
  const {
    size,
    showHandle = true,
    getStylesFromColorScheme
  } = props;
  const {
    height
  } = size;
  const defaultStyle = getStylesFromColorScheme(styles.staticSpacer, styles.staticDarkSpacer);
  return /*#__PURE__*/_jsx(View, {
    style: [defaultStyle, showHandle && styles.selectedSpacer, {
      height
    }]
  });
}
export default withPreferredColorScheme(ResizableBox);
//# sourceMappingURL=index.native.js.map