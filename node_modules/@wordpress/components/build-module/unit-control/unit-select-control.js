/**
 * External dependencies
 */
import clsx from 'clsx';
/**
 * WordPress dependencies
 */
import { forwardRef } from '@wordpress/element';

/**
 * Internal dependencies
 */

import { UnitSelect, UnitLabel } from './styles/unit-control-styles';
import { CSS_UNITS, hasUnits } from './utils';
import { jsx as _jsx } from "react/jsx-runtime";
function UnitSelectControl({
  className,
  isUnitSelectTabbable: isTabbable = true,
  onChange,
  size = 'default',
  unit = 'px',
  units = CSS_UNITS,
  ...props
}, ref) {
  if (!hasUnits(units) || units?.length === 1) {
    return /*#__PURE__*/_jsx(UnitLabel, {
      className: "components-unit-control__unit-label",
      selectSize: size,
      children: unit
    });
  }
  const handleOnChange = event => {
    const {
      value: unitValue
    } = event.target;
    const data = units.find(option => option.value === unitValue);
    onChange?.(unitValue, {
      event,
      data
    });
  };
  const classes = clsx('components-unit-control__select', className);
  return /*#__PURE__*/_jsx(UnitSelect, {
    ref: ref,
    className: classes,
    onChange: handleOnChange,
    selectSize: size,
    tabIndex: isTabbable ? undefined : -1,
    value: unit,
    ...props,
    children: units.map(option => /*#__PURE__*/_jsx("option", {
      value: option.value,
      children: option.label
    }, option.value))
  });
}
export default forwardRef(UnitSelectControl);
//# sourceMappingURL=unit-select-control.js.map