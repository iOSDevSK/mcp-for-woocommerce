/**
 * WordPress dependencies
 */
import { memo } from '@wordpress/element';
/**
 * Internal dependencies
 */
import SwitchCell from '../mobile/bottom-sheet/switch-cell';
import { jsx as _jsx } from "react/jsx-runtime";
const ToggleControl = memo(({
  label,
  checked,
  help,
  instanceId,
  className,
  onChange,
  ...props
}) => {
  const id = `inspector-toggle-control-${instanceId}`;
  const helpLabel = help && typeof help === 'function' ? help(checked) : help;
  return /*#__PURE__*/_jsx(SwitchCell, {
    label: label,
    id: id,
    help: helpLabel,
    className: className,
    value: checked,
    onValueChange: onChange,
    ...props
  });
});
export default ToggleControl;
//# sourceMappingURL=index.native.js.map