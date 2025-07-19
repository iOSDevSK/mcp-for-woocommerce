/**
 * WordPress dependencies
 */
import { withPreferredColorScheme } from '@wordpress/compose';
/**
 * Internal dependencies
 */
import Cell from './cell';
import styles from './styles.scss';
import { jsx as _jsx } from "react/jsx-runtime";
function FooterMessageCell({
  textAlign = 'left',
  ...props
}) {
  return /*#__PURE__*/_jsx(Cell, {
    ...props,
    editable: false,
    value: "",
    accessibilityRole: "text",
    labelStyle: [styles.footerMessageCell, {
      textAlign
    }]
  });
}
export default withPreferredColorScheme(FooterMessageCell);
//# sourceMappingURL=footer-message-cell.native.js.map