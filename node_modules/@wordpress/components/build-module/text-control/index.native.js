/**
 * WordPress dependencies
 */
import { memo } from '@wordpress/element';
/**
 * Internal dependencies
 */
import Cell from '../mobile/bottom-sheet/cell';
import { jsx as _jsx } from "react/jsx-runtime";
function TextControl({
  label,
  hideLabelFromVision,
  value,
  help,
  className,
  instanceId,
  onChange,
  type = 'text',
  placeholder,
  ...props
}) {
  const id = `inspector-text-control-${instanceId}`;
  return /*#__PURE__*/_jsx(Cell, {
    label: label,
    hideLabelFromVision: hideLabelFromVision,
    id: id,
    help: help,
    className: className,
    type: type,
    value: value,
    onChangeValue: onChange,
    "aria-describedby": !!help ? id + '__help' : undefined,
    valuePlaceholder: placeholder,
    ...props
  });
}
export default memo(TextControl);
//# sourceMappingURL=index.native.js.map