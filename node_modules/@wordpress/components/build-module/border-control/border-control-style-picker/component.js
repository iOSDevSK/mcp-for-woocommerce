/**
 * WordPress dependencies
 */
import { lineDashed, lineDotted, lineSolid } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { contextConnect } from '../../context';
import { ToggleGroupControl, ToggleGroupControlOptionIcon } from '../../toggle-group-control';
import { jsx as _jsx } from "react/jsx-runtime";
const BORDER_STYLES = [{
  label: __('Solid'),
  icon: lineSolid,
  value: 'solid'
}, {
  label: __('Dashed'),
  icon: lineDashed,
  value: 'dashed'
}, {
  label: __('Dotted'),
  icon: lineDotted,
  value: 'dotted'
}];
function UnconnectedBorderControlStylePicker({
  onChange,
  ...restProps
}, forwardedRef) {
  return /*#__PURE__*/_jsx(ToggleGroupControl, {
    __nextHasNoMarginBottom: true,
    __next40pxDefaultSize: true,
    ref: forwardedRef,
    isDeselectable: true,
    onChange: value => {
      onChange?.(value);
    },
    ...restProps,
    children: BORDER_STYLES.map(borderStyle => /*#__PURE__*/_jsx(ToggleGroupControlOptionIcon, {
      value: borderStyle.value,
      icon: borderStyle.icon,
      label: borderStyle.label
    }, borderStyle.value))
  });
}
const BorderControlStylePicker = contextConnect(UnconnectedBorderControlStylePicker, 'BorderControlStylePicker');
export default BorderControlStylePicker;
//# sourceMappingURL=component.js.map