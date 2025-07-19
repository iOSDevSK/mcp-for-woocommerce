/**
 * External dependencies
 */
import { Text } from 'react-native';

/**
 * WordPress dependencies
 */
import { usePreferredColorSchemeStyle } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import styles from './styles.scss';
import { jsx as _jsx } from "react/jsx-runtime";
function Heading({
  children
}) {
  const headingStyle = usePreferredColorSchemeStyle(styles.heading, styles['heading-dark']);
  return /*#__PURE__*/_jsx(Text, {
    accessibilityRole: "header",
    style: headingStyle,
    maxFontSizeMultiplier: 3,
    children: children
  });
}
export default Heading;
//# sourceMappingURL=heading.native.js.map