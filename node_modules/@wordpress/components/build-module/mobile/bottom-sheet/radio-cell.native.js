/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, check } from '@wordpress/icons';
import { usePreferredColorSchemeStyle } from '@wordpress/compose';
/**
 * Internal dependencies
 */
import Cell from './cell';
import styles from './styles.scss';
import { jsx as _jsx } from "react/jsx-runtime";
export default function BottomSheetRadioCell(props) {
  const {
    selected,
    ...cellProps
  } = props;
  const selectedIconStyle = usePreferredColorSchemeStyle(styles.selectedIcon, styles.selectedIconDark);
  return /*#__PURE__*/_jsx(Cell, {
    ...cellProps,
    accessibilityRole: "radio",
    accessibilityState: {
      selected
    },
    accessibilityHint: /* translators: accessibility text (hint for selecting option) */
    __('Double tap to select the option'),
    editable: false,
    value: "",
    showLockIcon: selected,
    children: selected && /*#__PURE__*/_jsx(Icon, {
      icon: check,
      style: selectedIconStyle
    })
  });
}
//# sourceMappingURL=radio-cell.native.js.map