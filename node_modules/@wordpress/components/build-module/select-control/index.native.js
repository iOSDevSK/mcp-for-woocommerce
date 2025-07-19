/**
 * WordPress dependencies
 */
import { memo } from '@wordpress/element';
/**
 * Internal dependencies
 */
import PickerCell from '../mobile/bottom-sheet/picker-cell';
import { jsx as _jsx } from "react/jsx-runtime";
export const SelectControl = memo(({
  help,
  instanceId,
  label,
  multiple = false,
  onChange,
  options = [],
  className,
  hideLabelFromVision,
  ...props
}) => {
  const id = `inspector-select-control-${instanceId}`;
  return /*#__PURE__*/_jsx(PickerCell, {
    label: label,
    hideLabelFromVision: hideLabelFromVision,
    id: id,
    help: help,
    className: className,
    onChangeValue: onChange,
    "aria-describedby": !!help ? `${id}__help` : undefined,
    multiple: multiple,
    options: options,
    ...props
  });
});
export default SelectControl;
//# sourceMappingURL=index.native.js.map