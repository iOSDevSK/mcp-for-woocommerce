/**
 * External dependencies
 */
import { View } from 'react-native';

/**
 * Internal dependencies
 */
import ApplyButton from './apply-button';
import Button from './back-button';
import Heading from './heading';
import styles from './styles.scss';
import { jsx as _jsx } from "react/jsx-runtime";
function NavBar({
  children
}) {
  return /*#__PURE__*/_jsx(View, {
    style: styles['nav-bar'],
    children: children
  });
}
NavBar.ApplyButton = ApplyButton;
NavBar.BackButton = Button.Back;
NavBar.DismissButton = Button.Dismiss;
NavBar.Heading = Heading;
export default NavBar;
//# sourceMappingURL=index.native.js.map