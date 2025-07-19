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
import styles from './bottom-separator-cover.scss';
import { jsx as _jsx } from "react/jsx-runtime";
function BottomSeparatorCover({
  getStylesFromColorScheme
}) {
  return /*#__PURE__*/_jsx(View, {
    style: getStylesFromColorScheme(styles.coverSeparator, styles.coverSeparatorDark)
  });
}
export default withPreferredColorScheme(BottomSeparatorCover);
//# sourceMappingURL=bottom-separator-cover.native.js.map