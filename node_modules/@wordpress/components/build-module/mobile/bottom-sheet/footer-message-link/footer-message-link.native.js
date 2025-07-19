/**
 * External dependencies
 */
import { Text, Linking } from 'react-native';
/**
 * WordPress dependencies
 */
import { usePreferredColorSchemeStyle } from '@wordpress/compose';
/**
 * Internal dependencies
 */
import styles from './styles.scss';
import { jsx as _jsx } from "react/jsx-runtime";
function FooterMessageLink({
  href,
  value
}) {
  const textStyle = usePreferredColorSchemeStyle(styles.footerMessageLink, styles.footerMessageLinkDark);
  return /*#__PURE__*/_jsx(Text, {
    style: textStyle,
    onPress: () => Linking.openURL(href),
    children: value
  });
}
export default FooterMessageLink;
//# sourceMappingURL=footer-message-link.native.js.map