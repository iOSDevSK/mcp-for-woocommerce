/**
 * External dependencies
 */
import { View } from 'react-native';

/**
 * WordPress dependencies
 */
import { usePreferredColorSchemeStyle } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import styles from './style.scss';
import { jsx as _jsx } from "react/jsx-runtime";
const BackgroundView = ({
  children
}) => {
  const backgroundStyles = usePreferredColorSchemeStyle(styles['components-autocomplete__background'], styles['components-autocomplete__background-dark']);
  return /*#__PURE__*/_jsx(View, {
    style: backgroundStyles,
    children: children
  });
};
export default BackgroundView;
//# sourceMappingURL=background-view.android.js.map