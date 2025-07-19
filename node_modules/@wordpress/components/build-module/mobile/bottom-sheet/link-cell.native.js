/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { link, Icon, chevronRight } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import Cell from './cell';
import styles from './styles.scss';
import { jsx as _jsx } from "react/jsx-runtime";
const {
  placeholderColor
} = styles;
export default function LinkCell({
  value,
  valueMask,
  onPress,
  showIcon = true
}) {
  return /*#__PURE__*/_jsx(Cell, {
    icon: showIcon && link,
    label: __('Link to')
    // Since this is not actually editable, we treat value as a placeholder.
    ,
    value: valueMask || value || __('Search or type URL'),
    valueStyle: !!(value || valueMask) ? undefined : placeholderColor,
    onPress: onPress,
    children: /*#__PURE__*/_jsx(Icon, {
      icon: chevronRight
    })
  });
}
//# sourceMappingURL=link-cell.native.js.map