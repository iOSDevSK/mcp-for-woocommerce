/**
 * WordPress dependencies
 */
import { memo } from '@wordpress/element';
/**
 * Internal dependencies
 */
import CyclePickerCell from '../bottom-sheet/cycle-picker-cell';
import { jsx as _jsx } from "react/jsx-runtime";
function CycleSelectControl({
  help,
  instanceId,
  label,
  multiple = false,
  onChange,
  options = [],
  className,
  hideLabelFromVision,
  ...props
}) {
  const id = `inspector-select-control-${instanceId}`;
  return /*#__PURE__*/_jsx(CyclePickerCell, {
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
}
export default memo(CycleSelectControl);
//# sourceMappingURL=index.native.js.map