/**
 * WordPress dependencies
 */
import { Icon, lock } from '@wordpress/icons';
import { usePreferredColorSchemeStyle } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import styles from './styles.scss';
import { jsx as _jsx } from "react/jsx-runtime";
export default function LockIcon() {
  const iconStyle = usePreferredColorSchemeStyle(styles.icon, styles['icon--dark']);
  return /*#__PURE__*/_jsx(Icon, {
    icon: lock,
    color: iconStyle.color,
    style: iconStyle
  });
}
//# sourceMappingURL=index.native.js.map